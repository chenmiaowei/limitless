<?php

namespace orangins\modules\transactions\edittype;

use orangins\lib\OranginsObject;
use orangins\modules\conduit\parametertype\ConduitParameterType;
use orangins\modules\transactions\bulk\type\BulkParameterType;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use Exception;

/**
 * Class PhabricatorEditType
 * @package orangins\modules\transactions\edittype
 * @author 陈妙威
 */
abstract class PhabricatorEditType extends OranginsObject
{

    /**
     * @var
     */
    private $editType;
    /**
     * @var
     */
    private $editField;
    /**
     * @var
     */
    private $transactionType;
    /**
     * @var
     */
    private $label;
    /**
     * @var array
     */
    private $metadata = array();

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
    private $conduitParameterType;

    /**
     * @var
     */
    private $bulkParameterType;
    /**
     * @var
     */
    private $bulkEditLabel;
    /**
     * @var
     */
    private $bulkEditGroupKey;

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
        if ($this->bulkEditLabel !== null) {
            return $this->bulkEditLabel;
        }

        return $this->getEditField()->getBulkEditLabel();
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
        if ($this->bulkEditGroupKey !== null) {
            return $this->bulkEditGroupKey;
        }

        return $this->getEditField()->getBulkEditGroupKey();
    }

    /**
     * @param $edit_type
     * @return static
     * @author 陈妙威
     */
    public function setEditType($edit_type)
    {
        $this->editType = $edit_type;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEditType()
    {
        return $this->editType;
    }

    /**
     * @param $metadata
     * @return $this
     * @author 陈妙威
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
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
     * @param $transaction_type
     * @return $this
     * @author 陈妙威
     */
    public function setTransactionType($transaction_type)
    {
        $this->transactionType = $transaction_type;
        return $this;
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
     * @param PhabricatorApplicationTransaction $template
     * @param array $spec
     * @return mixed
     * @author 陈妙威
     */
    abstract public function generateTransactions(
        PhabricatorApplicationTransaction $template,
        array $spec);

    /**
     * @param PhabricatorApplicationTransaction $template
     * @return mixed
     * @author 陈妙威
     */
    protected function newTransaction(
        PhabricatorApplicationTransaction $template)
    {
        /** @var PhabricatorApplicationTransaction $xaction */
        $phabricatorApplicationTransaction = clone $template;
        $xaction = $phabricatorApplicationTransaction
            ->setTransactionType($this->getTransactionType());

        foreach ($this->getMetadata() as $key => $value) {
            $xaction->setMetadataValue($key, $value);
        }

        return $xaction;
    }

    /**
     * @param PhabricatorEditField $edit_field
     * @return $this
     * @author 陈妙威
     */
    public function setEditField(PhabricatorEditField $edit_field)
    {
        $this->editField = $edit_field;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEditField()
    {
        return $this->editField;
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    protected function getTransactionValueFromValue($value)
    {
        return $value;
    }


    /* -(  Bulk  )--------------------------------------------------------------- */


    /**
     * @return null
     * @author 陈妙威
     */
    protected function newBulkParameterType()
    {
        if ($this->bulkParameterType) {
            return clone $this->bulkParameterType;
        }

        return null;
    }


    /**
     * @param BulkParameterType $type
     * @return $this
     * @author 陈妙威
     */
    public function setBulkParameterType(BulkParameterType $type)
    {
        $this->bulkParameterType = $type;
        return $this;
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
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function getTransactionValueFromBulkEdit($value)
    {
        return $this->getTransactionValueFromValue($value);
    }


    /* -(  Conduit  )------------------------------------------------------------ */


    /**
     * @return null
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        if ($this->conduitParameterType) {
            return clone $this->conduitParameterType;
        }

        return null;
    }

    /**
     * @param ConduitParameterType $type
     * @return $this
     * @author 陈妙威
     */
    public function setConduitParameterType(ConduitParameterType $type)
    {
        $this->conduitParameterType = $type;
        return $this;
    }

    /**
     * @return ConduitParameterType
     * @author 陈妙威
     */
    public function getConduitParameterType()
    {
        return $this->newConduitParameterType();
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    public function getConduitType()
    {
        $parameter_type = $this->getConduitParameterType();
        if (!$parameter_type) {
            throw new Exception(
                \Yii::t("app",
                    'Edit type (with key "%s") is missing a Conduit parameter type.',
                    $this->getEditType()));
        }

        return $parameter_type->getTypeName();
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
     * @return null
     * @author 陈妙威
     */
    public function getConduitTypeDescription()
    {
        if ($this->conduitTypeDescription === null) {
            if ($this->getEditField()) {
                return $this->getEditField()->getConduitTypeDescription();
            }
        }

        return $this->conduitTypeDescription;
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
     * @return null
     * @author 陈妙威
     */
    public function getConduitDescription()
    {
        if ($this->conduitDescription === null) {
            if ($this->getEditField()) {
                return $this->getEditField()->getConduitDescription();
            }
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
     * @return null
     * @author 陈妙威
     */
    public function getConduitDocumentation()
    {
        if ($this->conduitDocumentation === null) {
            if ($this->getEditField()) {
                return $this->getEditField()->getConduitDocumentation();
            }
        }

        return $this->conduitDocumentation;
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function getTransactionValueFromConduit($value)
    {
        return $this->getTransactionValueFromValue($value);
    }

}
