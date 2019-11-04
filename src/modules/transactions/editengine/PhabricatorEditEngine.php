<?php

namespace orangins\modules\transactions\editengine;

use AphrontDuplicateKeyQueryException;
use AphrontObjectMissingQueryException;
use AphrontQueryException;
use orangins\lib\actions\PhabricatorAction;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\OranginsObject;
use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery;
use orangins\lib\editor\PhabricatorEditEngineExtension;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\modules\conduit\protocol\ConduitAPIRequest;
use orangins\modules\draft\models\PhabricatorVersionedDraft;
use orangins\modules\transactions\bulk\PhabricatorBulkEditGroup;
use orangins\modules\transactions\draft\PhabricatorBuiltinDraftEngine;
use orangins\modules\transactions\draft\PhabricatorDraftInterface;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException;
use orangins\modules\transactions\response\PhabricatorApplicationTransactionResponse;
use orangins\lib\PhabricatorApplication;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\Aphront400Response;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\response\AphrontResponse;
use orangins\lib\response\OranginsResponseInterface;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUICrumbsView;
use orangins\lib\view\phui\PHUIDocumentView;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorPHIDConstants;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\search\models\PhabricatorEditEngineConfiguration;
use orangins\modules\search\models\PhabricatorEditEngineConfigurationQuery;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use orangins\modules\transactions\editfield\PhabricatorFileEditField;
use orangins\modules\transactions\edittype\PhabricatorEditType;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionNoEffectException;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\query\PhabricatorEditEngineQuery;
use orangins\modules\transactions\response\PhabricatorApplicationTransactionNoEffectResponse;
use orangins\modules\transactions\response\PhabricatorApplicationTransactionValidationResponse;
use orangins\modules\transactions\response\PhabricatorApplicationTransactionWarningResponse;
use orangins\modules\transactions\view\PhabricatorApplicationEditHTTPParameterHelpView;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionCommentView;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\AphrontFormView;
use PhutilInvalidStateException;
use PhutilJSONParserException;
use PhutilMethodNotImplementedException;
use PhutilSortVector;
use PhutilProxyException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use PhutilTypeSpec;
use PhutilURI;
use PhutilClassMapQuery;
use Exception;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\db\IntegrityException;
use yii\helpers\ArrayHelper;

/**
 * 数据编辑器渲染引擎， 用来渲染Editorx
 * @task fields Managing Fields
 * @task text Display Text
 * @task config Edit Engine Configuration
 * @task uri Managing URIs
 * @task load Creating and Loading Objects
 * @task web Responding to Web Requests
 * @task edit Responding to Edit Requests
 * @task http Responding to HTTP Parameter Requests
 * @task conduit Responding to Conduit Requests
 */
