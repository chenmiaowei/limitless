<?php

namespace orangins\lib\infrastructure\customfield\standard;

use Exception;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldNotProxyException;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomFieldGroup;
use orangins\lib\infrastructure\customfield\interfaces\PhabricatorStandardCustomFieldInterface;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\index\PhabricatorSearchAbstractDocument;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\error\PhabricatorApplicationTransactionValidationError;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use PhutilClassMapQuery;
use PhutilInvalidStateException;
use PhutilJSONParserException;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorStandardCustomField
 * @package orangins\lib\infrastructure\customfield\standard
 * @author 陈妙威
 */
abstract class PhabricatorStandardCustomField
    extends PhabricatorCustomField
{
    /**
     * @var int
     */
    public $sortOrder;
    /**
     * @var
     */
    private $rawKey;
    /**
     * @var string
     */
    private $fieldGroup;
    /**
     * @var
     */
    private $fieldKey;
    /**
     * @var
     */
    private $fieldName;
    /**
     * @var
     */
    private $fieldValue;
    /**
     * @var
     */
    private $fieldDescription;
    /**
     * @var
     */
    private $fieldConfig;
    /**
     * @var PhabricatorCustomField
     */
    private $applicationField;
    /**
     * @var array
     */
    private $strings = array();
    /**
     * @var
     */
    private $caption;
    /**
     * @var
     */
    private $fieldError;
    /**
     * @var
     */
    private $required;
    /**
     * @var
     */
    private $default;
    /**
     * @var
     */
    private $isCopyable;
    /**
     * @var
     */
    private $hasStorageValue;
    /**
     * @var
     */
    private $isBuiltin;

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getFieldType();

    /**
     * @param PhabricatorCustomField|PhabricatorStandardCustomFieldInterface $template
     * @param array $config
     * @param bool $builtin
     * @return array
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws PhutilInvalidStateException
     * @throws PhabricatorCustomFieldNotProxyException
     * @throws PhabricatorCustomFieldNotProxyException
     * @author 陈妙威
     */
    public static function buildStandardFields(
        PhabricatorCustomField $template,
        array $config,
        $builtin = false)
    {

        $types = (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getFieldType')
            ->execute();

        $fields = array();
        foreach ($config as $key => $value) {
            $type = ArrayHelper::getValue($value, 'type', 'text');
            if (empty($types[$type])) {
                // TODO: We should have better typechecking somewhere, and then make
                // this more serious.
                continue;
            }

            $namespace = $template->getStandardCustomFieldNamespace();
            $full_key = "std:{$namespace}:{$key}";

            $template = clone $template;

            /** @var PhabricatorStandardCustomField $var */
            $var = clone $types[$type];
            $standard = $var
                ->setRawStandardFieldKey($key)
                ->setFieldKey($full_key)
                ->setFieldConfig($value)
                ->setApplicationField($template);

            if ($builtin) {
                $standard->setIsBuiltin(true);
            }

            $field = $template->setProxy($standard);
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * @param PhabricatorStandardCustomFieldInterface $application_field
     * @return $this
     * @author 陈妙威
     */
    public function setApplicationField(
        PhabricatorStandardCustomFieldInterface $application_field)
    {
        $this->applicationField = $application_field;
        return $this;
    }

    /**
     * @return PhabricatorCustomField
     * @author 陈妙威
     */
    public function getApplicationField()
    {
        return $this->applicationField;
    }

    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function setFieldName($name)
    {
        $this->fieldName = $name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getFieldValue()
    {
        return $this->fieldValue;
    }

    /**
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function setFieldValue($value)
    {
        $this->fieldValue = $value;
        return $this;
    }


    /**
     * @param string $group
     * @return self
     */
    public function setFieldGroup($group)
    {
        $this->fieldGroup = $group;
        return $this;
    }

    /**
     * @param string $group
     * @return self
     */
    public function setSortOrder($group)
    {
        $this->sortOrder = $group;
        return $this;
    }


    /**
     * @param $caption
     * @return $this
     * @author 陈妙威
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * @param $description
     * @return $this
     * @author 陈妙威
     */
    public function setFieldDescription($description)
    {
        $this->fieldDescription = $description;
        return $this;
    }

    /**
     * @param $is_builtin
     * @return $this
     * @author 陈妙威
     */
    public function setIsBuiltin($is_builtin)
    {
        $this->isBuiltin = $is_builtin;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsBuiltin()
    {
        return $this->isBuiltin;
    }

    /**
     * @param array $config
     * @return $this
     * @author 陈妙威
     */
    public function setFieldConfig(array $config)
    {
        foreach ($config as $key => $value) {
            switch ($key) {
                case 'name':
                    $this->setFieldName($value);
                    break;
                case 'group':
                    $this->setFieldGroup((new PhabricatorCustomFieldGroup())->setName(ArrayHelper::getValue($value, 'name'))->setSortOrder(ArrayHelper::getValue($value, 'sort')));
                    break;
                case 'sort':
                    $this->setSortOrder($value);
                    break;
                case 'description':
                    $this->setFieldDescription($value);
                    break;
                case 'strings':
                    $this->setStrings($value);
                    break;
                case 'caption':
                    $this->setCaption($value);
                    break;
                case 'required':
                    if ($value) {
                        $this->setRequired($value);
                        $this->setFieldError(true);
                    }
                    break;
                case 'default':
                    $this->setFieldValue($value);
                    break;
                case 'copy':
                    $this->setIsCopyable($value);
                    break;
                case 'type':
                    // We set this earlier on.
                    break;
            }
        }
        $this->fieldConfig = $config;
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return array
     * @author 陈妙威
     */
    public function getFieldConfigValue($key, $default = null)
    {
        return ArrayHelper::getValue($this->fieldConfig, $key, $default);
    }

    /**
     * @param $field_error
     * @return $this
     * @author 陈妙威
     */
    public function setFieldError($field_error)
    {
        $this->fieldError = $field_error;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getFieldError()
    {
        return $this->fieldError;
    }

    /**
     * @param $required
     * @return $this
     * @author 陈妙威
     */
    public function setRequired($required)
    {
        $this->required = $required;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * @param $raw_key
     * @return $this
     * @author 陈妙威
     */
    public function setRawStandardFieldKey($raw_key)
    {
        $this->rawKey = $raw_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRawStandardFieldKey()
    {
        return $this->rawKey;
    }


    /* -(  PhabricatorCustomField  )--------------------------------------------- */


    /**
     * @param $field_key
     * @return $this
     * @author 陈妙威
     */
    public function setFieldKey($field_key)
    {
        $this->fieldKey = $field_key;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getFieldKey()
    {
        return $this->fieldKey;
    }

    /**
     * @return string
     */
    public function getFieldGroup()
    {
        return $this->fieldGroup;
    }

    /**
     * @return int|string
     * @author 陈妙威
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @return mixed|string
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function getFieldName()
    {
        return coalesce($this->fieldName, parent::getFieldName());
    }

    /**
     * @return mixed|null|string
     * @author 陈妙威
     */
    public function getFieldDescription()
    {
        return coalesce($this->fieldDescription, parent::getFieldDescription());
    }

    /**
     * @param array $strings
     * @author 陈妙威
     */
    public function setStrings(array $strings)
    {
        $this->strings = $strings;
        return;
    }

    /**
     * @param $key
     * @param null $default
     * @return array
     * @author 陈妙威
     */
    public function getString($key, $default = null)
    {
        return ArrayHelper::getValue($this->strings, $key, $default);
    }

    /**
     * @param $is_copyable
     * @return $this
     * @author 陈妙威
     */
    public function setIsCopyable($is_copyable)
    {
        $this->isCopyable = $is_copyable;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsCopyable()
    {
        return $this->isCopyable;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldUseStorage()
    {
        try {
            $object = $this->newStorageObject();
            return true;
        } catch (PhabricatorCustomFieldImplementationIncompleteException $ex) {
            return false;
        }
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getValueForStorage()
    {
        return $this->getFieldValue();
    }

    /**
     * @param $value
     * @return PhabricatorStandardCustomField
     * @author 陈妙威
     */
    public function setValueFromStorage($value)
    {
        return $this->setFieldValue($value);
    }

    /**
     * @return $this|PhabricatorCustomField
     * @author 陈妙威
     */
    public function didSetValueFromStorage()
    {
        $this->hasStorageValue = true;
        return $this;
    }

    /**
     * @return mixed|null|string
     * @author 陈妙威
     */
    public function getOldValueForApplicationTransactions()
    {
        if ($this->hasStorageValue) {
            return $this->getValueForStorage();
        } else {
            return null;
        }
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAppearInApplicationTransactions()
    {
        return true;
    }

    /**
     * @return bool|array
     * @author 陈妙威
     */
    public function shouldAppearInEditView()
    {
        return $this->getFieldConfigValue('edit', true);
    }

    /**
     * @param AphrontRequest $request
     * @author 陈妙威
     */
    public function readValueFromRequest(AphrontRequest $request)
    {
        $value = $request->getStr($this->getFieldKey());
        if (!strlen($value)) {
            $value = null;
        }
        $this->setFieldValue($value);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getInstructionsForEdit()
    {
        return $this->getFieldConfigValue('instructions');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getPlaceholder()
    {
        return $this->getFieldConfigValue('placeholder', null);
    }

    /**
     * @param array $handles
     * @return mixed
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function renderEditControl(array $handles)
    {
        return (new AphrontFormTextControl())
            ->setName($this->getFieldKey())
            ->setCaption($this->getCaption())
            ->setValue($this->getFieldValue())
            ->setError($this->getFieldError())
            ->setLabel($this->getFieldName())
            ->setPlaceholder($this->getPlaceholder());
    }

    /**
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function newStorageObject()
    {
        return $this->getApplicationField()->newStorageObject();
    }

    /**
     * @return bool|array
     * @author 陈妙威
     */
    public function shouldAppearInPropertyView()
    {
        return $this->getFieldConfigValue('view', true);
    }

    /**
     * @param array $handles
     * @return mixed|null
     * @author 陈妙威
     */
    public function renderPropertyViewValue(array $handles)
    {
        if (!strlen($this->getFieldValue())) {
            return null;
        }
        return $this->getFieldValue();
    }

    /**
     * @return bool|array
     * @author 陈妙威
     */
    public function shouldAppearInApplicationSearch()
    {
        return $this->getFieldConfigValue('search', false);
    }

    /**
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    protected function newStringIndexStorage()
    {
        return $this->getApplicationField()->newStringIndexStorage();
    }

    /**
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    protected function newNumericIndexStorage()
    {
        return $this->getApplicationField()->newNumericIndexStorage();
    }

    /**
     * @author 陈妙威
     */
    public function buildFieldIndexes()
    {
        return array();
    }

    /**
     * @author 陈妙威
     */
    public function buildOrderIndex()
    {
        return null;
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param AphrontRequest $request
     * @return array|void
     * @author 陈妙威
     */
    public function readApplicationSearchValueFromRequest(
        PhabricatorApplicationSearchEngine $engine,
        AphrontRequest $request)
    {
        return;
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param PhabricatorCursorPagedPolicyAwareQuery $query
     * @param $value
     * @author 陈妙威
     */
    public function applyApplicationSearchConstraintToQuery(
        PhabricatorApplicationSearchEngine $engine,
        PhabricatorCursorPagedPolicyAwareQuery $query,
        $value)
    {
        return;
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param AphrontFormView $form
     * @param $value
     * @author 陈妙威
     */
    public function appendToApplicationSearchForm(
        PhabricatorApplicationSearchEngine $engine,
        AphrontFormView $form,
        $value)
    {
        return;
    }

    /**
     * @param PhabricatorApplicationTransactionEditor $editor
     * @param $type
     * @param array $xactions
     * @return array
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function validateApplicationTransactions(
        PhabricatorApplicationTransactionEditor $editor,
        $type,
        array $xactions)
    {

        $this->setFieldError(null);

        $errors = parent::validateApplicationTransactions(
            $editor,
            $type,
            $xactions);

        if ($this->getRequired()) {
            $value = $this->getOldValueForApplicationTransactions();

            $transaction = null;
            foreach ($xactions as $xaction) {
                $value = $xaction->getNewValue();
                if (!$this->isValueEmpty($value)) {
                    $transaction = $xaction;
                    break;
                }
            }
            if ($this->isValueEmpty($value)) {
                $error = new PhabricatorApplicationTransactionValidationError(
                    $type,
                    Yii::t("app", 'Required'),
                    Yii::t("app", '{0} is required.', [
                        $this->getFieldName()
                    ]),
                    $transaction);
                $error->setIsMissingFieldError(true);
                $errors[] = $error;
                $this->setFieldError(Yii::t("app", 'Required'));
            }
        }

        return $errors;
    }

    /**
     * @param $value
     * @return bool
     * @author 陈妙威
     */
    protected function isValueEmpty($value)
    {
        if (is_array($value)) {
            return empty($value);
        }
        return !strlen($value);
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return string
     * @throws PhutilJSONParserException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws Exception
     * @author 陈妙威
     */
    public function getApplicationTransactionTitle(
        PhabricatorApplicationTransaction $xaction)
    {
        $author_phid = $xaction->getAuthorPHID();
        $old = $xaction->getOldValue();
        $new = $xaction->getNewValue();

        if (!$old) {
            return Yii::t("app",
                '{0} set {1} to {2}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    $new
                ]);
        } else if (!$new) {
            return Yii::t("app",
                '{0} removed {1}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName()
                ]);
        } else {
            return Yii::t("app",
                '{0} changed {1} from {2} to {3}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    $old,
                    $new
                ]);
        }
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return string
     * @throws PhutilJSONParserException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws Exception
     * @author 陈妙威
     */
    public function getApplicationTransactionTitleForFeed(
        PhabricatorApplicationTransaction $xaction)
    {

        $author_phid = $xaction->getAuthorPHID();
        $object_phid = $xaction->getObjectPHID();

        $old = $xaction->getOldValue();
        $new = $xaction->getNewValue();

        if (!$old) {
            return Yii::t("app",
                '{0} set {1} to {2} on {3}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    $new,
                    $xaction->renderHandleLink($object_phid)
                ]);
        } else if (!$new) {
            return Yii::t("app",
                '{0} removed {1} on {2}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    $xaction->renderHandleLink($object_phid)
                ]);
        } else {
            return Yii::t("app",
                '{0} changed {1} from {2} to {3} on {4}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    $old,
                    $new,
                    $xaction->renderHandleLink($object_phid)
                ]);
        }
    }

    /**
     * @return mixed|array
     * @author 陈妙威
     */
    public function getHeraldFieldValue()
    {
        return $this->getFieldValue();
    }

    /**
     * @param null $key
     * @return string
     * @author 陈妙威
     */
    public function getFieldControlID($key = null)
    {
        $key = coalesce($key, $this->getRawStandardFieldKey());
        return 'std:control:' . $key;
    }

    /**
     * @return bool|array
     * @author 陈妙威
     */
    public function shouldAppearInGlobalSearch()
    {
        return $this->getFieldConfigValue('fulltext', false);
    }

    /**
     * @param PhabricatorSearchAbstractDocument $document
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function updateAbstractDocument(
        PhabricatorSearchAbstractDocument $document)
    {

        $field_key = $this->getFieldConfigValue('fulltext');

        // If the caller or configuration didn't specify a valid field key,
        // generate one automatically from the field index.
        if (!is_string($field_key) || (strlen($field_key) != 4)) {
            $field_key = '!' . substr($this->getFieldIndex(), 0, 3);
        }

        $field_value = $this->getFieldValue();
        if (strlen($field_value)) {
            $document->addField($field_key, $field_value);
        }
    }

    /**
     * @return mixed
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    protected function newStandardEditField()
    {
        $short = $this->getModernFieldKey();

        return parent::newStandardEditField()
            ->setEditTypeKey($short)
            ->setGroupKey($this->getFieldGroup())
            ->setIsCopyable($this->getIsCopyable());
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAppearInConduitTransactions()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAppearInConduitDictionary()
    {
        return true;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModernFieldKey()
    {
        if ($this->getIsBuiltin()) {
            return $this->getRawStandardFieldKey();
        } else {
            return 'custom.' . $this->getRawStandardFieldKey();
        }
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getConduitDictionaryValue()
    {
        return $this->getFieldValue();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function newExportData()
    {
        return $this->getFieldValue();
    }

}
