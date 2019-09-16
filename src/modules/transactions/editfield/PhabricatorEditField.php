<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\OranginsObject;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\editor\PhabricatorEditor;
use orangins\lib\view\form\control\AphrontFormControl;
use orangins\lib\view\phui\PHUIRemarkupPreviewPanel;
use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\modules\transactions\bulk\type\BulkParameterType;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\transactions\edittype\PhabricatorSimpleEditType;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use PhutilMethodNotImplementedException;
use Yii;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
abstract class PhabricatorEditField extends OranginsObject
{

    /**
     * @var
     */
    private $key;
    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $label;
    /**
     * @var array
     */
    private $aliases = array();
    /**
     * @var
     */
    private $value;
    /**
     * @var
     */
    private $initialValue;
    /**
     * @var bool
     */
    private $hasValue = false;
    /**
     * @var ActiveRecordPHID
     */
    private $object;

    /**
     * @var
     */
    private $transactionType;
    /**
     * @var array
     */
    private $metadata = array();
    /**
     * @var
     */
    private $editTypeKey;
    /**
     * @var
     */
    private $isRequired;
    /**
     * @var
     */
    private $previewPanel;
    /**
     * @var
     */
    private $controlID;
    /**
     * @var
     */
    private $controlInstructions;
    /**
     * @var
     */
    private $bulkEditLabel;
    /**
     * @var
     */
    private $bulkEditGroupKey;

    /**
     * @var
     */
    private $description;
    /**
     * @var
     */
    private $conduitDescription;
    /**
     * @var
     */
    private $conduitDocumentation;
    /**
     * @var
     */
    private $conduitTypeDescription;

    /**
     * @var
     */
    private $commentActionLabel;
    /**
     * @var
     */
    private $commentActionValue;
    /**
     * @var
     */
    private $commentActionGroupKey;
    /**
     * @var int
     */
    private $commentActionOrder = 1000;
    /**
     * @var
     */
    private $hasCommentActionValue;

    /**
     * @var
     */
    private $isLocked;
    /**
     * @var
     */
    private $isHidden;

    /**
     * @var
     */
    private $isPreview;
    /**
     * @var
     */
    private $isEditDefaults;
    /**
     * @var
     */
    private $isSubmittedForm;
    /**
     * @var
     */
    private $controlError;
    /**
     * @var bool
     */
    private $canApplyWithoutEditCapability = false;

    /**
     * @var bool
     */
    private $isReorderable = true;
    /**
     * @var bool
     */
    private $isDefaultable = true;
    /**
     * @var bool
     */
    private $isLockable = true;
    /**
     * @var bool
     */
    private $isCopyable = false;
    /**
     * @var bool
     */
    private $isFormField = true;