abstract class PhabricatorEditEngine extends OranginsObject
    implements
    PhabricatorPolicyInterface,
    OranginsResponseInterface
{

    /**
     *
     */
    const EDITENGINECONFIG_DEFAULT = 'default';

    /**
     *
     */
    const SUBTYPE_DEFAULT = 'default';

    /**
     * @var PhabricatorUser
     */
    public $viewer;
    /**
     * @var PhabricatorAction
     */
    public $action;

    /**
     * @var
     */
    public $isCreate;
    /**
     * @var
     */
    public $editEngineConfiguration;
    /**
     * @var array
     */
    public $contextParameters = array();
    /**
     * @var
     */
    public $page;
    /**
     * @var
     */
    public $pages;
    /**
     * @var AphrontSideNavFilterView
     */
    public $navigation;

    /**
     * @var AphrontSideNavFilterView
     */
    public $mainNavigation;


    /**
     * @var ActiveRecordPHID
     */
    public $targetObject;

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return PhabricatorUser
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @return PhabricatorAction
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param PhabricatorAction $action
     * @return static
     */
    public function setAction(PhabricatorAction $action)
    {
        $this->action = $action;
        $this->setViewer($action->getViewer());
        return $this;
    }


    /**
     * @return mixed
     * @throws Exception
     * @throws ReflectionException
     * @author 陈妙威
     */
    final public function getEngineKey()
    {
        $key = $this->getPhobjectClassConstant('ENGINECONST', 64);
        if (strpos($key, '/') !== false) {
            throw new Exception(
                Yii::t("app",
                    'EditEngine ("%s") contains an invalid key character "/".',
                    get_class($this)));
        }
        return $key;
    }

    /**
     * @return PhabricatorApplication
     * @throws Exception
     * @author 陈妙威
     */
    final public function getApplication()
    {
        $app_class = $this->getEngineApplicationClass();
        return PhabricatorApplication::getByClass($app_class);
    }

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     */
    final public function addContextParameter($key)
    {
        $this->contextParameters[] = $key;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isEngineConfigurable()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isEngineExtensible()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isDefaultQuickCreateEngine()
    {
        return false;
    }

    /**
     * @return array
     * @throws Exception
     * @throws ReflectionException
     * @author 陈妙威
     */
    public function getDefaultQuickCreateFormKeys()
    {
        $keys = array();

        if ($this->isDefaultQuickCreateEngine()) {
            $keys[] = self::EDITENGINECONFIG_DEFAULT;
        }

        foreach ($keys as $idx => $key) {
            $keys[$idx] = $this->getEngineKey() . '/' . $key;
        }

        return $keys;
    }

    /**
     * @param $full_key
     * @return array
     * @author 陈妙威
     */
    public static function splitFullKey($full_key)
    {
        return explode('/', $full_key, 2);
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getQuickCreateOrderVector()
    {
        return (new PhutilSortVector())
            ->addString($this->getObjectCreateShortText());
    }

    /**
     * Force the engine to edit a particular object.
     * @param $target_object
     * @return PhabricatorEditEngine
     */
    public function setTargetObject($target_object)
    {
        $this->targetObject = $target_object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTargetObject()
    {
        return $this->targetObject;
    }

    /**
     * @param AphrontSideNavFilterView $navigation
     * @return $this
     * @author 陈妙威
     */
    public function setNavigation(AphrontSideNavFilterView $navigation)
    {
        $this->navigation = $navigation;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getNavigation()
    {
        return $this->navigation;
    }

    /**
     * @return AphrontSideNavFilterView
     */
    public function getMainNavigation()
    {
        return $this->mainNavigation;
    }

    /**
     * @param AphrontSideNavFilterView $mainNavigation
     * @return self
     */
    public function setMainNavigation($mainNavigation)
    {
        $this->mainNavigation = $mainNavigation;
        return $this;
    }



    /* -(  Managing Fields  )---------------------------------------------------- */


    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getEngineApplicationClass();

    /**
     * @param array
     * @return PhabricatorEditField[]
     * @author 陈妙威
     */
    abstract protected function buildCustomEditFields($object);

    /**
     * @param PhabricatorEditEngineConfiguration $config
     * @return PhabricatorEditField[]
     * @throws Exception
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    public function getFieldsForConfig(PhabricatorEditEngineConfiguration $config)
    {

        $object = $this->newEditableObject();

        $this->editEngineConfiguration = $config;

        // This is mostly making sure that we fill in default values.
        $this->setIsCreate(true);

        return $this->buildEditFields($object);
    }

    /**
     * @param $object
     * @return PhabricatorEditField[]
     * @throws Exception
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    final protected function buildEditFields($object)
    {
        $viewer = $this->getViewer();

        $fields = $this->buildCustomEditFields($object);
        foreach ($fields as $field) {
            $field
                ->setViewer($viewer)
                ->setObject($object);
        }

        /** @var array $fields */
        $fields = mpull($fields, null, 'getKey');

        if ($this->isEngineExtensible()) {
            $extensions = PhabricatorEditEngineExtension::getAllEnabledExtensions();
        } else {
            $extensions = array();
        }

        foreach ($extensions as $extension) {
            $extension->setViewer($viewer);

            if (!$extension->supportsObject($this, $object)) {
                continue;
            }

            $extension_fields = $extension->buildCustomEditFields($this, $object);

            // TODO: Validate this in more detail with a more tailored error.
            assert_instances_of($extension_fields, PhabricatorEditField::class);

            /** @var PhabricatorEditField $field */
            foreach ($extension_fields as $field) {
                $field
                    ->setViewer($viewer)
                    ->setObject($object);

                $group_key = $field->getBulkEditGroupKey();
                if ($group_key === null) {
                    $field->setBulkEditGroupKey('extension');
                }
            }

            $extension_fields = mpull($extension_fields, null, 'getKey');

            foreach ($extension_fields as $key => $field) {
                $fields[$key] = $field;
            }
        }

        /** @var PhabricatorEditEngineConfiguration $config */
        $config = $this->getEditEngineConfiguration();
        $fields = $this->willConfigureFields($object, $fields);
        $fields = $config->applyConfigurationToFields($this, $object, $fields);
        $fields = $this->applyPageToFields($object, $fields);

        return $fields;
    }

    /**
     * @param $object
     * @param array $fields
     * @return array
     * @author 陈妙威
     */
    protected function willConfigureFields($object, array $fields)
    {
        return $fields;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    final public function supportsSubtypes()
    {
        try {
            $object = $this->newEditableObject();
        } catch (Exception $ex) {
            return false;
        }

        return ($object instanceof PhabricatorEditEngineSubtypeInterface);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function newSubtypeMap()
    {
        return $this->newEditableObject()->newEditEngineSubtypeMap();
    }


    /* -(  Display Text  )------------------------------------------------------- */


    /**
     * @task text
     */
    abstract public function getEngineName();


    /**
     * @task text
     * @param $object
     */
    abstract protected function getObjectCreateTitleText($object);

    /**
     * @task text
     */
    protected function getFormHeaderText($object)
    {
        $config = $this->getEditEngineConfiguration();
        return $config->getName();
    }

    /**
     * @task text
     * @param $object
     */
    abstract protected function getObjectEditTitleText($object);


    /**
     * @task text
     */
    abstract protected function getObjectCreateShortText();


    /**
     * @task text
     */
    abstract protected function getObjectName();


    /**
     * @task text
     * @param $object
     * @return
     */
    abstract protected function getObjectEditShortText($object);


    /**
     * @task text
     * @param $object
     * @return
     */
    protected function getObjectCreateButtonText($object)
    {
        return $this->getObjectCreateTitleText($object);
    }


    /**
     * @task text
     */
    protected function getObjectEditButtonText($object)
    {
        return Yii::t("app", 'Save Changes');
    }


    /**
     * @task text
     */
    protected function getCommentViewSeriousHeaderText($object)
    {
        return Yii::t("app", 'Take Action');
    }


    /**
     * @task text
     */
    protected function getCommentViewSeriousButtonText($object)
    {
        return Yii::t("app", 'Submit');
    }


    /**
     * @task text
     * @param $object
     * @return string
     */
    protected function getCommentViewHeaderText($object)
    {
        return $this->getCommentViewSeriousHeaderText($object);
    }


    /**
     * @task text
     * @param $object
     * @return string
     */
    protected function getCommentViewButtonText($object)
    {
        return $this->getCommentViewSeriousButtonText($object);
    }


    /**
     * @task text
     */
    protected function getPageHeader($object)
    {
        return null;
    }


    /**
     * Return a human-readable header describing what this engine is used to do,
     * like "Configure Maniphest Task Forms".
     *
     * @return string Human-readable description of the engine.
     * @task text
     */
    abstract public function getSummaryHeader();


    /**
     * Return a human-readable summary of what this engine is used to do.
     *
     * @return string Human-readable description of the engine.
     * @task text
     */
    abstract public function getSummaryText();


    /* -(  Edit Engine Configuration  )------------------------------------------ */


    /**
     * @return bool
     * @author 陈妙威
     */
    protected function supportsEditEngineConfiguration()
    {
        return true;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function getEditEngineConfiguration()
    {
        return $this->editEngineConfiguration;
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws ReflectionException
     * @author 陈妙威
     */
    private function newConfigurationQuery()
    {
        return PhabricatorEditEngineConfiguration::find()
            ->setViewer($this->getViewer())
            ->withEngineKeys(array($this->getEngineKey()));
    }

    /**
     * @param PhabricatorEditEngineConfigurationQuery $query
     * @param $sort_method
     * @return null
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function loadEditEngineConfigurationWithQuery(
        PhabricatorEditEngineConfigurationQuery $query,
        $sort_method)
    {

        if ($sort_method) {
            $results = $query->execute();
            $results = msort($results, $sort_method);
            $result = head($results);
        } else {
            $result = $query->executeOne();
        }

        if (!$result) {
            return null;
        }

        $this->editEngineConfiguration = $result;
        return $result;
    }

    /**
     * @param $identifier
     * @return PhabricatorEditEngineConfiguration
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function loadEditEngineConfigurationWithIdentifier($identifier)
    {
        $query = $this->newConfigurationQuery()
            ->withIdentifiers(array($identifier));

        return $this->loadEditEngineConfigurationWithQuery($query, null);
    }

    /**
     * @return PhabricatorEditEngineConfiguration
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function loadDefaultConfiguration()
    {
        $query = $this->newConfigurationQuery()
            ->withIdentifiers(
                array(
                    self::EDITENGINECONFIG_DEFAULT,
                ))
            ->withIgnoreDatabaseConfigurations(true);

        return $this->loadEditEngineConfigurationWithQuery($query, null);
    }

    /**
     * @return PhabricatorEditEngineConfiguration
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function loadDefaultCreateConfiguration()
    {
        $query = $this->newConfigurationQuery()
            ->withIsDefault(true)
            ->withIsDisabled(false);

        return $this->loadEditEngineConfigurationWithQuery($query, 'getCreateSortKey');
    }

    /**
     * 获取配置里面编辑器自定义配置的额外字段
     * @param $object
     * @return PhabricatorEditEngineConfiguration
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function loadDefaultEditConfiguration($object)
    {
        /** @var PhabricatorEditEngineConfigurationQuery $query */
        $query = $this->newConfigurationQuery()
            ->withIsEdit(true)
            ->withIsDisabled(false);

        // If this object supports subtyping, we edit it with a form of the same
        // subtype: so "bug" tasks get edited with "bug" forms.
        if ($object instanceof PhabricatorEditEngineSubtypeInterface) {
            $query->withSubtypes(
                array(
                    $object->getEditEngineSubtype(),
                ));
        }

        return $this->loadEditEngineConfigurationWithQuery(
            $query,
            'getEditSortKey');
    }

    /**
     * @return PhabricatorEditEngineConfiguration[]
     * @throws Exception
     * @throws ReflectionException
     * @author 陈妙威
     */
    final public function getBuiltinEngineConfigurations()
    {
        $configurations = $this->newBuiltinEngineConfigurations();
        if (!$configurations) {
            throw new Exception(
                Yii::t("app",
                    'EditEngine ("{0}") returned no builtin engine configurations, but ' .
                    'an edit engine must have at least one configuration.', [
                        get_class($this)
                    ]));
        }

        assert_instances_of($configurations, PhabricatorEditEngineConfiguration::class);

        $has_default = false;
        foreach ($configurations as $config) {
            if ($config->builtin_key == self::EDITENGINECONFIG_DEFAULT) {
                $has_default = true;
            }
        }

        if (!$has_default) {
            /** @var PhabricatorEditEngineConfiguration $first */
            $first = head($configurations);
            if (!$first->builtin_key) {
                $first->builtin_key = self::EDITENGINECONFIG_DEFAULT;
                $first->is_default = true;
                $first->is_edit = true;

                if (!strlen($first->name)) {
                    $first->name = $this->getObjectCreateShortText();
                }
            } else {
                throw new Exception(
                    Yii::t("app",
                        'EditEngine ("{0}") returned builtin engine configurations, ' .
                        'but none are marked as default and the first configuration has ' .
                        'a different builtin key already. Mark a builtin as default or ' .
                        'omit the key from the first configuration', [
                            get_class($this)
                        ]));
            }
        }

        $builtins = array();
        foreach ($configurations as $key => $config) {
            $builtin_key = $config->builtin_key;

            if ($builtin_key === null) {
                throw new Exception(
                    Yii::t("app",
                        'EditEngine ("{0}") returned builtin engine configurations, ' .
                        'but one (with key "{1}") is missing a builtin key. Provide a ' .
                        'builtin key for each configuration (you can omit it from the ' .
                        'first configuration in the list to automatically assign the ' .
                        'default key).', [
                            get_class($this),
                            $key
                        ]));
            }

            if (isset($builtins[$builtin_key])) {
                throw new Exception(
                    Yii::t("app",
                        'EditEngine ("{0}") returned builtin engine configurations, ' .
                        'but at least two specify the same builtin key ("{1}"). Engines ' .
                        'must have unique builtin keys.', [
                            get_class($this),
                            $builtin_key
                        ]));
            }

            $builtins[$builtin_key] = $config;
        }


        return $builtins;
    }

    /**
     * @return PhabricatorEditEngineConfiguration[]
     * @throws Exception
     * @throws ReflectionException
     * @author 陈妙威
     */
    protected function newBuiltinEngineConfigurations()
    {
        return array(
            $this->newConfiguration(),
        );
    }

    /**
     * @return PhabricatorEditEngineConfiguration
     * @throws Exception
     * @throws ReflectionException
     * @author 陈妙威
     */
    final protected function newConfiguration()
    {
        return PhabricatorEditEngineConfiguration::initializeNewConfiguration(
            $this->getViewer(),
            $this);
    }


    /* -(  Managing URIs  )------------------------------------------------------ */


    /**
     * @task uri
     * @param $object
     * @return
     */
    abstract protected function getObjectViewURI($object);


    /**
     * @task uri
     * @param $object
     * @return string
     * @throws PhutilMethodNotImplementedException
     * @throws Exception
     */
    protected function getObjectCreateCancelURI($object)
    {
        return $this->getApplication()->getApplicationURI();
    }


    /**
     * @task uri
     * @throws Exception
     */
    protected function getEditorURI()
    {
        return $this->getApplication()->getApplicationURI('index/edit');
    }


    /**
     * @task uri
     * @param $object
     * @return
     */
    protected function getObjectEditCancelURI($object)
    {
        return $this->getObjectViewURI($object);
    }


    /**
     * @task uri
     * @param $form_key
     * @return null|string
     */
    public function getCreateURI($form_key)
    {
        try {
            $create_uri = $this->getEditURI(null, ['formKey' => $form_key]);
//            $create_uri = $this->getEditURI(null, "form/{$form_key}/");
        } catch (Exception $ex) {
            $create_uri = null;
        }

        return $create_uri;
    }

    /**
     * @task uri
     * @param ActiveRecord $object
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function getEditURI($object = null, $params = [])
    {
        $phutilURI = new PhutilURI($this->getEditorURI());
        if ($object && $object->getID()) {
            $phutilURI->appendQueryParam("id", $object->getID());
        }


        foreach ($params as $k => $param) {
            $phutilURI->appendQueryParam($k, $param);
        }

        return (string)$phutilURI;
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function getEffectiveObjectViewURI($object)
    {
        if ($this->getIsCreate()) {
            return $this->getObjectViewURI($object);
        }

        $page = $this->getSelectedPage();
        if ($page) {
            $view_uri = $page->getViewURI();
            if ($view_uri !== null) {
                return $view_uri;
            }
        }

        return $this->getObjectViewURI($object);
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function getEffectiveObjectEditDoneURI($object)
    {
        return $this->getEffectiveObjectViewURI($object);
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function getEffectiveObjectEditCancelURI($object)
    {
        $page = $this->getSelectedPage();
        if ($page) {
            $view_uri = $page->getViewURI();
            if ($view_uri !== null) {
                return $view_uri;
            }
        }
        return $this->getObjectEditCancelURI($object);
    }


    /* -(  Creating and Loading Objects  )--------------------------------------- */


    /**
     * Initialize a new object for creation.
     *
     * @return PhabricatorEditEngineSubtypeInterface|PhabricatorApplicationTransactionInterface Newly initialized object.
     * @task load
     */
    abstract protected function newEditableObject();


    /**
     * Build an empty query for objects.
     *
     * @return PhabricatorPolicyAwareQuery Query.
     * @task load
     */
    abstract protected function newObjectQuery();


    /**
     * Test if this workflow is creating a new object or editing an existing one.
     *
     * @return bool True if a new object is being created.
     * @task load
     */
    final public function getIsCreate()
    {
        return $this->isCreate;
    }

    /**
     * Initialize a new object for object creation via Conduit.
     *
     * @param array<wild> Raw transactions.
     * @return PhabricatorEditEngineSubtypeInterface Newly initialized object.
     * @task load
     */
    protected function newEditableObjectFromConduit(array $raw_xactions)
    {
        return $this->newEditableObject();
    }

    /**
     * Initialize a new object for documentation creation.
     *
     * @return PhabricatorEditEngineSubtypeInterface Newly initialized object.
     * @task load
     */
    protected function newEditableObjectForDocumentation()
    {
        return $this->newEditableObject();
    }

    /**
     * Flag this workflow as a create or edit.
     *
     * @param bool True if this is a create workflow.
     * @return PhabricatorEditEngine
     * @task load
     */
    private function setIsCreate($is_create)
    {
        $this->isCreate = $is_create;
        return $this;
    }


    /**
     * Try to load an object by ID, PHID, or monogram. This is done primarily
     * to make Conduit a little easier to use.
     *
     * @param array ID, PHID, or monogram.
     * @param array $capabilities List of required capability constants, or omit for
     *   defaults.
     * @return object Corresponding editable object.
     * @task load
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     */
    private function newObjectFromIdentifier(
        $identifier,
        array $capabilities = array())
    {
        if (is_int($identifier) || ctype_digit($identifier)) {
            $object = $this->newObjectFromID($identifier, $capabilities);

            if (!$object) {
                throw new Exception(
                    Yii::t("app",
                        'No object exists with ID "%s".',
                        $identifier));
            }

            return $object;
        }

        $type_unknown = PhabricatorPHIDConstants::PHID_TYPE_UNKNOWN;
        if (PhabricatorPHID::phid_get_type($identifier) != $type_unknown) {
            $object = $this->newObjectFromPHID($identifier, $capabilities);

            if (!$object) {
                throw new Exception(
                    Yii::t("app",
                        'No object exists with PHID "%s".',
                        $identifier));
            }

            return $object;
        }

        $target = (new PhabricatorObjectQuery())
            ->setViewer($this->getViewer())
            ->withNames(array($identifier))
            ->executeOne();
        if (!$target) {
            throw new Exception(
                Yii::t("app",
                    'Monogram "{0}" does not identify a valid object.', [
                        $identifier
                    ]));
        }

        $expect = $this->newEditableObject();
        $expect_class = get_class($expect);
        $target_class = get_class($target);
        if ($expect_class !== $target_class) {
            throw new Exception(
                Yii::t("app",
                    'Monogram "%s" identifies an object of the wrong type. Loaded ' .
                    'object has class "%s", but this editor operates on objects of ' .
                    'type "%s".',
                    $identifier,
                    $target_class,
                    $expect_class));
        }

        // Load the object by PHID using this engine's standard query. This makes
        // sure it's really valid, goes through standard policy check logic, and
        // picks up any `need...()` clauses we want it to load with.

        $object = $this->newObjectFromPHID($target->getPHID(), $capabilities);
        if (!$object) {
            throw new Exception(
                Yii::t("app",
                    'Failed to reload object identified by monogram "{0}" when ' .
                    'querying by PHID.',
                    $identifier));
        }

        return $object;
    }

    /**
     * Load an object by ID.
     *
     * @param int Object ID.
     * @param array $capabilities
     * @return object|null Object, or null if no such object exists.
     * @throws Exception
     * @task load
     */
    private function newObjectFromID($id, array $capabilities = array())
    {
        $query = $this->newObjectQuery();
        $query = $query
            ->withIDs(array($id));

        return $this->newObjectFromQuery($query, $capabilities);
    }


    /**
     * Load an object by PHID.
     *
     * @param string Object PHID.
     * @param array $capabilities
     * @return object|null Object, or null if no such object exists.
     * @throws Exception
     * @task load
     */
    private function newObjectFromPHID($phid, array $capabilities = array())
    {
        $query = $this->newObjectQuery()
            ->withPHIDs(array($phid));

        return $this->newObjectFromQuery($query, $capabilities);
    }


    /**
     * Load an object given a configured query.
     *
     * @param PhabricatorPolicyAwareQuery $query
     * @param array $capabilities
     * @return object|null Object, or null if no such object exists.
     * @throws Exception
     * @task load
     */
    private function newObjectFromQuery(
        PhabricatorPolicyAwareQuery $query,
        array $capabilities = array())
    {

        $viewer = $this->getViewer();

        if (!$capabilities) {
            $capabilities = array(
                PhabricatorPolicyCapability::CAN_VIEW,
                PhabricatorPolicyCapability::CAN_EDIT,
            );
        }

        $object = $query
            ->setViewer($viewer)
            ->requireCapabilities($capabilities)
            ->executeOne();
        if (!$object) {
            return null;
        }

        return $object;
    }


    /**
     * Verify that an object is appropriate for editing.
     *
     * @param array Loaded value.
     * @return void
     * @task load
     * @throws Exception
     */
    private function validateObject($object)
    {
        if (!$object || !is_object($object)) {
            throw new Exception(
                Yii::t("app",
                    'EditEngine "{0}" created or loaded an invalid object: object must ' .
                    'actually be an object, but is of some other type ("{0}").',
                    get_class($this),
                    gettype($object)));
        }

        if (!($object instanceof PhabricatorApplicationTransactionInterface)) {
            throw new Exception(
                Yii::t("app",
                    'EditEngine "{0}" created or loaded an invalid object: object (of ' .
                    'class "{1}") must implement "{2}", but does not.',
                    [
                        get_class($this),
                        get_class($object),
                        'PhabricatorApplicationTransactionInterface'
                    ]));
        }
    }


    /* -(  Responding to Web Requests  )----------------------------------------- */


    /**
     * @return mixed
     * @throws AphrontDuplicateKeyQueryException
     * @throws AphrontObjectMissingQueryException
     * @throws AphrontQueryException
     * @throws IntegrityException
     * @throws InvalidConfigException
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws Throwable
     * @throws UnknownPropertyException
     * @author 陈妙威
     */
    final public function buildResponse()
    {
        $action = $this->getEditAction();
        $capabilities = array();
        $use_default = false;
        $require_create = true;
        switch ($action) {
            case 'comment':
                $capabilities = array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                );
                $use_default = true;
                break;
            case 'parameters':
                $use_default = true;
                break;
            case 'nodefault':
            case 'nocreate':
            case 'nomanage':
                $require_create = false;
                break;
            default:
                break;
        }

        $object = $this->getTargetObject();
        if (!$object) {
            $id = $this->getAction()->getRequest()->getURIData('id');

            if ($id) {
                $this->setIsCreate(false);
                $object = $this->newObjectFromID($id, $capabilities);
                if (!$object) {
                    return new Aphront404Response();
                }
            } else {
                // Make sure the viewer has permission to create new objects of
                // this type if we're going to create a new object.
                if ($require_create) {
                    $this->requireCreateCapability();
                }

                $this->setIsCreate(true);
                $object = $this->newEditableObject();
            }
        } else {
            $id = $object->getID();
        }

        $this->validateObject($object);

        if ($use_default) {
            $config = $this->loadDefaultConfiguration();
            if (!$config) {
                return new Aphront404Response();
            }
        } else {
            $form_key = $this->getAction()->getRequest()->getURIData('formKey');
            if (strlen($form_key)) {
                $config = $this->loadEditEngineConfigurationWithIdentifier($form_key);

                if (!$config) {
                    return new Aphront404Response();
                }

                if ($id && !$config->is_edit) {
                    return $this->buildNotEditFormRespose($object, $config);
                }
            } else {
                if ($id) {
                    $config = $this->loadDefaultEditConfiguration($object);
                    if (!$config) {
                        return $this->buildNoEditResponse($object);
                    }
                } else {
                    $config = $this->loadDefaultCreateConfiguration();
                    if (!$config) {
                        return $this->buildNoCreateResponse($object);
                    }
                }
            }
        }

        if ($config->is_disabled) {
            return $this->buildDisabledFormResponse($object, $config);
        }

        $page_key = $this->getAction()->getRequest()->getURIData('pageKey');
        if (!strlen($page_key)) {
            $pages = $this->getPages($object);
            if ($pages) {
                $page_key = head_key($pages);
            }
        }

        if (strlen($page_key)) {
            $page = $this->selectPage($object, $page_key);
            if (!$page) {
                return new Aphront404Response();
            }
        }

        switch ($action) {
            case 'parameters':
                return $this->buildParametersResponse($object);
            case 'nodefault':
                return $this->buildNoDefaultResponse($object);
            case 'nocreate':
                return $this->buildNoCreateResponse($object);
            case 'nomanage':
                return $this->buildNoManageResponse($object);
            case 'comment':
                return $this->buildCommentResponse($object);
            default:
                return $this->buildEditResponse($object);
        }
    }

    /**
     * @param $object
     * @param bool $final
     * @return PHUICrumbsView
     * @throws Exception
     * @author 陈妙威
     */
    private function buildCrumbs($object, $final = false)
    {
        $action = $this->getAction();

        $crumbs = $action->buildApplicationCrumbsForEditEngine();
        if ($this->getIsCreate()) {
            $create_text = $this->getObjectCreateShortText();
            if ($final) {
                $crumbs->addTextCrumb($create_text);
            } else {
                $edit_uri = $this->getEditURI($object);
                $crumbs->addTextCrumb($create_text, $edit_uri);
            }
        } else {
            $crumbs->addTextCrumb(
                $this->getObjectEditShortText($object),
                $this->getEffectiveObjectViewURI($object));

            $edit_text = Yii::t("app", 'Edit');
            if ($final) {
                $crumbs->addTextCrumb($edit_text);
            } else {
                $edit_uri = $this->getEditURI($object);
                $crumbs->addTextCrumb($edit_text, $edit_uri);
            }
        }

        return $crumbs;
    }

    /**
     * @param PhabricatorApplicationTransactionInterface|ActiveRecordPHID $object
     * @return mixed
     * @throws InvalidConfigException
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws AphrontObjectMissingQueryException
     * @throws AphrontQueryException
     * @throws Throwable
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws IntegrityException
     * @author 陈妙威
     */
    private function buildEditResponse($object)
    {
        $viewer = $this->getViewer();
        $action = $this->getAction();
        $request = $action->getRequest();

        $fields = $this->buildEditFields($object);
        $template = $object->getApplicationTransactionTemplate();

        if ($this->getIsCreate()) {
            $cancel_uri = $this->getObjectCreateCancelURI($object);
            $submit_button = $this->getObjectCreateButtonText($object);
        } else {
            $cancel_uri = $this->getEffectiveObjectEditCancelURI($object);
            $submit_button = $this->getObjectEditButtonText($object);
        }

        $config = $this->getEditEngineConfiguration()
            ->attachEngine($this);

        // NOTE: Don't prompt users to override locks when creating objects,
        // even if the default settings would create a locked object.

        $can_interact = PhabricatorPolicyFilter::canInteract($viewer, $object);
        if (!$can_interact &&
            !$this->getIsCreate() &&
            !$request->getBool('editEngine') &&
            !$request->getBool('overrideLock')) {

            $lock = PhabricatorEditEngineLock::newForObject($viewer, $object);

            $dialog = $this->getAction()
                ->newDialog()
                ->addHiddenInput('overrideLock', true)
                ->setDisableWorkflowOnSubmit(true)
                ->addCancelButton($cancel_uri);

            return $lock->willPromptUserForLockOverrideWithDialog($dialog);
        }

        $validation_exception = null;
        if ($request->isFormPost() && $request->getBool('editEngine')) {
            $submit_fields = $fields;

            foreach ($submit_fields as $key => $field) {
                if (!$field->shouldGenerateTransactionsFromSubmit()) {
                    unset($submit_fields[$key]);
                    continue;
                }
            }

            // Before we read the submitted values, store a copy of what we would
            // use if the form was empty so we can figure out which transactions are
            // just setting things to their default values for the current form.
            $defaults = array();
            foreach ($submit_fields as $key => $field) {
                $defaults[$key] = $field->getValueForTransaction();
            }

            foreach ($submit_fields as $key => $field) {
                $field->setIsSubmittedForm(true);

                if (!$field->shouldReadValueFromSubmit()) {
                    continue;
                }

                $field->readValueFromSubmit($request);
            }

            $xactions = array();

            if ($this->getIsCreate()) {
                $phabricatorApplicationTransaction = clone $template;
                $xactions[] = $phabricatorApplicationTransaction
                    ->setTransactionType(PhabricatorTransactions::TYPE_CREATE);

                if ($this->supportsSubtypes()) {
                    $phabricatorApplicationTransaction1 = clone $template;
                    $xactions[] = $phabricatorApplicationTransaction1
                        ->setTransactionType(PhabricatorTransactions::TYPE_SUBTYPE)
                        ->setNewValue($config->getSubtype());
                }
            }

            foreach ($submit_fields as $key => $field) {
                $field_value = $field->getValueForTransaction();

                $type_xactions = $field->generateTransactions(
                    clone $template,
                    array(
                        'value' => $field_value,
                    ));

                foreach ($type_xactions as $type_xaction) {
                    $default = $defaults[$key];

                    if ($default === $field->getValueForTransaction()) {
                        $type_xaction->setIsDefaultTransaction(true);
                    }

                    $xactions[] = $type_xaction;
                }
            }

            $editor = $object->getApplicationTransactionEditor()
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->setContinueOnNoEffect(true);

            try {
                $xactions = $this->willApplyTransactions($object, $xactions);

                $editor->applyTransactions($object, $xactions);

                $this->didApplyTransactions($object, $xactions);

                return $this->newEditResponse($request, $object, $xactions);
            } catch (PhabricatorApplicationTransactionValidationException $ex) {
                $validation_exception = $ex;

                foreach ($fields as $field) {
                    $message = $this->getValidationExceptionShortMessage($ex, $field);
                    if ($message === null) {
                        continue;
                    }

                    $field->setControlError($message);
                }
            }
        } else {
            if ($this->getIsCreate()) {
                $template = $request->getStr('template');

                if (strlen($template)) {
                    $template_object = $this->newObjectFromIdentifier(
                        $template,
                        array(
                            PhabricatorPolicyCapability::CAN_VIEW,
                        ));
                    if (!$template_object) {
                        return new Aphront404Response();
                    }
                } else {
                    $template_object = null;
                }

                if ($template_object) {
                    $copy_fields = $this->buildEditFields($template_object);
                    /** @var PhabricatorEditField[] $copy_fields */
                    $copy_fields = mpull($copy_fields, null, 'getKey');
                    foreach ($copy_fields as $copy_key => $copy_field) {
                        if (!$copy_field->getIsCopyable()) {
                            unset($copy_fields[$copy_key]);
                        }
                    }
                } else {
                    $copy_fields = array();
                }

                foreach ($fields as $field) {
                    if (!$field->shouldReadValueFromRequest()) {
                        continue;
                    }

                    $field_key = $field->getKey();
                    if (isset($copy_fields[$field_key])) {
                        $field->readValueFromField($copy_fields[$field_key]);
                    }

                    $field->readValueFromRequest($request);
                }
            }
        }

        $action_button = $this->buildEditFormActionButton($object);

        if ($this->getIsCreate()) {
            $header_text = $this->getFormHeaderText($object);
        } else {
            $header_text = $this->getObjectEditTitleText($object);
        }

        $show_preview = !$request->isAjax();

        if ($show_preview) {
            $previews = array();
            foreach ($fields as $field) {
                $preview = $field->getPreviewPanel();
                if (!$preview) {
                    continue;
                }

                $control_id = $field->getControlID();

                $preview
                    ->setControlID($control_id)
                    ->setPreviewURI('/transactions/remarkuppreview/');

                $previews[] = $preview;
            }
        } else {
            $previews = array();
        }

        $form = $this->buildEditForm($object, $fields);

        $crumbs = $this->buildCrumbs($object, $final = true);
        $crumbs->setBorder(true);

        if ($request->isAjax()) {
            return $this->getAction()
                ->newDialog()
                ->addClass("wmin-600")
                ->setWidth(AphrontDialogView::WIDTH_FULL)
                ->setTitle($header_text)
                ->setValidationException($validation_exception)
                ->appendForm($form)
                ->addCancelButton($cancel_uri)
                ->addSubmitButton($submit_button);
        }

        $box_header = (new PHUIPageHeaderView())
            ->setHeader($header_text);

        if ($action_button) {
            $box_header->addActionLink($action_button);
        }

        $box = (new PHUIObjectBoxView())
            ->setViewer($viewer)
            ->setValidationException($validation_exception)
            ->setBackground(PHUIObjectBoxView::WHITE_CONFIG)
            ->appendChild($form);

        // This is fairly questionable, but in use by Settings.
        if ($request->getURIData('formSaved')) {
            $box->setFormSaved(true);
        }

        $content = array(
            $box,
            $previews,
        );

        $view = new PHUITwoColumnView();

        $page_header = $this->getPageHeader($object);
        if ($page_header) {
            $view->setHeader($page_header);
        }

        $page = $action->newPage()
            ->setHeader($box_header)
            ->setTitle($header_text)
            ->setCrumbs($crumbs)
            ->appendChild($view);

        $navigation = $this->getNavigation();
        if ($navigation) {
            $view->setFixed(true);
            $view->setNavigation($navigation);
            $view->setMainColumn($content);
        } else {
            $view->setFooter($content);
        }

        $mainNavigation = $this->getMainNavigation();
        if ($mainNavigation) {
            $page->setNavigation($mainNavigation);
        }
        return $page;
    }

    /**
     * @param AphrontRequest $request
     * @param $object
     * @param array $xactions
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    protected function newEditResponse(AphrontRequest $request, $object, $xactions)
    {
        $uri = $this->getEffectiveObjectEditDoneURI($object);
        return (new AphrontRedirectResponse())->setURI($uri);
    }

    /**
     * @param ActiveRecord $object
     * @param PhabricatorEditField[] $fields
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function buildEditForm($object, $fields)
    {
        $viewer = $this->getViewer();
        $controller = $this->getAction();
        $request = $controller->getRequest();

        $fields = $this->willBuildEditForm($object, $fields);

        $form = (new AphrontFormView())
            ->setViewer($viewer)
            ->addHiddenInput('editEngine', 'true');

        foreach ($this->contextParameters as $param) {
            $form->addHiddenInput($param, $request->getStr($param));
        }

        foreach ($fields as $field) {
            if (!$field->getIsFormField()) {
                continue;
            }
            $field->appendToForm($form);
        }

        if ($this->getIsCreate()) {
            $cancel_uri = $this->getObjectCreateCancelURI($object);
            $submit_button = $this->getObjectCreateButtonText($object);
        } else {
            $cancel_uri = $this->getEffectiveObjectEditCancelURI($object);
            $submit_button = $this->getObjectEditButtonText($object);
        }

        if (!$request->isAjax()) {
            $buttons = (new AphrontFormSubmitControl())
                ->setValue($submit_button);

            if ($cancel_uri) {
                $buttons->addCancelButton($cancel_uri);
            }

            $form->appendChild($buttons);
        }
        return $form;
    }

    /**
     * @param ActiveRecord $editor
     * @param PhabricatorFileEditField[] $fields
     * @return PhabricatorFileEditField[]
     * @author 陈妙威
     */
    protected function willBuildEditForm($editor, array $fields)
    {
        return $fields;
    }

    /**
     * @param $object
     * @return null
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function buildEditFormActionButton($object)
    {
        if (!$this->isEngineConfigurable()) {
            return null;
        }

        $viewer = $this->getViewer();

        $action_view = (new PhabricatorActionListView())
            ->setUser($viewer);

        foreach ($this->buildEditFormActions($object) as $action) {
            $action_view->addAction($action);
        }

        $action_button = (new PHUIButtonView())
            ->setTag('a')
            ->setText(Yii::t("app", 'Configure Form'))
            ->setHref('#')
            ->setIcon('fa-gear')
            ->setDropdownMenu($action_view);

        return $action_button;
    }

    /**
     * @param $object
     * @return array
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @throws Exception
     * @author 陈妙威
     */
    private function buildEditFormActions($object)
    {
        $actions = array();

        if ($this->supportsEditEngineConfiguration()) {
            $engine_key = $this->getEngineKey();
            $config = $this->getEditEngineConfiguration();

            $can_manage = PhabricatorPolicyFilter::hasCapability(
                $this->getViewer(),
                $config,
                PhabricatorPolicyCapability::CAN_EDIT);

            if ($can_manage) {
                $manage_uri = $config->getURI();
            } else {
                $manage_uri = $this->getEditURI(null, ['editAction' => 'nomanage']);
            }

            $view_uri = "/transactions/editengine/{$engine_key}/";

            $actions[] = (new PhabricatorActionView())
                ->setLabel(true)
                ->setName(Yii::t("app", 'Configuration'));

            $actions[] = (new PhabricatorActionView())
                ->setName(Yii::t("app", 'View Form Configurations'))
                ->setIcon('fa-list-ul')
                ->setHref($view_uri);

            $actions[] = (new PhabricatorActionView())
                ->setName(Yii::t("app", 'Edit Form Configuration'))
                ->setIcon('fa-pencil')
                ->setHref($manage_uri)
                ->setDisabled(!$can_manage)
                ->setWorkflow(!$can_manage);
        }

        $actions[] = (new PhabricatorActionView())
            ->setLabel(true)
            ->setName(Yii::t("app", 'Documentation'));

        $actions[] = (new PhabricatorActionView())
            ->setName(Yii::t("app", 'Using HTTP Parameters'))
            ->setIcon('fa-book')
            ->setHref($this->getEditURI($object, ['editAction' => 'parameters']));

        $doc_href = PhabricatorEnv::getDoclink('User Guide: Customizing Forms');
        $actions[] = (new PhabricatorActionView())
            ->setName(Yii::t("app", 'User Guide: Customizing Forms'))
            ->setIcon('fa-book')
            ->setHref($doc_href);

        return $actions;
    }


    /**
     * @param $text
     * @return mixed
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function newNUXButton($text)
    {
        $specs = $this->newCreateActionSpecifications(array());
        $head = head($specs);

        return (new PHUIButtonView())
            ->setTag('a')
            ->setText($text)
            ->setHref($head['uri'])
            ->setDisabled($head['disabled'])
            ->setWorkflow($head['workflow'])
            ->setColor(PHUIButtonView::COLOR_SUCCESS);
    }


    /**
     * @param PHUICrumbsView $crumbs
     * @param array $parameters
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    final public function addActionToCrumbs(PHUICrumbsView $crumbs, array $parameters = array())
    {
        $viewer = $this->getViewer();

        $specs = $this->newCreateActionSpecifications($parameters);

        $head = head($specs);
        $menu_uri = $head['uri'];

        $dropdown = null;
        if (count($specs) > 1) {
            $menu_icon = 'fa-caret-square-o-down';
            $menu_name = $this->getObjectCreateShortText();
            $workflow = false;
            $disabled = false;

            $dropdown = (new PhabricatorActionListView())
                ->setUser($viewer);

            foreach ($specs as $spec) {
                $dropdown->addAction(
                    (new PhabricatorActionView())
                        ->setName($spec['name'])
                        ->setIcon($spec['icon'])
                        ->setHref($spec['uri'])
                        ->setDisabled($head['disabled'])
                        ->setWorkflow($head['workflow']));
            }

        } else {
            $menu_icon = $head['icon'];
            $menu_name = $head['name'];

            $workflow = $head['workflow'];
            $disabled = $head['disabled'];
        }

        $action = (new PHUIListItemView())
            ->setName($menu_name)
            ->setHref($menu_uri)
            ->setIcon($menu_icon)
            ->setWorkflow($workflow)
            ->setDisabled($disabled);

        if ($dropdown) {
            $action->setDropdownMenu($dropdown);
        }

        $crumbs->addAction($action);
    }


    /**
     * Build a raw description of available "Create New Object" UI options so
     * other methods can build menus or buttons.
     * @param array $parameters
     * @return array
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @throws Exception
     */
    public function newCreateActionSpecifications(array $parameters)
    {
        $viewer = $this->getViewer();

        $can_create = $this->hasCreateCapability();

        if ($can_create) {
            $configs = $this->loadUsableConfigurationsForCreate();
        } else {
            $configs = array();
        }

        $disabled = false;
        $workflow = false;

        $menu_icon = 'fa-plus-square';
        $specs = array();
        if (!$configs) {
            if ($viewer->isLoggedIn()) {
                $disabled = true;
            } else {
                // If the viewer isn't logged in, assume they'll get hit with a login
                // dialog and are likely able to create objects after they log in.
                $disabled = false;
            }
            $workflow = true;

            if ($can_create) {
                $create_uri = $this->getEditURI(null, ['editAction' => 'nodefault']);
            } else {
                $create_uri = $this->getEditURI(null, ['editAction' => 'nocreate']);
            }

            $specs[] = array(
                'name' => $this->getObjectCreateShortText(),
                'uri' => $create_uri,
                'icon' => $menu_icon,
                'disabled' => $disabled,
                'workflow' => $workflow,
            );
        } else {
            /** @var PhabricatorEditEngineConfiguration[] $configs */
            foreach ($configs as $config) {
                $config_uri = $config->getCreateURI();

                if ($parameters) {
                    $config_uri = (string)(new PhutilURI($config_uri))
                        ->setQueryParams($parameters);
                }

                $specs[] = array(
                    'name' => $config->getDisplayName(),
                    'uri' => $config_uri,
                    'icon' => 'fa-plus',
                    'disabled' => false,
                    'workflow' => false,
                );
            }
        }

        return $specs;
    }

    /**
     * @param ActiveRecordPHID $object
     * @return mixed
     * @throws Exception
     * @throws ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    final public function buildEditEngineCommentView($object)
    {
        $config = $this->loadDefaultEditConfiguration($object);

        if (!$config) {
            // TODO: This just nukes the entire comment form if you don't have access
            // to any edit forms. We might want to tailor this UX a bit.
            return (new PhabricatorApplicationTransactionCommentView())
                ->setNoPermission(true);
        }

        $viewer = $this->getViewer();

        $can_interact = PhabricatorPolicyFilter::canInteract($viewer, $object);
        if (!$can_interact) {
            $lock = PhabricatorEditEngineLock::newForObject($viewer, $object);

            return (new PhabricatorApplicationTransactionCommentView())
                ->setEditEngineLock($lock);
        }

        $object_phid = $object->getPHID();
        $is_serious = PhabricatorEnv::getEnvConfig('orangins.serious-business');

        if ($is_serious) {
            $header_text = $this->getCommentViewSeriousHeaderText($object);
            $button_text = $this->getCommentViewSeriousButtonText($object);
        } else {
            $header_text = $this->getCommentViewHeaderText($object);
            $button_text = $this->getCommentViewButtonText($object);
        }

        $comment_uri = $this->getEditURI($object, ['editAction' => 'comment']);

        $view = (new PhabricatorApplicationTransactionCommentView())
            ->setViewer($viewer)
            ->setObjectPHID($object_phid)
            ->setHeaderText($header_text)
            ->setAction($comment_uri)
            ->setSubmitButtonName($button_text);

        $draft = PhabricatorVersionedDraft::loadDraft(
            $object_phid,
            $viewer->getPHID());
        if ($draft) {
            $view->setVersionedDraft($draft);
        }
        $view->setCurrentVersion($this->loadDraftVersion($object));

        $fields = $this->buildEditFields($object);

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $object,
            PhabricatorPolicyCapability::CAN_EDIT);

        $comment_actions = array();
        foreach ($fields as $field) {
            if (!$field->shouldGenerateTransactionsFromComment()) {
                continue;
            }

            if (!$can_edit) {
                if (!$field->getCanApplyWithoutEditCapability()) {
                    continue;
                }
            }

            $comment_action = $field->getCommentAction();
            if (!$comment_action) {
                continue;
            }

            $key = $comment_action->getKey();

            // TODO: Validate these better.

            $comment_actions[$key] = $comment_action;
        }

        $comment_actions = msortv($comment_actions, 'getSortVector');

        $view->setCommentActions($comment_actions);

        $comment_groups = $this->newCommentActionGroups();
        $view->setCommentActionGroups($comment_groups);

        return $view;
    }

    /**
     * @param PhabricatorApplicationTransactionInterface|ActiveRecordPHID $object
     * @return int|null
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    protected function loadDraftVersion($object)
    {
        $viewer = $this->getViewer();

        if (!$viewer->isLoggedIn()) {
            return null;
        }

        $template = $object->getApplicationTransactionTemplate();

        // Find the most recent transaction the user has written. We'll use this
        // as a version number to make sure that out-of-date drafts get discarded.
        $result = $template::find()->select(['id'])->andWhere(['object_phid' => $object->getPHID(), 'author_phid' => $viewer->getPHID()])->one();
        if ($result) {
            return (int)$result['id'];
        } else {
            return null;
        }
    }


    /* -(  Responding to HTTP Parameter Requests  )------------------------------ */


    /**
     * Respond to a request for documentation on HTTP parameters.
     *
     * @param object Editable object.
     * @return PhabricatorStandardPageView Response object.
     * @throws Exception
     * @throws InvalidConfigException
     * @task http
     */
    private function buildParametersResponse($object)
    {
        $controller = $this->getAction();
        $viewer = $this->getViewer();

        $fields = $this->buildEditFields($object);

        $crumbs = $this->buildCrumbs($object);
        $crumbs->addTextCrumb(Yii::t("app", 'HTTP Parameters'));
        $crumbs->setBorder(true);

        $header_text = Yii::t("app", 'HTTP Parameters: {0}', [
            $this->getObjectCreateShortText()
        ]);

        $header = (new PHUIPageHeaderView())
            ->setHeader($header_text);

        $help_view = (new PhabricatorApplicationEditHTTPParameterHelpView())
            ->setUser($viewer)
            ->setFields($fields);

        $document = (new PHUIDocumentView())
            ->setUser($viewer)
            ->appendChild($help_view);

        return $controller->newPage()
            ->setHeader($header)
            ->setTitle(Yii::t("app", 'HTTP Parameters'))
            ->setCrumbs($crumbs)
            ->appendChild($document);
    }


    /**
     * @param $object
     * @param $title
     * @param $body
     * @return AphrontResponse
     * @throws Exception
     * @author 陈妙威
     */
    private function buildError($object, $title, $body)
    {
        $cancel_uri = $this->getObjectCreateCancelURI($object);

        $dialog = $this->getAction()
            ->newDialog()
            ->addCancelButton($cancel_uri);

        if ($title !== null) {
            $dialog->setTitle($title);
        }

        if ($body !== null) {
            $dialog->appendParagraph($body);
        }
        $aphrontDialogResponse = new AphrontDialogResponse();
        $aphrontDialogResponse->setDialog($dialog);
        return $aphrontDialogResponse;
    }


    /**
     * @param $object
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function buildNoDefaultResponse($object)
    {
        return $this->buildError(
            $object,
            Yii::t("app", 'No Default Create Forms'),
            Yii::t("app",
                'This application is not configured with any forms for creating ' .
                'objects that are visible to you and enabled.'));
    }

    /**
     * @param $object
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function buildNoCreateResponse($object)
    {
        return $this->buildError(
            $object,
            Yii::t("app", 'No Create Permission'),
            Yii::t("app", 'You do not have permission to create these objects.'));
    }

    /**
     * @param $object
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function buildNoManageResponse($object)
    {
        return $this->buildError(
            $object,
            Yii::t("app", 'No Manage Permission'),
            Yii::t("app",
                'You do not have permission to configure forms for this ' .
                'application.'));
    }

    /**
     * @param $object
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function buildNoEditResponse($object)
    {
        return $this->buildError(
            $object,
            Yii::t("app", 'No Edit Forms'),
            Yii::t("app",
                'You do not have access to any forms which are enabled and marked ' .
                'as edit forms.'));
    }

    /**
     * @param $object
     * @param PhabricatorEditEngineConfiguration $config
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function buildNotEditFormRespose($object, $config)
    {
        return $this->buildError(
            $object,
            Yii::t("app", 'Not an Edit Form'),
            Yii::t("app",
                'This form ("{0}") is not marked as an edit form, so ' .
                'it can not be used to edit objects.',
                [
                    $config->getName()
                ]));
    }

    /**
     * @param $object
     * @param PhabricatorEditEngineConfiguration $config
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function buildDisabledFormResponse($object, $config)
    {
        return $this->buildError(
            $object,
            Yii::t("app", 'Form Disabled'),
            Yii::t("app",
                'This form ("{0}") has been disabled, so it can not be used.',
                [
                    $config->getName()
                ]));
    }

    /**
     * @param $object
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function buildLockedObjectResponse($object)
    {
        $dialog = $this->buildError($object, null, null);
        $viewer = $this->getViewer();

        $lock = PhabricatorEditEngineLock::newForObject($viewer, $object);
        return $lock->willBlockUserInteractionWithDialog($dialog);
    }

    /**
     * @param PhabricatorApplicationTransactionInterface|PhabricatorPolicyInterface $object
     * @return mixed
     * @throws AphrontDuplicateKeyQueryException
     * @throws AphrontObjectMissingQueryException
     * @throws AphrontQueryException
     * @throws IntegrityException
     * @throws InvalidConfigException
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws Throwable
     * @throws UnknownPropertyException
     * @author 陈妙威
     */
    private function buildCommentResponse($object)
    {
        $viewer = $this->getViewer();

        if ($this->getIsCreate()) {
            return new Aphront404Response();
        }

        $controller = $this->getAction();


        if (!$this->getAction()->getRequest()->isFormPost()) {
            return new Aphront400Response();
        }

        $can_interact = PhabricatorPolicyFilter::canInteract($viewer, $object);
        if (!$can_interact) {
            return $this->buildLockedObjectResponse($object);
        }

        $config = $this->loadDefaultEditConfiguration($object);
        if (!$config) {
            return new Aphront404Response();
        }

        $fields = $this->buildEditFields($object);

        $is_preview = $this->getAction()->getRequest()->isPreviewRequest();
        $view_uri = $this->getEffectiveObjectViewURI($object);

        $template = $object->getApplicationTransactionTemplate();
        $comment_template = $template->getApplicationTransactionCommentObject();

        $comment_text = $this->getAction()->getRequest()->getStr('comment');

        $actions = $this->getAction()->getRequest()->getStr('editengine.actions');
        if ($actions) {
            $actions = phutil_json_decode($actions);
        }

        if ($is_preview) {
            $version_key = PhabricatorVersionedDraft::KEY_VERSION;
            $request_version = $this->getAction()->getRequest()->getInt($version_key);
            $current_version = $this->loadDraftVersion($object);
            if ($request_version >= $current_version) {
                $draft = PhabricatorVersionedDraft::loadOrCreateDraft(
                    $object->getPHID(),
                    $viewer->getPHID(),
                    $current_version);

                $is_empty = (!strlen($comment_text) && !$actions);

                $draft
                    ->setProperty('comment', $comment_text)
                    ->setProperty('actions', $actions)
                    ->save();

                $draft_engine = $this->newDraftEngine($object);
                if ($draft_engine) {
                    $draft_engine
                        ->setVersionedDraft($draft)
                        ->synchronize();
                }
            }
        }

        $xactions = array();

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $object,
            PhabricatorPolicyCapability::CAN_EDIT);

        if ($actions) {
            $action_map = array();
            foreach ($actions as $action) {
                $type = ArrayHelper::getValue($action, 'type');
                if (!$type) {
                    continue;
                }

                if (empty($fields[$type])) {
                    continue;
                }

                $action_map[$type] = $action;
            }

            foreach ($action_map as $type => $action) {
                $field = $fields[$type];

                if (!$field->shouldGenerateTransactionsFromComment()) {
                    continue;
                }

                // If you don't have edit permission on the object, you're limited in
                // which actions you can take via the comment form. Most actions
                // need edit permission, but some actions (like "Accept Revision")
                // can be applied by anyone with view permission.
                if (!$can_edit) {
                    if (!$field->getCanApplyWithoutEditCapability()) {
                        // We know the user doesn't have the capability, so this will
                        // raise a policy exception.
                        PhabricatorPolicyFilter::requireCapability(
                            $viewer,
                            $object,
                            PhabricatorPolicyCapability::CAN_EDIT);
                    }
                }

                if (array_key_exists('initialValue', $action)) {
                    $field->setInitialValue($action['initialValue']);
                }

                $field->readValueFromComment(ArrayHelper::getValue($action, 'value'));

                $type_xactions = $field->generateTransactions(
                    clone $template,
                    array(
                        'value' => $field->getValueForTransaction(),
                    ));
                foreach ($type_xactions as $type_xaction) {
                    $xactions[] = $type_xaction;
                }
            }
        }

        $auto_xactions = $this->newAutomaticCommentTransactions($object);
        foreach ($auto_xactions as $xaction) {
            $xactions[] = $xaction;
        }

        if (strlen($comment_text) || !$xactions) {
            $phabricatorApplicationTransaction = clone $template;
            $comment_template1 = clone $comment_template;
            $xactions[] = $phabricatorApplicationTransaction
                ->setTransactionType(PhabricatorTransactions::TYPE_COMMENT)
                ->attachComment($comment_template1->setContent($comment_text));
        }

        $editor = $object->getApplicationTransactionEditor()
            ->setActor($viewer)
            ->setContinueOnNoEffect($this->getAction()->getRequest()->isContinueRequest())
            ->setContinueOnMissingFields(true)
            ->setContentSourceFromRequest($this->getAction()->getRequest())
            ->setRaiseWarnings(!$this->getAction()->getRequest()->getBool('editEngine.warnings'))
            ->setIsPreview($is_preview);

        try {
            $xactions = $editor->applyTransactions($object, $xactions);
        } catch (PhabricatorApplicationTransactionValidationException $ex) {
            return (new PhabricatorApplicationTransactionValidationResponse())
                ->setCancelURI($view_uri)
                ->setException($ex);
        } catch (PhabricatorApplicationTransactionNoEffectException $ex) {
            return (new PhabricatorApplicationTransactionNoEffectResponse())
                ->setCancelURI($view_uri)
                ->setException($ex);
        } catch (PhabricatorApplicationTransactionWarningException $ex) {
            return (new PhabricatorApplicationTransactionWarningResponse())
                ->setCancelURI($view_uri)
                ->setException($ex);
        }

        if (!$is_preview) {
            PhabricatorVersionedDraft::purgeDrafts(
                $object->getPHID(),
                $viewer->getPHID());

            $draft_engine = $this->newDraftEngine($object);
            if ($draft_engine) {
                $draft_engine
                    ->setVersionedDraft(null)
                    ->synchronize();
            }
        }

        if ($this->getAction()->getRequest()->isAjax() && $is_preview) {
            $preview_content = $this->newCommentPreviewContent($object, $xactions);

            return (new PhabricatorApplicationTransactionResponse())
                ->setViewer($viewer)
                ->setTransactions($xactions)
                ->setIsPreview($is_preview)
                ->setPreviewContent($preview_content);
        } else {
            return (new AphrontRedirectResponse())->setURI($view_uri);
        }
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function newDraftEngine($object)
    {
        $viewer = $this->getViewer();

        if ($object instanceof PhabricatorDraftInterface) {
            $engine = $object->newDraftEngine();
        } else {
            $engine = new PhabricatorBuiltinDraftEngine();
        }

        return $engine
            ->setObject($object)
            ->setViewer($viewer);
    }


    /* -(  Conduit  )------------------------------------------------------------ */


    /**
     * Respond to a Conduit edit request.
     *
     * This method accepts a list of transactions to apply to an object, and
     * either edits an existing object or creates a new one.
     *
     * @task conduit
     * @param ConduitAPIRequest $request
     * @return array
     * @throws AphrontObjectMissingQueryException
     * @throws AphrontQueryException
     * @throws IntegrityException
     * @throws InvalidConfigException
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionValidationException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilProxyException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws Throwable
     */
    final public function buildConduitResponse(ConduitAPIRequest $request)
    {
        $viewer = $this->getViewer();

        $config = $this->loadDefaultConfiguration();
        if (!$config) {
            throw new Exception(
                Yii::t("app",
                    'Unable to load configuration for this EditEngine ("%s").',
                    get_class($this)));
        }

        $raw_xactions = $this->getRawConduitTransactions();

        $identifier = $this->getAction()->getRequest()->getValue('objectIdentifier');
        if ($identifier) {
            $this->setIsCreate(false);

            // After T13186, each transaction can individually weaken or replace the
            // capabilities required to apply it, so we no longer need CAN_EDIT to
            // attempt to apply transactions to objects. In practice, almost all
            // transactions require CAN_EDIT so we won't get very far if we don't
            // have it.
            $capabilities = array(
                PhabricatorPolicyCapability::CAN_VIEW,
            );

            $object = $this->newObjectFromIdentifier(
                $identifier,
                $capabilities);
        } else {
            $this->requireCreateCapability();

            $this->setIsCreate(true);
            $object = $this->newEditableObjectFromConduit($raw_xactions);
        }

        $this->validateObject($object);

        $fields = $this->buildEditFields($object);

        $types = $this->getConduitEditTypesFromFields($fields);
        $template = $object->getApplicationTransactionTemplate();

        $xactions = $this->getConduitTransactions(
            $request,
            $raw_xactions,
            $types,
            $template);

        /** @var PhabricatorApplicationTransactionEditor $editor */
        $editor = $object->getApplicationTransactionEditor()
            ->setActor($viewer)
            ->setContentSource($this->getAction()->getRequest()->newContentSource())
            ->setContinueOnNoEffect(true);

        if (!$this->getIsCreate()) {
            $editor->setContinueOnMissingFields(true);
        }

        $xactions = $editor->applyTransactions($object, $xactions);

        $xactions_struct = array();
        foreach ($xactions as $xaction) {
            $xactions_struct[] = array(
                'phid' => $xaction->getPHID(),
            );
        }

        return array(
            'object' => array(
                'id' => (int)$object->getID(),
                'phid' => $object->getPHID(),
            ),
            'transactions' => $xactions_struct,
        );
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function getRawConduitTransactions()
    {
        $transactions_key = 'transactions';
        $xactions = $this->getAction()->getRequest()->getValue($transactions_key);
        if (!is_array($xactions)) {
            throw new Exception(
                Yii::t("app",
                    'Parameter "%s" is not a list of transactions.',
                    $transactions_key));
        }

        foreach ($xactions as $key => $xaction) {
            if (!is_array($xaction)) {
                throw new Exception(
                    Yii::t("app",
                        'Parameter "%s" must contain a list of transaction descriptions, ' .
                        'but item with key "%s" is not a dictionary.',
                        $transactions_key,
                        $key));
            }

            if (!array_key_exists('type', $xaction)) {
                throw new Exception(
                    Yii::t("app",
                        'Parameter "%s" must contain a list of transaction descriptions, ' .
                        'but item with key "%s" is missing a "type" field. Each ' .
                        'transaction must have a type field.',
                        $transactions_key,
                        $key));
            }
        }

        return $xactions;
    }


    /**
     * Generate transactions which can be applied from edit actions in a Conduit
     * request.
     *
     * @param ConduitAPIRequest $request The request.
     * @param array $xactions Raw conduit transactions.
     * @param PhabricatorEditType[] $types Supported edit types.
     * @param PhabricatorApplicationTransaction $template Template transaction.
     * @return array
     * @throws PhutilProxyException
     * @throws Exception
     * @task conduit
     */
    private function getConduitTransactions(
        ConduitAPIRequest $request,
        array $xactions,
        array $types,
        PhabricatorApplicationTransaction $template)
    {

        $viewer = $request->getUser();
        $results = array();

        foreach ($xactions as $key => $xaction) {
            $type = $xaction['type'];
            if (empty($types[$type])) {
                throw new Exception(
                    pht(
                        'Transaction with key "%s" has invalid type "%s". This type is ' .
                        'not recognized. Valid types are: %s.',
                        $key,
                        $type,
                        implode(', ', array_keys($types))));
            }
        }

        if ($this->getIsCreate()) {
            /** @var PhabricatorApplicationTransaction $x */
            $x = clone $template;
            $results[] = $x
                ->setTransactionType(PhabricatorTransactions::TYPE_CREATE);
        }

        $is_strict = $request->getIsStrictlyTyped();

        foreach ($xactions as $xaction) {
            $type = $types[$xaction['type']];

            // Let the parameter type interpret the value. This allows you to
            // use usernames in list<user> fields, for example.
            $parameter_type = $type->getConduitParameterType();

            $parameter_type->setViewer($viewer);

            try {
                $value = $xaction['value'];
                $value = $parameter_type->getValue($xaction, 'value', $is_strict);
                $value = $type->getTransactionValueFromConduit($value);
                $xaction['value'] = $value;
            } catch (Exception $ex) {
                throw new PhutilProxyException(
                    pht(
                        'Exception when processing transaction of type "%s": %s',
                        $xaction['type'],
                        $ex->getMessage()),
                    $ex);
            }

            $type_xactions = $type->generateTransactions(
                clone $template,
                $xaction);

            foreach ($type_xactions as $type_xaction) {
                $results[] = $type_xaction;
            }
        }

        return $results;
    }

    /**
     * @param PhabricatorEditEngine[] $fields
     * @return array<string, PhabricatorEditType>
     * @throws Exception
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @task conduit
     */
    private function getConduitEditTypesFromFields(array $fields)
    {
        $types = array();
        foreach ($fields as $field) {
            $field_types = $field->getConduitEditTypes();

            if ($field_types === null) {
                continue;
            }

            foreach ($field_types as $field_type) {
                $types[$field_type->getEditType()] = $field_type;
            }
        }
        return $types;
    }

    /**
     * @return PhabricatorEditField[]
     * @throws Exception
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @author 陈妙威
     */
    public function getConduitEditTypes()
    {
        $config = $this->loadDefaultConfiguration();
        if (!$config) {
            return array();
        }

        $object = $this->newEditableObjectForDocumentation();
        $fields = $this->buildEditFields($object);
        return $this->getConduitEditTypesFromFields($fields);
    }

    /**
     * @return PhabricatorEditEngine[]
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    final public static function getAllEditEngines()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorEditEngine::class)
            ->setUniqueMethod('getEngineKey')
            ->execute();
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $key
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final public static function getByKey(PhabricatorUser $viewer, $key)
    {
        return (new PhabricatorEditEngineQuery())
            ->setViewer($viewer)
            ->withEngineKeys(array($key))
            ->executeOne();
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getIcon()
    {
        $application = $this->getApplication();
        return $application->getIcon();
    }

    /**
     * @return PhabricatorEditEngineConfiguration[]
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function loadUsableConfigurationsForCreate()
    {
        $viewer = $this->getViewer();

        $configs = PhabricatorEditEngineConfiguration::find()
            ->setViewer($viewer)
            ->withEngineKeys(array($this->getEngineKey()))
            ->withIsDefault(true)
            ->withIsDisabled(false)
            ->execute();

        /** @var PhabricatorEditEngineConfiguration[] $configs */
        $configs = msort($configs, 'getCreateSortKey');

        // Attach this specific engine to configurations we load so they can access
        // any runtime configuration. For example, this allows us to generate the
        // correct "Create Form" buttons when editing forms, see T12301.
        foreach ($configs as $config) {
            $config->attachEngine($this);
        }

        return $configs;
    }

    /**
     * @param PhabricatorApplicationTransactionValidationException $ex
     * @param PhabricatorEditField $field
     * @return null
     * @author 陈妙威
     */
    protected function getValidationExceptionShortMessage(
        PhabricatorApplicationTransactionValidationException $ex,
        PhabricatorEditField $field)
    {

        $xaction_type = $field->getTransactionType();
        if ($xaction_type === null) {
            return null;
        }

        return $ex->getShortMessage($xaction_type);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getCreateNewObjectPolicy()
    {
        return PhabricatorPolicies::POLICY_USER;
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function requireCreateCapability()
    {
        PhabricatorPolicyFilter::requireCapability($this->getViewer(), $this, PhabricatorPolicyCapability::CAN_EDIT);
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function hasCreateCapability()
    {
        return PhabricatorPolicyFilter::hasCapability(
            $this->getViewer(),
            $this,
            PhabricatorPolicyCapability::CAN_EDIT);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isCommentAction()
    {
        return ($this->getEditAction() == 'comment');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEditAction()
    {
        return $this->getAction()->getRequest()->getURIData('editAction');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function newCommentActionGroups()
    {
        return array();
    }

    /**
     * @param $object
     * @return array
     * @author 陈妙威
     */
    protected function newAutomaticCommentTransactions($object)
    {
        return array();
    }

    /**
     * @param $object
     * @param array $xactions
     * @return null
     * @author 陈妙威
     */
    protected function newCommentPreviewContent($object, array $xactions)
    {
        return null;
    }


    /* -(  Form Pages  )--------------------------------------------------------- */


    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSelectedPage()
    {
        return $this->page;
    }


    /**
     * @param $object
     * @param $page_key
     * @return null
     * @author 陈妙威
     */
    private function selectPage($object, $page_key)
    {
        $pages = $this->getPages($object);

        if (empty($pages[$page_key])) {
            return null;
        }

        $this->page = $pages[$page_key];
        return $this->page;
    }


    /**
     * @param $object
     * @return array
     * @author 陈妙威
     */
    protected function newPages($object)
    {
        return array();
    }


    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getPages($object)
    {
        if ($this->pages === null) {
            $pages = $this->newPages($object);

            assert_instances_of($pages, PhabricatorEditPage::className());
            $pages = mpull($pages, null, 'getKey');

            $this->pages = $pages;
        }

        return $this->pages;
    }

    /**
     * @param $object
     * @param array $fields
     * @return array
     * @author 陈妙威
     */
    private function applyPageToFields($object, array $fields)
    {
        $pages = $this->getPages($object);
        if (!$pages) {
            return $fields;
        }

        if (!$this->getSelectedPage()) {
            return $fields;
        }

        $page_picks = array();
        /** @var PhabricatorEditPage $wild */
        $wild = head($pages);
        $default_key = $wild->getKey();
        foreach ($pages as $page_key => $page) {
            foreach ($page->getFieldKeys() as $field_key) {
                $page_picks[$field_key] = $page_key;
            }
            if ($page->getIsDefault()) {
                $default_key = $page_key;
            }
        }

        $page_map = array_fill_keys(array_keys($pages), array());
        foreach ($fields as $field_key => $field) {
            if (isset($page_picks[$field_key])) {
                $page_map[$page_picks[$field_key]][$field_key] = $field;
                continue;
            }

            // TODO: Maybe let the field pick a page to associate itself with so
            // extensions can force themselves onto a particular page?

            $page_map[$default_key][$field_key] = $field;
        }

        $page = $this->getSelectedPage();
        if (!$page) {
            $page = head($pages);
        }

        $selected_key = $page->getKey();
        return $page_map[$selected_key];
    }

    /**
     * @param $object
     * @param array $xactions
     * @return array
     * @author 陈妙威
     */
    protected function willApplyTransactions($object, array $xactions)
    {
        return $xactions;
    }

    /**
     * @param $object
     * @param array $xactions
     * @author 陈妙威
     */
    protected function didApplyTransactions($object, array $xactions)
    {
        return;
    }


    /* -(  Bulk Edits  )--------------------------------------------------------- */

    /**
     * @return array
     * @throws Exception
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    final public function newBulkEditGroupMap()
    {
        $groups = $this->newBulkEditGroups();

        $map = array();
        foreach ($groups as $group) {
            $key = $group->getKey();

            if (isset($map[$key])) {
                throw new Exception(
                    Yii::t("app",
                        'Two bulk edit groups have the same key ("%s"). Each bulk edit ' .
                        'group must have a unique key.',
                        $key));
            }

            $map[$key] = $group;
        }

        if ($this->isEngineExtensible()) {
            $extensions = PhabricatorEditEngineExtension::getAllEnabledExtensions();
        } else {
            $extensions = array();
        }

        foreach ($extensions as $extension) {
            $extension_groups = $extension->newBulkEditGroups($this);
            foreach ($extension_groups as $group) {
                $key = $group->getKey();

                if (isset($map[$key])) {
                    throw new Exception(
                        Yii::t("app",
                            'Extension "%s" defines a bulk edit group with the same key ' .
                            '("%s") as the main editor or another extension. Each bulk ' .
                            'edit group must have a unique key.'));
                }

                $map[$key] = $group;
            }
        }

        return $map;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function newBulkEditGroups()
    {
        return array(
            (new PhabricatorBulkEditGroup())
                ->setKey('default')
                ->setLabel(Yii::t("app", 'Primary Fields')),
            (new PhabricatorBulkEditGroup())
                ->setKey('extension')
                ->setLabel(Yii::t("app", 'Support Applications')),
        );
    }

    /**
     * @return array
     * @throws Exception
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @author 陈妙威
     */
    final public function newBulkEditMap()
    {
        $config = $this->loadDefaultConfiguration();
        if (!$config) {
            throw new Exception(
                Yii::t("app", 'No default edit engine configuration for bulk edit.'));
        }

        $object = $this->newEditableObject();
        $fields = $this->buildEditFields($object);
        $groups = $this->newBulkEditGroupMap();

        $edit_types = $this->getBulkEditTypesFromFields($fields);

        $map = array();
        foreach ($edit_types as $key => $type) {
            $bulk_type = $type->getBulkParameterType();
            if ($bulk_type === null) {
                continue;
            }

            $bulk_label = $type->getBulkEditLabel();
            if ($bulk_label === null) {
                continue;
            }

            $group_key = $type->getBulkEditGroupKey();
            if (!$group_key) {
                $group_key = 'default';
            }

            if (!isset($groups[$group_key])) {
                throw new Exception(
                    Yii::t("app",
                        'Field "%s" has a bulk edit group key ("%s") with no ' .
                        'corresponding bulk edit group.',
                        $key,
                        $group_key));
            }

            $map[] = array(
                'label' => $bulk_label,
                'xaction' => $key,
                'group' => $group_key,
                'control' => array(
                    'type' => $bulk_type->getPHUIXControlType(),
                    'spec' => (object)$bulk_type->getPHUIXControlSpecification(),
                ),
            );
        }

        return $map;
    }


    /**
     * @param array $xactions
     * @return array
     * @throws Exception
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    final public function newRawBulkTransactions(array $xactions)
    {
        $config = $this->loadDefaultConfiguration();
        if (!$config) {
            throw new Exception(
                Yii::t("app", 'No default edit engine configuration for bulk edit.'));
        }

        $object = $this->newEditableObject();
        $fields = $this->buildEditFields($object);

        $edit_types = $this->getBulkEditTypesFromFields($fields);
        $template = $object->getApplicationTransactionTemplate();

        $raw_xactions = array();
        foreach ($xactions as $key => $xaction) {
            PhutilTypeSpec::checkMap(
                $xaction,
                array(
                    'type' => 'string',
                    'value' => 'optional wild',
                ));

            $type = $xaction['type'];
            if (!isset($edit_types[$type])) {
                throw new Exception(
                    Yii::t("app",
                        'Unsupported bulk edit type "%s".',
                        $type));
            }

            $edit_type = $edit_types[$type];

            // Replace the edit type with the underlying transaction type. Usually
            // these are 1:1 and the transaction type just has more internal noise,
            // but it's possible that this isn't the case.
            $xaction['type'] = $edit_type->getTransactionType();

            $value = $xaction['value'];
            $value = $edit_type->getTransactionValueFromBulkEdit($value);
            $xaction['value'] = $value;

            $xaction_objects = $edit_type->generateTransactions(
                clone $template,
                $xaction);

            foreach ($xaction_objects as $xaction_object) {
                $raw_xaction = array(
                    'type' => $xaction_object->getTransactionType(),
                    'metadata' => $xaction_object->getMetadata(),
                    'new' => $xaction_object->getNewValue(),
                );

                if ($xaction_object->hasOldValue()) {
                    $raw_xaction['old'] = $xaction_object->getOldValue();
                }

                if ($xaction_object->hasComment()) {
                    $comment = $xaction_object->getComment();
                    $raw_xaction['comment'] = $comment->getContent();
                }

                $raw_xactions[] = $raw_xaction;
            }
        }

        return $raw_xactions;
    }

    /**
     * @param PhabricatorEditField[] $fields
     * @return PhabricatorEditType[]
     * @author 陈妙威
     */
    private function getBulkEditTypesFromFields(array $fields)
    {
        $types = array();

        foreach ($fields as $field) {
            $field_types = $field->getBulkEditTypes();

            if ($field_types === null) {
                continue;
            }

            foreach ($field_types as $field_type) {
                $types[$field_type->getEditType()] = $field_type;
            }
        }

        return $types;
    }


    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */


    /**
     * @return string
     * @throws ReflectionException
     * @author 陈妙威
     */
    public function getPHID()
    {
        return $this->getClassShortName();
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function getCapabilities()
    {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
        );
    }

    /**
     * @param $capability
     * @return mixed|void
     * @throws Exception
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                return PhabricatorPolicies::getMostOpenPolicy();
            case PhabricatorPolicyCapability::CAN_EDIT:
                return $this->getCreateNewObjectPolicy();
        }
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool|mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return false;
    }


}
