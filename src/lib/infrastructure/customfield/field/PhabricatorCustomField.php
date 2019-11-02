<?php

namespace orangins\lib\infrastructure\customfield\field;

use orangins\lib\infrastructure\customfield\editor\PhabricatorCustomFieldEditField;
use orangins\lib\infrastructure\customfield\storage\PhabricatorCustomFieldIndexStorage;
use orangins\lib\infrastructure\customfield\storage\PhabricatorCustomFieldNumericIndexStorage;
use orangins\lib\infrastructure\customfield\storage\PhabricatorCustomFieldStorage;
use orangins\lib\infrastructure\customfield\storage\PhabricatorCustomFieldStringIndexStorage;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldDataNotAvailableException;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldNotAttachedException;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldNotProxyException;
use orangins\lib\infrastructure\customfield\interfaces\PhabricatorCustomFieldInterface;
use orangins\lib\infrastructure\customfield\interfaces\PhabricatorStandardCustomFieldInterface;
use orangins\lib\infrastructure\customfield\standard\PhabricatorStandardCustomField;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\lib\OranginsObject;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\metamta\view\PhabricatorMetaMTAMailBody;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\index\PhabricatorSearchAbstractDocument;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use PhutilClassMapQuery;
use Exception;
use PhutilJSONParserException;
use PhutilSafeHTML;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * @task apps         Building Applications with Custom Fields
 * @task core         Core Properties and Field Identity
 * @task proxy        Field Proxies
 * @task context      Contextual Data
 * @task render       Rendering Utilities
 * @task storage      Field Storage
 * @task edit         Integration with Edit Views
 * @task view         Integration with Property Views
 * @task array         Integration with List views
 * @task appsearch    Integration with ApplicationSearch
 * @task appxaction   Integration with ApplicationTransactions
 * @task xactionmail  Integration with Transaction Mail
 * @task globalsearch Integration with Global Search
 * @task herald       Integration with Herald
 */
