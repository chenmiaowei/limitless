<?php

namespace orangins\lib\infrastructure\customfield\editor;

use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\request\AphrontRequest;
use orangins\lib\request\httpparametertype\AphrontHTTPParameterType;
use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\lib\view\form\control\AphrontFormControl;
use orangins\modules\conduit\parametertype\ConduitParameterType;
use orangins\modules\transactions\bulk\type\BulkParameterType;
use orangins\modules\transactions\commentaction\PhabricatorEditEngineCommentAction;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use orangins\modules\transactions\edittype\PhabricatorSimpleEditType;

/**
 * Class PhabricatorCustomFieldEditField
 * @package orangins\lib\infrastructure\customfield\editor
 * @author 陈妙威
 */
final class PhabricatorCustomFieldEditField
    extends PhabricatorEditField
{

    /**
     * @var
     */
    private $customField;
    /**
     * @var
     */
    private $httpParameterType;
    /**
     * @var
     */
    private $conduitParameterType;
    /**
     * @var
     */
    private $bulkParameterType;
    /**
     * @var
     */
    private $commentAction;

    /**
     * @param PhabricatorCustomField $custom_field
     * @return $this
     * @author 陈妙威
     */
    public function setCustomField(PhabricatorCustomField $custom_field)
    {
        $this->customField = $custom_field;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCustomField()
    {
        return $this->customField;
    }

    /**
     * @param AphrontHTTPParameterType $type
     * @return $this
     * @author 陈妙威
     */
    public function setCustomFieldHTTPParameterType(
        AphrontHTTPParameterType $type)
    {
        $this->httpParameterType = $type;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCustomFieldHTTPParameterType()
    {
        return $this->httpParameterType;
    }

    /**
     * @param ConduitParameterType $type
     * @return $this
     * @author 陈妙威
     */
    public function setCustomFieldConduitParameterType(
        ConduitParameterType $type)
    {
        $this->conduitParameterType = $type;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCustomFieldConduitParameterType()
    {
        return $this->conduitParameterType;
    }

    /**
     * @param BulkParameterType $type
     * @return $this
     * @author 陈妙威
     */
    public function setCustomFieldBulkParameterType(
        BulkParameterType $type)
    {
        $this->bulkParameterType = $type;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCustomFieldBulkParameterType()
    {
        return $this->bulkParameterType;
    }

    /**
     * @param PhabricatorEditEngineCommentAction $comment_action
     * @return $this
     * @author 陈妙威
     */
    public function setCustomFieldCommentAction(
        PhabricatorEditEngineCommentAction $comment_action)
    {
        $this->commentAction = $comment_action;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCustomFieldCommentAction()
    {
        return $this->commentAction;
    }

    /**
     * @return AphrontFormControl|null
     * @author 陈妙威
     */
    protected function buildControl()
    {
        if (!$this->getIsFormField()) {
            return null;
        }

        $field = $this->getCustomField();
        $clone = clone $field;

        $value = $this->getValue();
        $clone->setValueFromApplicationTransactions($value);

        return $clone->renderEditControl(array());
    }

    /**
     * @return PhabricatorSimpleEditType
     * @author 陈妙威
     */
    protected function newEditType()
    {
        return (new PhabricatorCustomFieldEditType())
            ->setCustomField($this->getCustomField());
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getValueForTransaction()
    {
        $value = $this->getValue();
        $field = $this->getCustomField();

        // Avoid changing the value of the field itself, since later calls would
        // incorrectly reflect the new value.
        $clone = clone $field;
        $clone->setValueFromApplicationTransactions($value);
        return $clone->getNewValueForApplicationTransactions();
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    protected function getValueForCommentAction($value)
    {
        $field = $this->getCustomField();
        $clone = clone $field;
        $clone->setValueFromApplicationTransactions($value);

        // TODO: This is somewhat bogus because only StandardCustomFields
        // implement a getFieldValue() method -- not all CustomFields. Today,
        // only StandardCustomFields can ever actually generate a comment action
        // so we never reach this method with other field types.

        return $clone->getFieldValue();
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    protected function getValueExistsInSubmit(AphrontRequest $request, $key)
    {
        return true;
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return mixed|null
     * @author 陈妙威
     */
    protected function getValueFromSubmit(AphrontRequest $request, $key)
    {
        $field = $this->getCustomField();

        $clone = clone $field;

        $clone->readValueFromRequest($request);
        return $clone->getNewValueForApplicationTransactions();
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function newConduitEditTypes()
    {
        $field = $this->getCustomField();

        if (!$field->shouldAppearInConduitTransactions()) {
            return array();
        }

        return parent::newConduitEditTypes();
    }

    /**
     * @return mixed|AphrontStringHTTPParameterType|null
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        $type = $this->getCustomFieldHTTPParameterType();

        if ($type) {
            return clone $type;
        }

        return null;
    }

    /**
     * @return mixed|null
     * @author 陈妙威
     */
    protected function newCommentAction()
    {
        $action = $this->getCustomFieldCommentAction();

        if ($action) {
            return clone $action;
        }

        return null;
    }

    /**
     * @return mixed|null
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        $type = $this->getCustomFieldConduitParameterType();

        if ($type) {
            return clone $type;
        }

        return null;
    }

    /**
     * @return mixed|BulkParameterType|null
     * @author 陈妙威
     */
    protected function newBulkParameterType()
    {
        $type = $this->getCustomFieldBulkParameterType();

        if ($type) {
            return clone $type;
        }

        return null;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAllReadValueFromRequestKeys()
    {
        $keys = array();

        // NOTE: This piece of complexity is so we can expose a reasonable key in
        // the UI ("custom.x") instead of a crufty internal key ("std:app:x").
        // Perhaps we can simplify this some day.

        // In the parent, this is just getKey(), but that returns a cumbersome
        // key in EditFields. Use the simpler edit type key instead.
        $keys[] = $this->getEditTypeKey();

        foreach ($this->getAliases() as $alias) {
            $keys[] = $alias;
        }

        return $keys;
    }

}