    /**
     * @var
     */
    private $conduitEditTypes;
    /**
     * @var
     */
    private $bulkEditTypes;

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param $label
     * @return $this
     * @author 陈妙威
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param $bulk_edit_label
     * @return $this
     * @author 陈妙威
     */
    public function setBulkEditLabel($bulk_edit_label)
    {
        $this->bulkEditLabel = $bulk_edit_label;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBulkEditLabel()
    {
        return $this->bulkEditLabel;
    }

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     */
    public function setBulkEditGroupKey($key)
    {
        $this->bulkEditGroupKey = $key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBulkEditGroupKey()
    {
        return $this->bulkEditGroupKey;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param array $aliases
     * @return $this
     * @author 陈妙威
     */
    public function setAliases(array $aliases)
    {
        $this->aliases = $aliases;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getObject()
    {
        return $this->object;
    }


    /**
     * @param $is_locked
     * @return $this
     * @author 陈妙威
     */
    public function setIsLocked($is_locked)
    {
        $this->isLocked = $is_locked;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsLocked()
    {
        return $this->isLocked;
    }

    /**
     * @param $preview
     * @return $this
     * @author 陈妙威
     */
    public function setIsPreview($preview)
    {
        $this->isPreview = $preview;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsPreview()
    {
        return $this->isPreview;
    }

    /**
     * @param $is_reorderable
     * @return $this
     * @author 陈妙威
     */
    public function setIsReorderable($is_reorderable)
    {
        $this->isReorderable = $is_reorderable;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIsReorderable()
    {
        return $this->isReorderable;
    }

    /**
     * @param $is_form_field
     * @return $this
     * @author 陈妙威
     */
    public function setIsFormField($is_form_field)
    {
        $this->isFormField = $is_form_field;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIsFormField()
    {
        return $this->isFormField;
    }

    /**
     * @param $description
     * @return $this
     * @author 陈妙威
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $conduit_description
     * @return $this
     * @author 陈妙威
     */
    public function setConduitDescription($conduit_description)
    {
        $this->conduitDescription = $conduit_description;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getConduitDescription()
    {
        if ($this->conduitDescription === null) {
            return $this->getDescription();
        }
        return $this->conduitDescription;
    }

    /**
     * @param $conduit_documentation
     * @return $this
     * @author 陈妙威
     */
    public function setConduitDocumentation($conduit_documentation)
    {
        $this->conduitDocumentation = $conduit_documentation;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getConduitDocumentation()
    {
        return $this->conduitDocumentation;
    }

    /**
     * @param $conduit_type_description
     * @return $this
     * @author 陈妙威
     */
    public function setConduitTypeDescription($conduit_type_description)
    {
        $this->conduitTypeDescription = $conduit_type_description;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getConduitTypeDescription()
    {
        return $this->conduitTypeDescription;
    }

    /**
     * @param $is_edit_defaults
     * @return $this
     * @author 陈妙威
     */
    public function setIsEditDefaults($is_edit_defaults)
    {
        $this->isEditDefaults = $is_edit_defaults;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsEditDefaults()
    {
        return $this->isEditDefaults;
    }

    /**
     * @param $is_defaultable
     * @return $this
     * @author 陈妙威
     */
    public function setIsDefaultable($is_defaultable)
    {
        $this->isDefaultable = $is_defaultable;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIsDefaultable()
    {
        return $this->isDefaultable;
    }

    /**
     * @param $is_lockable
     * @return $this
     * @author 陈妙威
     */
    public function setIsLockable($is_lockable)
    {
        $this->isLockable = $is_lockable;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIsLockable()
    {
        return $this->isLockable;
    }

    /**
     * @param $is_hidden
     * @return $this
     * @author 陈妙威
     */
    public function setIsHidden($is_hidden)
    {
        $this->isHidden = $is_hidden;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsHidden()
    {
        return $this->isHidden;
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
     * @return bool
     * @author 陈妙威
     */
    public function getIsCopyable()
    {
        return $this->isCopyable;
    }

    /**
     * @param $is_submitted
     * @return $this
     * @author 陈妙威
     */
    public function setIsSubmittedForm($is_submitted)
    {
        $this->isSubmittedForm = $is_submitted;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsSubmittedForm()
    {
        return $this->isSubmittedForm;
    }

    /**
     * @param $is_required
     * @return $this
     * @author 陈妙威
     */
    public function setIsRequired($is_required)
    {
        $this->isRequired = $is_required;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsRequired()
    {
        return $this->isRequired;
    }

    /**
     * @param $control_error
     * @return $this
     * @author 陈妙威
     */
    public function setControlError($control_error)
    {
        $this->controlError = $control_error;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getControlError()
    {
        return $this->controlError;
    }

    /**
     * @param $label
     * @return $this
     * @author 陈妙威
     */
    public function setCommentActionLabel($label)
    {
        $this->commentActionLabel = $label;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCommentActionLabel()
    {
        return $this->commentActionLabel;
    }

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     */
    public function setCommentActionGroupKey($key)
    {
        $this->commentActionGroupKey = $key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCommentActionGroupKey()
    {
        return $this->commentActionGroupKey;
    }

    /**
     * @param $order
     * @return $this
     * @author 陈妙威
     */
    public function setCommentActionOrder($order)
    {
        $this->commentActionOrder = $order;
        return $this;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getCommentActionOrder()
    {
        return $this->commentActionOrder;
    }

    /**
     * @param $comment_action_value
     * @return $this
     * @author 陈妙威
     */
    public function setCommentActionValue($comment_action_value)
    {
        $this->hasCommentActionValue = true;
        $this->commentActionValue = $comment_action_value;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCommentActionValue()
    {
        return $this->commentActionValue;
    }

    /**
     * @param PHUIRemarkupPreviewPanel $preview_panel
     * @return $this
     * @author 陈妙威
     */
    public function setPreviewPanel(PHUIRemarkupPreviewPanel $preview_panel)
    {
        $this->previewPanel = $preview_panel;
        return $this;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getPreviewPanel()
    {
        if ($this->getIsHidden()) {
            return null;
        }

        if ($this->getIsLocked()) {
            return null;
        }

        return $this->previewPanel;
    }

    /**
     * @param $control_instructions
     * @return $this
     * @author 陈妙威
     */
    public function setControlInstructions($control_instructions)
    {
        $this->controlInstructions = $control_instructions;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getControlInstructions()
    {
        return $this->controlInstructions;
    }

    /**
     * @param $can_apply
     * @return $this
     * @author 陈妙威
     */
    public function setCanApplyWithoutEditCapability($can_apply)
    {
        $this->canApplyWithoutEditCapability = $can_apply;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getCanApplyWithoutEditCapability()
    {
        return $this->canApplyWithoutEditCapability;
    }

    /**
     * @return AphrontFormControl
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function newControl()
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @return AphrontFormControl
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function buildControl()
    {
        if (!$this->getIsFormField()) {
            return null;
        }

        $control = $this->newControl();
        if ($control === null) {
            return null;
        }

        $control
            ->setValue($this->getValueForControl())
            ->setName($this->getKey());

        if (!$control->getLabel()) {
            $control->setLabel($this->getLabel());
        }

        if ($this->getIsSubmittedForm()) {
            $error = $this->getControlError();
            if ($error !== null) {
                $control->setError($error);
            }
        } else if ($this->getIsRequired()) {
            $control->setError(true);
        }

        return $control;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getControlID()
    {
        if (!$this->controlID) {
            $this->controlID = JavelinHtml::generateUniqueNodeId();
        }
        return $this->controlID;
    }

    /**
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function renderControl()
    {
        $control = $this->buildControl();
        if ($control === null) {
            return null;
        }

        if ($this->getIsPreview()) {
            $disabled = true;
            $hidden = false;
        } else if ($this->getIsEditDefaults()) {
            $disabled = false;
            $hidden = false;
        } else {
            $disabled = $this->getIsLocked();
            $hidden = $this->getIsHidden();
        }

        if ($hidden) {
            return null;
        }

        $control->setDisabled($disabled);

        if ($this->controlID) {
            $control = $control->setId($this->controlID);
        }
        return $control;
    }

    /**
     * @param AphrontFormView $form
     * @return $this
     * @throws Exception
     * @throws PhutilMethodNotImplementedException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function appendToForm(AphrontFormView $form)
    {
        $control = $this->renderControl();
        if ($control !== null) {

            if ($this->getIsPreview()) {
                if ($this->getIsHidden()) {
                    $control
//                        ->addClass('aphront-form-preview-hidden')
                        ->setError(Yii::t('app', 'Hidden'));
                } else if ($this->getIsLocked()) {
                    $control
                        ->setError(Yii::t('app', 'Locked'));
                }
            }
            $instructions = $this->getControlInstructions();
            if (strlen($instructions)) {
                $form->appendRemarkupInstructions($instructions);
            }
            $form->appendControl($control);
        }
        return $this;
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getValueForControl()
    {
        return $this->getValue();
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getValueForDefaults()
    {
        $value = $this->getValue();

        // By default, just treat the empty string like `null` since they're
        // equivalent for almost all fields and this reduces the number of
        // meaningless transactions we generate when adjusting defaults.
        if ($value === '') {
            return null;
        }

        return $value;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getValue()
    {
        return $this->value;
    }

    /**
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function setValue($value)
    {
        $this->hasValue = true;
        $this->value = $value;

        // If we don't have an initial value set yet, use the value as the
        // initial value.
        $initial_value = $this->getInitialValue();
        if ($initial_value === null) {
            $this->initialValue = $value;
        }

        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return static
     * @author 陈妙威
     */
    public function setMetadataValue($key, $value)
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getValueForTransaction()
    {
        return $this->getValue();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTransactionType()
    {
        return $this->transactionType;
    }

    /**
     * @param $type
     * @return static
     * @author 陈妙威
     */
    public function setTransactionType($type)
    {
        $this->transactionType = $type;
        return $this;
    }



    /**
     * @param PhabricatorEditor $editor
     * @author 陈妙威
     * @return PhabricatorEditField
     */
    public function readValueFromEditor(PhabricatorEditor $editor)
    {
        $value = ArrayHelper::getValue($editor, $this->getKey());
        if (is_string($value) && trim($value) === '') {
            $value = $this->getDefaultValue();
        }
        $this->value = $value;
        return $this;
    }

    /**
     * @param AphrontRequest $request
     * @return $this
     * @author 陈妙威
     */
    public function readValueFromRequest(AphrontRequest $request)
    {
        $check = $this->getAllReadValueFromRequestKeys();
        foreach ($check as $key) {
            if (!$this->getValueExistsInRequest($request, $key)) {
                continue;
            }

            $this->value = $this->getValueFromRequest($request, $key);
            break;
        }
        return $this;
    }

    /**
     * @param AphrontRequest $request
     * @return $this
     * @author 陈妙威
     */
    public function readValueFromSubmit(AphrontRequest $request)
    {
        $key = $this->getKey();
        if ($this->getValueExistsInSubmit($request, $key)) {
            $value = $this->getValueFromSubmit($request, $key);
        } else {
            $value = $this->getDefaultValue();
        }
        $this->value = $value;

        $initial_value = $this->getInitialValueFromSubmit($request, $key);
        $this->initialValue = $initial_value;

        return $this;
    }

    /**
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function readValueFromComment($value)
    {
        $this->value = $this->getValueFromComment($value);
        return $this;
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    protected function getValueFromComment($value)
    {
        return $value;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAllReadValueFromRequestKeys()
    {
        $keys = array();

        $keys[] = $this->getKey();
        foreach ($this->getAliases() as $alias) {
            $keys[] = $alias;
        }

        return $keys;
    }

    /**
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function readDefaultValueFromConfiguration($value)
    {
        $this->value = $this->getDefaultValueFromConfiguration($value);
        return $this;
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    protected function getDefaultValueFromConfiguration($value)
    {
        return $value;
    }

    /**
     * @param $object
     * @return null
     * @author 陈妙威
     */
    protected function getValueFromObject($object)
    {
        if ($this->hasValue) {
            return $this->value;
        } else {
            return $this->getDefaultValue();
        }
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    protected function getValueExistsInRequest(AphrontRequest $request, $key)
    {
        return $this->getHTTPParameterValueExists($request, $key);
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return null
     * @author 陈妙威
     */
    protected function getValueFromRequest(AphrontRequest $request, $key)
    {
        return $this->getHTTPParameterValue($request, $key);
    }

    /**
     * @param PhabricatorEditField $other
     * @return $this
     * @author 陈妙威
     */
    public function readValueFromField(PhabricatorEditField $other)
    {
        $this->value = $this->getValueFromField($other);
        return $this;
    }

    /**
     * @param PhabricatorEditField $other
     * @return mixed
     * @author 陈妙威
     */
    protected function getValueFromField(PhabricatorEditField $other)
    {
        return $other->getValue();
    }


    /**
     * Read and return the value the object had when the user first loaded the
     * form.
     *
     * This is the initial value from the user's point of view when they started
     * the edit process, and used primarily to prevent race conditions for fields
     * like "Projects" and "Subscribers" that use tokenizers and support edge
     * transactions.
     *
     * Most fields do not need to store these values or deal with initial value
     * handling.
     *
     * @param AphrontRequest $request
     * @return wild Value read from request.
     */
    protected function getInitialValueFromSubmit(AphrontRequest $request, $key)
    {
        return null;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getInitialValue()
    {
        return $this->initialValue;
    }

    /**
     * @param $initial_value
     * @return $this
     * @author 陈妙威
     */
    public function setInitialValue($initial_value)
    {
        $this->initialValue = $initial_value;
        return $this;
    }


    /**
     * @param AphrontRequest $request
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    protected function getValueExistsInSubmit(AphrontRequest $request, $key)
    {
        return $this->getHTTPParameterValueExists($request, $key);
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return null
     * @author 陈妙威
     */
    protected function getValueFromSubmit(AphrontRequest $request, $key)
    {
        return $this->getHTTPParameterValue($request, $key);
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    protected function getHTTPParameterValueExists(AphrontRequest $request, $key)
    {
        $type = $this->getHTTPParameterType();

        if ($type) {
            return $type->getExists($request, $key);
        }

        return false;
    }

    /**
     * @param $request
     * @param $key
     * @return null
     * @author 陈妙威
     */
    protected function getHTTPParameterValue($request, $key)
    {
        $type = $this->getHTTPParameterType();

        if ($type) {
            return $type->getValue($request, $key);
        }

        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function getDefaultValue()
    {
        $type = $this->getHTTPParameterType();
        if ($type) {
            return $type->getDefaultValue();
        }
        return null;
    }

    /**
     * @return AphrontStringHTTPParameterType|null
     * @author 陈妙威
     */
    final public function getHTTPParameterType()
    {
        if (!$this->getIsFormField()) {
            return null;
        }

        $type = $this->newHTTPParameterType();

        if ($type) {
            $type->setViewer($this->getViewer());
        }

        return $type;
    }

    /**
     * @return AphrontStringHTTPParameterType
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        return new AphrontStringHTTPParameterType();
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function getBulkParameterType()
    {
        $type = $this->newBulkParameterType();
        if (!$type) {
            return null;
        }

        $type
            ->setField($this)
            ->setViewer($this->getViewer());

        return $type;
    }

    /**
     * @return BulkParameterType
     * @author 陈妙威
     */
    protected function newBulkParameterType()
    {
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getConduitParameterType()
    {
        $type = $this->newConduitParameterType();

        if (!$type) {
            return null;
        }

        $type->setViewer($this->getViewer());

        return $type;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newConduitParameterType();

    /**
     * @param $edit_type_key
     * @return $this
     * @author 陈妙威
     */
    public function setEditTypeKey($edit_type_key)
    {
        $this->editTypeKey = $edit_type_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEditTypeKey()
    {
        if ($this->editTypeKey === null) {
            return $this->getKey();
        }
        return $this->editTypeKey;
    }

    /**
     * @return PhabricatorSimpleEditType
     * @author 陈妙威
     */
    protected function newEditType()
    {
        return new PhabricatorSimpleEditType();
    }

    /**
     * @return PhabricatorSimpleEditType|null
     * @author 陈妙威
     */
    public function getEditType()
    {
        $transaction_type = $this->getTransactionType();
        if ($transaction_type === null) {
            return null;
        }

        $edit_type = $this->newEditType();
        if (!$edit_type) {
            return null;
        }

        $type_key = $this->getEditTypeKey();

        $edit_type
            ->setEditField($this)
            ->setTransactionType($transaction_type)
            ->setEditType($type_key)
            ->setMetadata($this->getMetadata());

        if (!$edit_type->getConduitParameterType()) {
            $conduit_parameter = $this->getConduitParameterType();
            if ($conduit_parameter) {
                $edit_type->setConduitParameterType($conduit_parameter);
            }
        }

        if (!$edit_type->getBulkParameterType()) {
            $bulk_parameter = $this->getBulkParameterType();
            if ($bulk_parameter) {
                $edit_type->setBulkParameterType($bulk_parameter);
            }
        }

        return $edit_type;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getConduitEditTypes()
    {
        if ($this->conduitEditTypes === null) {
            $edit_types = $this->newConduitEditTypes();
            $edit_types = mpull($edit_types, null, 'getEditType');
            $this->conduitEditTypes = $edit_types;
        }

        return $this->conduitEditTypes;
    }

    /**
     * @param $key
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    final public function getConduitEditType($key)
    {
        $edit_types = $this->getConduitEditTypes();

        if (empty($edit_types[$key])) {
            throw new Exception(
                \Yii::t("app",
                    'This EditField does not provide a Conduit EditType with key "%s".',
                    $key));
        }

        return $edit_types[$key];
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function newConduitEditTypes()
    {
        $edit_type = $this->getEditType();

        if (!$edit_type) {
            return array();
        }

        return array($edit_type);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getBulkEditTypes()
    {
        if ($this->bulkEditTypes === null) {
            $edit_types = $this->newBulkEditTypes();
            $edit_types = mpull($edit_types, null, 'getEditType');
            $this->bulkEditTypes = $edit_types;
        }

        return $this->bulkEditTypes;
    }

    /**
     * @param $key
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    final public function getBulkEditType($key)
    {
        $edit_types = $this->getBulkEditTypes();

        if (empty($edit_types[$key])) {
            throw new Exception(
                \Yii::t("app",
                    'This EditField does not provide a Bulk EditType with key "%s".',
                    $key));
        }

        return $edit_types[$key];
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function newBulkEditTypes()
    {
        $edit_type = $this->getEditType();

        if (!$edit_type) {
            return array();
        }

        return array($edit_type);
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getCommentAction()
    {
        $label = $this->getCommentActionLabel();
        if ($label === null) {
            return null;
        }

        $action = $this->newCommentAction();
        if ($action === null) {
            return null;
        }

        if ($this->hasCommentActionValue) {
            $value = $this->getCommentActionValue();
        } else {
            $value = $this->getValue();
        }

        $action
            ->setKey($this->getKey())
            ->setLabel($label)
            ->setValue($this->getValueForCommentAction($value))
            ->setOrder($this->getCommentActionOrder())
            ->setGroupKey($this->getCommentActionGroupKey());

        return $action;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function newCommentAction()
    {
        return null;
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    protected function getValueForCommentAction($value)
    {
        return $value;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldGenerateTransactionsFromSubmit()
    {
        if (!$this->getIsFormField()) {
            return false;
        }

        $edit_type = $this->getEditType();
        if (!$edit_type) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldReadValueFromRequest()
    {
        if (!$this->getIsFormField()) {
            return false;
        }

        if ($this->getIsLocked()) {
            return false;
        }

        if ($this->getIsHidden()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldReadValueFromSubmit()
    {
        if (!$this->getIsFormField()) {
            return false;
        }

        if ($this->getIsLocked()) {
            return false;
        }

        if ($this->getIsHidden()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldGenerateTransactionsFromComment()
    {
        if (!$this->getCommentActionLabel()) {
            return false;
        }

        if ($this->getIsLocked()) {
            return false;
        }

        if ($this->getIsHidden()) {
            return false;
        }

        return true;
    }

    /**
     * @param PhabricatorApplicationTransaction $template
     * @param array $spec
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    public function generateTransactions(PhabricatorApplicationTransaction $template, array $spec)
    {

        $edit_type = $this->getEditType();
        if (!$edit_type) {
            throw new Exception(
                \Yii::t("app",
                    'EditField (with key "{0}", of class "{1}") is generating ' .
                    'transactions, but has no EditType.',
                    [
                        $this->getKey(),
                        get_class($this)
                    ]));
        }

        return $edit_type->generateTransactions($template, $spec);
    }

}
