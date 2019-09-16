<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\helpers\OranginsUtil;
use orangins\lib\request\AphrontRequest;
use orangins\lib\request\httpparametertype\AphrontPHIDListHTTPParameterType;
use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\modules\conduit\parametertype\ConduitPHIDListParameterType;
use orangins\modules\conduit\parametertype\ConduitPHIDParameterType;
use orangins\modules\transactions\edittype\PhabricatorDatasourceEditType;
use orangins\modules\transactions\edittype\PhabricatorEdgeEditType;

/**
 * Class PhabricatorPHIDListEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
abstract class PhabricatorPHIDListEditField extends PhabricatorEditField
{

    /**
     * @var
     */
    private $useEdgeTransactions;
    /**
     * @var
     */
    private $isSingleValue;
    /**
     * @var
     */
    private $isNullable;

    /**
     * @param $use_edge_transactions
     * @return $this
     * @author 陈妙威
     */
    public function setUseEdgeTransactions($use_edge_transactions)
    {
        $this->useEdgeTransactions = $use_edge_transactions;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getUseEdgeTransactions()
    {
        return $this->useEdgeTransactions;
    }

    /**
     * @param $value
     * @return PhabricatorPHIDListEditField
     * @author 陈妙威
     */
    public function setSingleValue($value)
    {
        if ($value === null) {
            $value = array();
        } else {
            $value = array($value);
        }

        $this->isSingleValue = true;
        return $this->setValue($value);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsSingleValue()
    {
        return $this->isSingleValue;
    }

    /**
     * @param $is_nullable
     * @return $this
     * @author 陈妙威
     */
    public function setIsNullable($is_nullable)
    {
        $this->isNullable = $is_nullable;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsNullable()
    {
        return $this->isNullable;
    }

    /**
     * @return AphrontPHIDListHTTPParameterType|AphrontStringHTTPParameterType
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        return new AphrontPHIDListHTTPParameterType();
    }

    /**
     * @return ConduitPHIDListParameterType|mixed
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        if ($this->getIsSingleValue()) {
            return (new ConduitPHIDParameterType())
                ->setIsNullable($this->getIsNullable());
        } else {
            return new ConduitPHIDListParameterType();
        }
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return array|null
     * @author 陈妙威
     */
    protected function getValueFromRequest(AphrontRequest $request, $key)
    {
        $value = parent::getValueFromRequest($request, $key);
        if ($this->getIsSingleValue()) {
            $value = array_slice($value, 0, 1);
        }
        return $value;
    }

    /**
     * @return array|mixed|null
     * @author 陈妙威
     */
    public function getValueForTransaction()
    {
        $new = parent::getValueForTransaction();
        if ($this->getIsSingleValue()) {
            if ($new) {
                return head($new);
            } else {
                return null;
            }
        }

        if (!$this->getUseEdgeTransactions()) {
            return $new;
        }

        $old = $this->getInitialValue();
        if ($old === null) {
            return array(
                '=' => array_fuse($new),
            );
        }

        // If we're building an edge transaction and the request has data about the
        // original value the user saw when they loaded the form, interpret the
        // edit as a mixture of "+" and "-" operations instead of a single "="
        // operation. This limits our exposure to race conditions by making most
        // concurrent edits merge correctly.

        $add = array_diff($new, $old);
        $rem = array_diff($old, $new);

        $value = array();

        if ($add) {
            $value['+'] = array_fuse($add);
        }
        if ($rem) {
            $value['-'] = array_fuse($rem);
        }

        return $value;
    }

    /**
     * @return PhabricatorDatasourceEditType|PhabricatorEdgeEditType
     * @author 陈妙威
     */
    protected function newEditType()
    {
        if ($this->getUseEdgeTransactions()) {
            return new PhabricatorEdgeEditType();
        }

        return (new PhabricatorDatasourceEditType())
            ->setIsSingleValue($this->getIsSingleValue())
            ->setIsNullable($this->getIsNullable());
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function newBulkEditTypes()
    {
        return $this->newConduitEditTypes();
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function newConduitEditTypes()
    {
        if (!$this->getUseEdgeTransactions()) {
            return parent::newConduitEditTypes();
        }

        $transaction_type = $this->getTransactionType();
        if ($transaction_type === null) {
            return array();
        }

        $type_key = $this->getEditTypeKey();

        $base = $this->getEditType();

        $phabricatorSimpleEditType = clone $base;
        $add = $phabricatorSimpleEditType
            ->setEditType($type_key . '.add')
            ->setEdgeOperation('+')
            ->setConduitTypeDescription(\Yii::t("app", 'List of PHIDs to add.'))
            ->setConduitParameterType($this->getConduitParameterType());

        $phabricatorSimpleEditType1 = clone $base;
        $rem = $phabricatorSimpleEditType1
            ->setEditType($type_key . '.remove')
            ->setEdgeOperation('-')
            ->setConduitTypeDescription(\Yii::t("app", 'List of PHIDs to remove.'))
            ->setConduitParameterType($this->getConduitParameterType());

        $phabricatorSimpleEditType2 = clone $base;
        $set = $phabricatorSimpleEditType2
            ->setEditType($type_key . '.set')
            ->setEdgeOperation('=')
            ->setConduitTypeDescription(\Yii::t("app", 'List of PHIDs to set.'))
            ->setConduitParameterType($this->getConduitParameterType());

        return array(
            $add,
            $rem,
            $set,
        );
    }

}