abstract class PhabricatorCustomField extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $object;
    /**
     * @var PhabricatorStandardCustomField
     */
    private $proxy;

    /**
     *
     */
    const ROLE_APPLICATIONTRANSACTIONS = 'ApplicationTransactions';
    /**
     *
     */
    const ROLE_TRANSACTIONMAIL = 'ApplicationTransactions.mail';
    /**
     *
     */
    const ROLE_APPLICATIONSEARCH = 'ApplicationSearch';
    /**
     *
     */
    const ROLE_STORAGE = 'storage';
    /**
     *
     */
    const ROLE_DEFAULT = 'default';
    /**
     *
     */
    const ROLE_EDIT = 'edit';
    /**
     *
     */
    const ROLE_VIEW = 'view';
    /**
     *
     */
    const ROLE_LIST = 'list';
    /**
     *
     */
    const ROLE_GLOBALSEARCH = 'GlobalSearch';
    /**
     *
     */
    const ROLE_CONDUIT = 'conduit';
    /**
     *
     */
    const ROLE_HERALD = 'herald';
    /**
     *
     */
    const ROLE_EDITENGINE = 'EditEngine';
    /**
     *
     */
    const ROLE_HERALDACTION = 'herald.action';
    /**
     *
     */
    const ROLE_EXPORT = 'export';


    /* -(  Building Applications with Custom Fields  )--------------------------- */


    /**
     * @task apps
     * @param PhabricatorCustomFieldInterface $object
     * @param $role
     * @return PhabricatorCustomFieldList|mixed
     * @throws Exception
     */
    public static function getObjectFields(
        PhabricatorCustomFieldInterface $object,
        $role)
    {

        try {
            $attachment = $object->getCustomFields();
        } catch (PhabricatorDataNotAttachedException $ex) {
            $attachment = new PhabricatorCustomFieldAttachment();
            $object->attachCustomFields($attachment);
        }

        try {
            $field_list = $attachment->getCustomFieldList($role);
        } catch (PhabricatorCustomFieldNotAttachedException $ex) {
            $base_class = $object->getCustomFieldBaseClass();

            $spec = $object->getCustomFieldSpecificationForRole($role);
            if (!is_array($spec)) {
                throw new Exception(
                    Yii::t("app",
                        "Expected an array from {0} for object of class '{1}'.",
                        [
                            'getCustomFieldSpecificationForRole()',
                            get_class($object)
                        ]));
            }

            $fields = self::buildFieldList(
                $base_class,
                $spec,
                $object);

            foreach ($fields as $key => $field) {
                if (!$field->shouldEnableForRole($role)) {
                    unset($fields[$key]);
                }
            }

            foreach ($fields as $field) {
                $field->setObject($object);
            }

            $field_list = new PhabricatorCustomFieldList($fields);
            $attachment->addCustomFieldList($role, $field_list);
        }

        return $field_list;
    }


    /**
     * @task apps
     * @param PhabricatorCustomFieldInterface $object
     * @param $role
     * @param $field_key
     * @return PhabricatorCustomField
     * @throws Exception
     */
    public static function getObjectField(
        PhabricatorCustomFieldInterface $object,
        $role,
        $field_key)
    {

        $fields = self::getObjectFields($object, $role)->getFields();

        return ArrayHelper::getValue($fields, $field_key);
    }


    /**
     * @task apps
     * @param $base_class
     * @param array $spec
     * @param $object
     * @param array $options
     * @return array
     * @throws Exception
     */
    public static function buildFieldList(
        $base_class,
        array $spec,
        $object,
        array $options = array())
    {

        /** @var PhabricatorCustomField[] $field_objects */
        $field_objects = (new PhutilClassMapQuery())
            ->setAncestorClass($base_class)
            ->execute();

        $fields = array();
        foreach ($field_objects as $field_object) {
            $field_object = clone $field_object;
            foreach ($field_object->createFields($object) as $field) {
                $key = $field->getFieldKey();
                if (isset($fields[$key])) {
                    throw new Exception(
                        Yii::t("app",
                            "Both '{0}' and '{1}' define a custom field with " .
                            "field key '{2}'. Field keys must be unique.", [
                                get_class($fields[$key]),
                                get_class($field),
                                $key
                            ]));
                }
                $fields[$key] = $field;
            }
        }

        foreach ($fields as $key => $field) {
            if (!$field->isFieldEnabled()) {
                unset($fields[$key]);
            }
        }

        $fields = array_select_keys($fields, array_keys($spec)) + $fields;

        if (empty($options['withDisabled'])) {
            foreach ($fields as $key => $field) {
                if (isset($spec[$key]['disabled'])) {
                    $is_disabled = $spec[$key]['disabled'];
                } else {
                    $is_disabled = $field->shouldDisableByDefault();
                }

                if ($is_disabled) {
                    if ($field->canDisableField()) {
                        unset($fields[$key]);
                    }
                }
            }
        }

        return $fields;
    }


    /* -(  Core Properties and Field Identity  )--------------------------------- */


    /**
     * Return a key which uniquely identifies this field, like
     * "mycompany:dinosaur:count". Normally you should provide some level of
     * namespacing to prevent collisions.
     *
     * @return string String which uniquely identifies this field.
     * @task core
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function getFieldKey()
    {
        if ($this->proxy) {
            return $this->proxy->getFieldKey();
        }
        throw new PhabricatorCustomFieldImplementationIncompleteException(
            $this,
            $field_key_is_incomplete = true);
    }

    /**
     * @return string
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function getModernFieldKey()
    {
        if ($this->proxy) {
            return $this->proxy->getModernFieldKey();
        }
        return $this->getFieldKey();
    }


    /**
     * Return a human-readable field name.
     *
     * @return string Human readable field name.
     * @task core
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function getFieldName()
    {
        if ($this->proxy) {
            return $this->proxy->getFieldName();
        }
        return $this->getModernFieldKey();
    }


    /**
     * Return a short, human-readable description of the field's behavior. This
     * provides more context to administrators when they are customizing fields.
     *
     * @return string|null Optional human-readable description.
     * @task core
     */
    public function getFieldDescription()
    {
        if ($this->proxy) {
            return $this->proxy->getFieldDescription();
        }
        return null;
    }


    /**
     * Most field implementations are unique, in that one class corresponds to
     * one field. However, some field implementations are general and a single
     * implementation may drive several fields.
     *
     * For general implementations, the general field implementation can return
     * multiple field instances here.
     *
     * @param object The object to create fields for.
     * @return PhabricatorCustomField[] List of fields.
     * @task core
     */
    public function createFields($object)
    {
        return array($this);
    }


    /**
     * You can return `false` here if the field should not be enabled for any
     * role. For example, it might depend on something (like an application or
     * library) which isn't installed, or might have some global configuration
     * which allows it to be disabled.
     *
     * @return bool False to completely disable this field for all roles.
     * @task core
     */
    public function isFieldEnabled()
    {
        if ($this->proxy) {
            return $this->proxy->isFieldEnabled();
        }
        return true;
    }


    /**
     * Low level selector for field availability. Fields can appear in different
     * roles (like an edit view, a array view, etc.), but not every field needs
     * to appear everywhere. Fields that are disabled in a role won't appear in
     * that context within applications.
     *
     * Normally, you do not need to override this method. Instead, override the
     * methods specific to roles you want to enable. For example, implement
     * @{method:shouldUseStorage()} to activate the `'storage'` role.
     *
     * @return bool True to enable the field for the given role.
     * @task core
     * @throws Exception
     */
    public function shouldEnableForRole($role)
    {

        // NOTE: All of these calls proxy individually, so we don't need to
        // proxy this call as a whole.

        switch ($role) {
            case self::ROLE_APPLICATIONTRANSACTIONS:
                return $this->shouldAppearInApplicationTransactions();
            case self::ROLE_APPLICATIONSEARCH:
                return $this->shouldAppearInApplicationSearch();
            case self::ROLE_STORAGE:
                return $this->shouldUseStorage();
            case self::ROLE_EDIT:
                return $this->shouldAppearInEditView();
            case self::ROLE_VIEW:
                return $this->shouldAppearInPropertyView();
            case self::ROLE_LIST:
                return $this->shouldAppearInListView();
            case self::ROLE_GLOBALSEARCH:
                return $this->shouldAppearInGlobalSearch();
            case self::ROLE_CONDUIT:
                return $this->shouldAppearInConduitDictionary();
            case self::ROLE_TRANSACTIONMAIL:
                return $this->shouldAppearInTransactionMail();
            case self::ROLE_HERALD:
                return $this->shouldAppearInHerald();
            case self::ROLE_HERALDACTION:
                return $this->shouldAppearInHeraldActions();
            case self::ROLE_EDITENGINE:
                return $this->shouldAppearInEditView() ||
                    $this->shouldAppearInEditEngine();
            case self::ROLE_EXPORT:
                return $this->shouldAppearInDataExport();
            case self::ROLE_DEFAULT:
                return true;
            default:
                throw new Exception(Yii::t("app", "Unknown field role '{0}'!", [
                    $role
                ]));
        }
    }


    /**
     * Allow administrators to disable this field. Most fields should allow this,
     * but some are fundamental to the behavior of the application and can be
     * locked down to avoid chaos, disorder, and the decline of civilization.
     *
     * @return bool False to prevent this field from being disabled through
     *              configuration.
     * @task core
     */
    public function canDisableField()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldDisableByDefault()
    {
        return false;
    }


    /**
     * Return an index string which uniquely identifies this field.
     *
     * @return string Index string which uniquely identifies this field.
     * @task core
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    final public function getFieldIndex()
    {
        return PhabricatorHash::digestForIndex($this->getFieldKey());
    }


    /* -(  Field Proxies  )------------------------------------------------------ */


    /**
     * Proxies allow a field to use some other field's implementation for most
     * of their behavior while still subclassing an application field. When a
     * proxy is set for a field with @{method:setProxy}, all of its methods will
     * call through to the proxy by default.
     *
     * This is most commonly used to implement configuration-driven custom fields
     * using @{class:PhabricatorStandardCustomField}.
     *
     * This method must be overridden to return `true` before a field can accept
     * proxies.
     *
     * @return bool True if you can @{method:setProxy} this field.
     * @task proxy
     */
    public function canSetProxy()
    {
        if ($this instanceof PhabricatorStandardCustomFieldInterface) {
            return true;
        }
        return false;
    }


    /**
     * Set the proxy implementation for this field. See @{method:canSetProxy} for
     * discussion of field proxies.
     *
     * @param PhabricatorCustomField Field implementation.
     * @return PhabricatorCustomField
     * @throws PhabricatorCustomFieldNotProxyException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    final public function setProxy(PhabricatorCustomField $proxy)
    {
        if (!$this->canSetProxy()) {
            throw new PhabricatorCustomFieldNotProxyException($this);
        }

        $this->proxy = $proxy;
        return $this;
    }


    /**
     * Get the field's proxy implementation, if any. For discussion, see
     * @{method:canSetProxy}.
     *
     * @return PhabricatorStandardCustomField  Proxy field, if one is set.
     */
    final public function getProxy()
    {
        return $this->proxy;
    }


    /* -(  Contextual Data  )---------------------------------------------------- */


    /**
     * Sets the object this field belongs to.
     *
     * @param PhabricatorCustomFieldInterface $object The object this field belongs to.
     * @return static
     * @task context
     */
    final public function setObject(PhabricatorCustomFieldInterface $object)
    {
        if ($this->proxy) {
            $this->proxy->setObject($object);
            return $this;
        }

        $this->object = $object;
        $this->didSetObject($object);
        return $this;
    }


    /**
     * Read object data into local field storage, if applicable.
     *
     * @param PhabricatorCustomFieldInterface $object
     * @return PhabricatorCustomField
     * @task context
     */
    public function readValueFromObject(PhabricatorCustomFieldInterface $object)
    {
        if ($this->proxy) {
            $this->proxy->readValueFromObject($object);
        }
        return $this;
    }


    /**
     * Get the object this field belongs to.
     *
     * @return PhabricatorCustomFieldInterface The object this field belongs to.
     * @task context
     */
    final public function getObject()
    {
        if ($this->proxy) {
            return $this->proxy->getObject();
        }

        return $this->object;
    }


    /**
     * This is a hook, primarily for subclasses to load object data.
     *
     * @param PhabricatorCustomFieldInterface $object
     * @return void The object this field belongs to.
     */
    protected function didSetObject(PhabricatorCustomFieldInterface $object)
    {
        return;
    }


    /**
     * @task context
     * @param PhabricatorUser $viewer
     * @return PhabricatorCustomField
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        if ($this->proxy) {
            $this->proxy->setViewer($viewer);
            return $this;
        }

        $this->viewer = $viewer;
        return $this;
    }


    /**
     * @task context
     */
    final public function getViewer()
    {
        if ($this->proxy) {
            return $this->proxy->getViewer();
        }

        return $this->viewer;
    }


    /**
     * @task context
     * @return
     * @throws PhabricatorCustomFieldDataNotAvailableException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    final protected function requireViewer()
    {
        if ($this->proxy) {
            return $this->proxy->requireViewer();
        }

        if (!$this->viewer) {
            throw new PhabricatorCustomFieldDataNotAvailableException($this);
        }
        return $this->viewer;
    }


    /* -(  Rendering Utilities  )------------------------------------------------ */


    /**
     * @task render
     * @param array $handles
     * @return null|PhutilSafeHTML
     * @throws Exception
     */
    protected function renderHandleList(array $handles)
    {
        if (!$handles) {
            return null;
        }

        $out = array();
        foreach ($handles as $handle) {
            $out[] = $handle->renderHovercardLink();
        }

        return phutil_implode_html(phutil_tag('br'), $out);
    }


    /* -(  Storage  )------------------------------------------------------------ */


    /**
     * Return true to use field storage.
     *
     * Fields which can be edited by the user will most commonly use storage,
     * while some other types of fields (for instance, those which just display
     * information in some stylized way) may not. Many builtin fields do not use
     * storage because their data is available on the object itself.
     *
     * If you implement this, you must also implement @{method:getValueForStorage}
     * and @{method:setValueFromStorage}.
     *
     * @return bool True to use storage.
     * @task storage
     */
    public function shouldUseStorage()
    {
        if ($this->proxy) {
            return $this->proxy->shouldUseStorage();
        }
        return false;
    }


    /**
     * Return a new, empty storage object. This should be a subclass of
     * @{class:PhabricatorCustomFieldStorage} which is bound to the application's
     * database.
     *
     * @return PhabricatorCustomFieldStorage New empty storage object.
     * @task storage
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function newStorageObject()
    {
        // NOTE: This intentionally isn't proxied, to avoid call cycles.
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }


    /**
     * Return a serialized representation of the field value, appropriate for
     * storing in auxiliary field storage. You must implement this method if
     * you implement @{method:shouldUseStorage}.
     *
     * If the field value is a scalar, it can be returned unmodiifed. If not,
     * it should be serialized (for example, using JSON).
     *
     * @return string Serialized field value.
     * @task storage
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function getValueForStorage()
    {
        if ($this->proxy) {
            return $this->proxy->getValueForStorage();
        }
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }


    /**
     * Set the field's value given a serialized storage value. This is called
     * when the field is loaded; if no data is available, the value will be
     * null. You must implement this method if you implement
     * @{method:shouldUseStorage}.
     *
     * Usually, the value can be loaded directly. If it isn't a scalar, you'll
     * need to undo whatever serialization you applied in
     * @{method:getValueForStorage}.
     *
     * @param string|null Serialized field representation (from
     *                    @{method:getValueForStorage}) or null if no value has
     *                    ever been stored.
     * @return static
     * @task storage
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function setValueFromStorage($value)
    {
        if ($this->proxy) {
            return $this->proxy->setValueFromStorage($value);
        }
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }

    /**
     * @return $this
     * @author 陈妙威
     */
    public function didSetValueFromStorage()
    {
        if ($this->proxy) {
            return $this->proxy->didSetValueFromStorage();
        }
        return $this;
    }


    /* -(  ApplicationSearch  )-------------------------------------------------- */


    /**
     * Appearing in ApplicationSearch allows a field to be indexed and searched
     * for.
     *
     * @return bool True to appear in ApplicationSearch.
     * @task appsearch
     */
    public function shouldAppearInApplicationSearch()
    {
        if ($this->proxy) {
            return $this->proxy->shouldAppearInApplicationSearch();
        }
        return false;
    }


    /**
     * Return one or more indexes which this field can meaningfully query against
     * to implement ApplicationSearch.
     *
     * Normally, you should build these using @{method:newStringIndex} and
     * @{method:newNumericIndex}. For example, if a field holds a numeric value
     * it might return a single numeric index:
     *
     *   return array($this->newNumericIndex($this->getValue()));
     *
     * If a field holds a more complex value (like a array of users), it might
     * return several string indexes:
     *
     *   $indexes = array();
     *   foreach ($this->getValue() as $phid) {
     *     $indexes[] = $this->newStringIndex($phid);
     *   }
     *   return $indexes;
     *
     * @return array
     * @task appsearch
     */
    public function buildFieldIndexes()
    {
        if ($this->proxy) {
            return $this->proxy->buildFieldIndexes();
        }
        return array();
    }


    /**
     * Return an index against which this field can be meaningfully ordered
     * against to implement ApplicationSearch.
     *
     * This should be a single index, normally built using
     * @{method:newStringIndex} and @{method:newNumericIndex}.
     *
     * The value of the index is not used.
     *
     * Return null from this method if the field can not be ordered.
     *
     * @return PhabricatorCustomFieldIndexStorage A single index to order by.
     * @task appsearch
     */
    public function buildOrderIndex()
    {
        if ($this->proxy) {
            return $this->proxy->buildOrderIndex();
        }
        return null;
    }


    /**
     * Build a new empty storage object for storing string indexes. Normally,
     * this should be a concrete subclass of
     * @{class:PhabricatorCustomFieldStringIndexStorage}.
     *
     * @return PhabricatorCustomFieldStringIndexStorage Storage object.
     * @task appsearch
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    protected function newStringIndexStorage()
    {
        // NOTE: This intentionally isn't proxied, to avoid call cycles.
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }


    /**
     * Build a new empty storage object for storing string indexes. Normally,
     * this should be a concrete subclass of
     * @{class:PhabricatorCustomFieldStringIndexStorage}.
     *
     * @return PhabricatorCustomFieldStringIndexStorage Storage object.
     * @task appsearch
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    protected function newNumericIndexStorage()
    {
        // NOTE: This intentionally isn't proxied, to avoid call cycles.
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }


    /**
     * Build and populate storage for a string index.
     *
     * @param string String to index.
     * @return PhabricatorCustomFieldStringIndexStorage Populated storage.
     * @task appsearch
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    protected function newStringIndex($value = null)
    {
        if ($this->proxy) {
            return $this->proxy->newStringIndex();
        }

        $key = $this->getFieldIndex();
        return $this
            ->newStringIndexStorage()
            ->setIndexKey($key)
            ->setIndexValue($value);
    }


    /**
     * Build and populate storage for a numeric index.
     *
     * @param string Numeric value to index.
     * @return PhabricatorCustomFieldNumericIndexStorage Populated storage.
     * @task appsearch
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    protected function newNumericIndex($value = null)
    {
        if ($this->proxy) {
            return $this->proxy->newNumericIndex();
        }
        $key = $this->getFieldIndex();
        return $this
            ->newNumericIndexStorage()
            ->setIndexKey($key)
            ->setIndexValue($value);
    }


    /**
     * Read a query value from a request, for storage in a saved query. Normally,
     * this method should, e.g., read a string out of the request.
     *
     * @param PhabricatorApplicationSearchEngine $engine Engine building the query.
     * @param AphrontRequest $request Request to read from.
     * @return mixed
     * @task appsearch
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function readApplicationSearchValueFromRequest(
        PhabricatorApplicationSearchEngine $engine,
        AphrontRequest $request)
    {
        if ($this->proxy) {
            return $this->proxy->readApplicationSearchValueFromRequest(
                $engine,
                $request);
        }
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }


    /**
     * Constrain a query, given a field value. Generally, this method should
     * use `with...()` methods to apply filters or other constraints to the
     * query.
     *
     * @param PhabricatorApplicationSearchEngine $engine
     * @param PhabricatorCursorPagedPolicyAwareQuery $query
     * @param PhabricatorApplicationSearchEngine Engine executing the query.
     * @return mixed
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @task appsearch
     */
    public function applyApplicationSearchConstraintToQuery(
        PhabricatorApplicationSearchEngine $engine,
        PhabricatorCursorPagedPolicyAwareQuery $query,
        $value)
    {
        if ($this->proxy) {
            return $this->proxy->applyApplicationSearchConstraintToQuery(
                $engine,
                $query,
                $value);
        }
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }


    /**
     * Append search controls to the interface.
     *
     * @param PhabricatorApplicationSearchEngine $engine Engine constructing the form.
     * @param AphrontFormView $form The form to update.
     * @param array $value Value from the saved query.
     * @task appsearch
     * @return
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function appendToApplicationSearchForm(
        PhabricatorApplicationSearchEngine $engine,
        AphrontFormView $form,
        $value)
    {
        if ($this->proxy) {
            return $this->proxy->appendToApplicationSearchForm(
                $engine,
                $form,
                $value);
        }
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }


    /* -(  ApplicationTransactions  )-------------------------------------------- */


    /**
     * Appearing in ApplicationTrasactions allows a field to be edited using
     * standard workflows.
     *
     * @return bool True to appear in ApplicationTransactions.
     * @task appxaction
     */
    public function shouldAppearInApplicationTransactions()
    {
        if ($this->proxy) {
            return $this->proxy->shouldAppearInApplicationTransactions();
        }
        return false;
    }


    /**
     * @task appxaction
     */
    public function getApplicationTransactionType()
    {
        if ($this->proxy) {
            return $this->proxy->getApplicationTransactionType();
        }
        return PhabricatorTransactions::TYPE_CUSTOMFIELD;
    }


    /**
     * @task appxaction
     */
    public function getApplicationTransactionMetadata()
    {
        if ($this->proxy) {
            return $this->proxy->getApplicationTransactionMetadata();
        }
        return array();
    }


    /**
     * @task appxaction
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function getOldValueForApplicationTransactions()
    {
        if ($this->proxy) {
            return $this->proxy->getOldValueForApplicationTransactions();
        }
        return $this->getValueForStorage();
    }


    /**
     * @task appxaction
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function getNewValueForApplicationTransactions()
    {
        if ($this->proxy) {
            return $this->proxy->getNewValueForApplicationTransactions();
        }
        return $this->getValueForStorage();
    }


    /**
     * @task appxaction
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function setValueFromApplicationTransactions($value)
    {
        if ($this->proxy) {
            return $this->proxy->setValueFromApplicationTransactions($value);
        }
        return $this->setValueFromStorage($value);
    }


    /**
     * @task appxaction
     * @param PhabricatorApplicationTransaction $xaction
     * @return mixed
     * @throws PhutilJSONParserException
     */
    public function getNewValueFromApplicationTransactions(
        PhabricatorApplicationTransaction $xaction)
    {
        if ($this->proxy) {
            return $this->proxy->getNewValueFromApplicationTransactions($xaction);
        }
        return $xaction->getNewValue();
    }


    /**
     * @task appxaction
     * @param PhabricatorApplicationTransaction $xaction
     * @return bool
     * @throws PhutilJSONParserException
     */
    public function getApplicationTransactionHasEffect(
        PhabricatorApplicationTransaction $xaction)
    {
        if ($this->proxy) {
            return $this->proxy->getApplicationTransactionHasEffect($xaction);
        }
        return ($xaction->getOldValue() !== $xaction->getNewValue());
    }


    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return mixed
     * @task appxaction
     */
    public function applyApplicationTransactionInternalEffects(
        PhabricatorApplicationTransaction $xaction)
    {
        if ($this->proxy) {
            return $this->proxy->applyApplicationTransactionInternalEffects($xaction);
        }
        return;
    }


    /**
     * @task appxaction
     * @param PhabricatorApplicationTransaction $xaction
     * @return array
     */
    public function getApplicationTransactionRemarkupBlocks(
        PhabricatorApplicationTransaction $xaction)
    {
        if ($this->proxy) {
            return $this->proxy->getApplicationTransactionRemarkupBlocks($xaction);
        }
        return array();
    }


    /**
     * @task appxaction
     * @param PhabricatorApplicationTransaction $xaction
     * @return void
     * @throws Exception
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function applyApplicationTransactionExternalEffects(
        PhabricatorApplicationTransaction $xaction)
    {
        if ($this->proxy) {
            return $this->proxy->applyApplicationTransactionExternalEffects($xaction);
        }

        if (!$this->shouldEnableForRole(self::ROLE_STORAGE)) {
            return;
        }

        $this->setValueFromApplicationTransactions($xaction->getNewValue());
        $value = $this->getValueForStorage();

        $table = $this->newStorageObject();
        $conn_w = $table->establishConnection('w');

        if ($value === null) {
            queryfx(
                $conn_w,
                'DELETE FROM %T WHERE objectPHID = %s AND fieldIndex = %s',
                $table->getTableName(),
                $this->getObject()->getPHID(),
                $this->getFieldIndex());
        } else {
            queryfx(
                $conn_w,
                'INSERT INTO %T (objectPHID, fieldIndex, fieldValue)
          VALUES (%s, %s, %s)
          ON DUPLICATE KEY UPDATE fieldValue = VALUES(fieldValue)',
                $table->getTableName(),
                $this->getObject()->getPHID(),
                $this->getFieldIndex(),
                $value);
        }

        return;
    }


    /**
     * Validate transactions for an object. This allows you to raise an error
     * when a transaction would set a field to an invalid value, or when a field
     * is required but no transactions provide value.
     *
     * @param PhabricatorApplicationTransactionEditor $editor
     * @param PhabricatorLiskDAO Editor applying the transactions.
     * @param PhabricatorApplicationTransaction[] $xactions
     * @return array
     *   errors.
     *
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @task appxaction
     */
    public function validateApplicationTransactions(
        PhabricatorApplicationTransactionEditor $editor,
        $type,
        array $xactions)
    {
        if ($this->proxy) {
            return $this->proxy->validateApplicationTransactions(
                $editor,
                $type,
                $xactions);
        }
        return array();
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public function getApplicationTransactionTitle(
        PhabricatorApplicationTransaction $xaction)
    {
        if ($this->proxy) {
            return $this->proxy->getApplicationTransactionTitle(
                $xaction);
        }

        $author_phid = $xaction->getAuthorPHID();
        return Yii::t("app",
            '{0} updated this object.',
            [
                $xaction->renderHandleLink($author_phid)
            ]);
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public function getApplicationTransactionTitleForFeed(
        PhabricatorApplicationTransaction $xaction)
    {
        if ($this->proxy) {
            return $this->proxy->getApplicationTransactionTitleForFeed(
                $xaction);
        }

        $author_phid = $xaction->getAuthorPHID();
        $object_phid = $xaction->getObjectPHID();
        return Yii::t("app",
            '{0} updated {1}.',
            [
                $xaction->renderHandleLink($author_phid),
                $xaction->renderHandleLink($object_phid)
            ]);
    }


    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return bool
     * @author 陈妙威
     */
    public function getApplicationTransactionHasChangeDetails(
        PhabricatorApplicationTransaction $xaction)
    {
        if ($this->proxy) {
            return $this->proxy->getApplicationTransactionHasChangeDetails(
                $xaction);
        }
        return false;
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @param PhabricatorUser $viewer
     * @return null
     * @author 陈妙威
     */
    public function getApplicationTransactionChangeDetails(
        PhabricatorApplicationTransaction $xaction,
        PhabricatorUser $viewer)
    {
        if ($this->proxy) {
            return $this->proxy->getApplicationTransactionChangeDetails(
                $xaction,
                $viewer);
        }
        return null;
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return array
     * @author 陈妙威
     */
    public function getApplicationTransactionRequiredHandlePHIDs(
        PhabricatorApplicationTransaction $xaction)
    {
        if ($this->proxy) {
            return $this->proxy->getApplicationTransactionRequiredHandlePHIDs(
                $xaction);
        }
        return array();
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return bool
     * @author 陈妙威
     */
    public function shouldHideInApplicationTransactions(
        PhabricatorApplicationTransaction $xaction)
    {
        if ($this->proxy) {
            return $this->proxy->shouldHideInApplicationTransactions($xaction);
        }
        return false;
    }


    /* -(  Transaction Mail  )--------------------------------------------------- */


    /**
     * @task xactionmail
     */
    public function shouldAppearInTransactionMail()
    {
        if ($this->proxy) {
            return $this->proxy->shouldAppearInTransactionMail();
        }
        return false;
    }


    /**
     * @task xactionmail
     * @param PhabricatorMetaMTAMailBody $body
     * @param PhabricatorApplicationTransactionEditor $editor
     * @param array $xactions
     * @return mixed
     */
    public function updateTransactionMailBody(
        PhabricatorMetaMTAMailBody $body,
        PhabricatorApplicationTransactionEditor $editor,
        array $xactions)
    {
        if ($this->proxy) {
            return $this->proxy->updateTransactionMailBody($body, $editor, $xactions);
        }
        return;
    }


    /* -(  Edit View  )---------------------------------------------------------- */


    /**
     * @param PhabricatorEditEngine $engine
     * @return PhabricatorEditField[]
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function getEditEngineFields(PhabricatorEditEngine $engine)
    {
        $field = $this->newStandardEditField();

        return array(
            $field,
        );
    }

    /**
     * @return mixed
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    protected function newEditField()
    {
        $field = (new PhabricatorCustomFieldEditField())
            ->setCustomField($this);

        $http_type = $this->getHTTPParameterType();
        if ($http_type) {
            $field->setCustomFieldHTTPParameterType($http_type);
        }

        $conduit_type = $this->getConduitEditParameterType();
        if ($conduit_type) {
            $field->setCustomFieldConduitParameterType($conduit_type);
        }

        $bulk_type = $this->getBulkParameterType();
        if ($bulk_type) {
            $field->setCustomFieldBulkParameterType($bulk_type);
        }

        $comment_action = $this->getCommentAction();
        if ($comment_action) {
            $field
                ->setCustomFieldCommentAction($comment_action)
                ->setCommentActionLabel(
                    Yii::t("app",
                        'Change {0}',
                        [
                            $this->getFieldName()
                        ]));
        }

        return $field;
    }

    /**
     * @return mixed
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    protected function newStandardEditField()
    {
        if ($this->proxy) {
            return $this->proxy->newStandardEditField();
        }

        if ($this->shouldAppearInEditView()) {
            $form_field = true;
        } else {
            $form_field = false;
        }

        $bulk_label = $this->getBulkEditLabel();

        return $this->newEditField()
            ->setKey($this->getFieldKey())
            ->setEditTypeKey($this->getModernFieldKey())
            ->setLabel($this->getFieldName())
            ->setBulkEditLabel($bulk_label)
            ->setDescription($this->getFieldDescription())
            ->setTransactionType($this->getApplicationTransactionType())
            ->setIsFormField($form_field)
            ->setValue($this->getNewValueForApplicationTransactions());
    }

    /**
     * @return string
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    protected function getBulkEditLabel()
    {
        if ($this->proxy) {
            return $this->proxy->getBulkEditLabel();
        }

        return Yii::t("app", 'Set "{0}" to', [
            $this->getFieldName()
        ]);
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getBulkParameterType()
    {
        return $this->newBulkParameterType();
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function newBulkParameterType()
    {
        if ($this->proxy) {
            return $this->proxy->newBulkParameterType();
        }
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function getHTTPParameterType()
    {
        if ($this->proxy) {
            return $this->proxy->getHTTPParameterType();
        }
        return null;
    }

    /**
     * @task edit
     */
    public function shouldAppearInEditView()
    {
        if ($this->proxy) {
            return $this->proxy->shouldAppearInEditView();
        }
        return false;
    }

    /**
     * @task edit
     */
    public function shouldAppearInEditEngine()
    {
        if ($this->proxy) {
            return $this->proxy->shouldAppearInEditEngine();
        }
        return false;
    }


    /**
     * @task edit
     * @param AphrontRequest $request
     * @return mixed
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function readValueFromRequest(AphrontRequest $request)
    {
        if ($this->proxy) {
            return $this->proxy->readValueFromRequest($request);
        }
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }


    /**
     * @task edit
     */
    public function getRequiredHandlePHIDsForEdit()
    {
        if ($this->proxy) {
            return $this->proxy->getRequiredHandlePHIDsForEdit();
        }
        return array();
    }


    /**
     * @return array
     * @task edit
     */
    public function getInstructionsForEdit()
    {
        if ($this->proxy) {
            return $this->proxy->getInstructionsForEdit();
        }
        return null;
    }


    /**
     * @task edit
     * @param array $handles
     * @return mixed
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function renderEditControl(array $handles)
    {
        if ($this->proxy) {
            return $this->proxy->renderEditControl($handles);
        }
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }


    /* -(  Property View  )------------------------------------------------------ */


    /**
     * @task view
     */
    public function shouldAppearInPropertyView()
    {
        if ($this->proxy) {
            return $this->proxy->shouldAppearInPropertyView();
        }
        return false;
    }


    /**
     * @task view
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function renderPropertyViewLabel()
    {
        if ($this->proxy) {
            return $this->proxy->renderPropertyViewLabel();
        }
        return $this->getFieldName();
    }


    /**
     * @task view
     * @param array $handles
     * @return mixed
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function renderPropertyViewValue(array $handles)
    {
        if ($this->proxy) {
            return $this->proxy->renderPropertyViewValue($handles);
        }
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }


    /**
     * @task view
     */
    public function getStyleForPropertyView()
    {
        if ($this->proxy) {
            return $this->proxy->getStyleForPropertyView();
        }
        return 'property';
    }


    /**
     * @task view
     */
    public function getIconForPropertyView()
    {
        if ($this->proxy) {
            return $this->proxy->getIconForPropertyView();
        }
        return null;
    }


    /**
     * @task view
     */
    public function getRequiredHandlePHIDsForPropertyView()
    {
        if ($this->proxy) {
            return $this->proxy->getRequiredHandlePHIDsForPropertyView();
        }
        return array();
    }


    /* -(  List View  )---------------------------------------------------------- */


    /**
     * @task array
     */
    public function shouldAppearInListView()
    {
        if ($this->proxy) {
            return $this->proxy->shouldAppearInListView();
        }
        return false;
    }


    /**
     * @task array
     * @param PHUIObjectItemView $view
     * @return mixed
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function renderOnListItem(PHUIObjectItemView $view)
    {
        if ($this->proxy) {
            return $this->proxy->renderOnListItem($view);
        }
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }


    /* -(  Global Search  )------------------------------------------------------ */


    /**
     * @task globalsearch
     */
    public function shouldAppearInGlobalSearch()
    {
        if ($this->proxy) {
            return $this->proxy->shouldAppearInGlobalSearch();
        }
        return false;
    }


    /**
     * @task globalsearch
     * @param PhabricatorSearchAbstractDocument $document
     * @return PhabricatorSearchAbstractDocument
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function updateAbstractDocument(
        PhabricatorSearchAbstractDocument $document)
    {
        if ($this->proxy) {
            return $this->proxy->updateAbstractDocument($document);
        }
        return $document;
    }


    /* -(  Data Export  )-------------------------------------------------------- */


    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAppearInDataExport()
    {
        if ($this->proxy) {
            return $this->proxy->shouldAppearInDataExport();
        }

        try {
            $this->newExportFieldType();
            return true;
        } catch (PhabricatorCustomFieldImplementationIncompleteException $ex) {
            return false;
        }
    }

    /**
     * @return mixed
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function newExportField()
    {
        if ($this->proxy) {
            return $this->proxy->newExportField();
        }

        return $this->newExportFieldType()
            ->setLabel($this->getFieldName());
    }

    /**
     * @return mixed
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function newExportData()
    {
        if ($this->proxy) {
            return $this->proxy->newExportData();
        }
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }

    /**
     * @return mixed
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    protected function newExportFieldType()
    {
        if ($this->proxy) {
            return $this->proxy->newExportFieldType();
        }
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }


    /* -(  Conduit  )------------------------------------------------------------ */


    /**
     * @task conduit
     */
    public function shouldAppearInConduitDictionary()
    {
        if ($this->proxy) {
            return $this->proxy->shouldAppearInConduitDictionary();
        }
        return false;
    }


    /**
     * @task conduit
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function getConduitDictionaryValue()
    {
        if ($this->proxy) {
            return $this->proxy->getConduitDictionaryValue();
        }
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAppearInConduitTransactions()
    {
        if ($this->proxy) {
            return $this->proxy->shouldAppearInConduitDictionary();
        }
        return false;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getConduitSearchParameterType()
    {
        return $this->newConduitSearchParameterType();
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function newConduitSearchParameterType()
    {
        if ($this->proxy) {
            return $this->proxy->newConduitSearchParameterType();
        }
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getConduitEditParameterType()
    {
        return $this->newConduitEditParameterType();
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function newConduitEditParameterType()
    {
        if ($this->proxy) {
            return $this->proxy->newConduitEditParameterType();
        }
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getCommentAction()
    {
        return $this->newCommentAction();
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function newCommentAction()
    {
        if ($this->proxy) {
            return $this->proxy->newCommentAction();
        }
        return null;
    }


    /* -(  Herald  )------------------------------------------------------------- */


    /**
     * Return `true` to make this field available in Herald.
     *
     * @return bool True to expose the field in Herald.
     * @task herald
     */
    public function shouldAppearInHerald()
    {
        if ($this->proxy) {
            return $this->proxy->shouldAppearInHerald();
        }
        return false;
    }


    /**
     * Get the name of the field in Herald. By default, this uses the
     * normal field name.
     *
     * @return string Herald field name.
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @task herald
     */
    public function getHeraldFieldName()
    {
        if ($this->proxy) {
            return $this->proxy->getHeraldFieldName();
        }
        return $this->getFieldName();
    }


    /**
     * Get the field value for evaluation by Herald.
     *
     * @return array Field value.
     * @task herald
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function getHeraldFieldValue()
    {
        if ($this->proxy) {
            return $this->proxy->getHeraldFieldValue();
        }
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }


    /**
     * Get the available conditions for this field in Herald.
     *
     * @return array<string> List of Herald condition constants.
     * @task herald
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    public function getHeraldFieldConditions()
    {
        if ($this->proxy) {
            return $this->proxy->getHeraldFieldConditions();
        }
        throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }


    /**
     * Get the Herald value type for the given condition.
     *
     * @param string       Herald condition constant.
     * @return  string|null  Herald value type, or null to use the default.
     * @task herald
     */
    public function getHeraldFieldValueType($condition)
    {
        if ($this->proxy) {
            return $this->proxy->getHeraldFieldValueType($condition);
        }
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getHeraldFieldStandardType()
    {
        if ($this->proxy) {
            return $this->proxy->getHeraldFieldStandardType();
        }
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getHeraldDatasource()
    {
        if ($this->proxy) {
            return $this->proxy->getHeraldDatasource();
        }
        return null;
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAppearInHeraldActions()
    {
        if ($this->proxy) {
            return $this->proxy->shouldAppearInHeraldActions();
        }
        return false;
    }


    /**
     * @return null
     * @author 陈妙威
     */
    public function getHeraldActionName()
    {
        if ($this->proxy) {
            return $this->proxy->getHeraldActionName();
        }

        return null;
    }


    /**
     * @return null
     * @author 陈妙威
     */
    public function getHeraldActionStandardType()
    {
        if ($this->proxy) {
            return $this->proxy->getHeraldActionStandardType();
        }

        return null;
    }


    /**
     * @param $value
     * @return null
     * @author 陈妙威
     */
    public function getHeraldActionDescription($value)
    {
        if ($this->proxy) {
            return $this->proxy->getHeraldActionDescription($value);
        }

        return null;
    }


    /**
     * @param $value
     * @return null
     * @author 陈妙威
     */
    public function getHeraldActionEffectDescription($value)
    {
        if ($this->proxy) {
            return $this->proxy->getHeraldActionEffectDescription($value);
        }

        return null;
    }


    /**
     * @return null
     * @author 陈妙威
     */
    public function getHeraldActionDatasource()
    {
        if ($this->proxy) {
            return $this->proxy->getHeraldActionDatasource();
        }

        return null;
    }

}
