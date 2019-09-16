<?php

namespace orangins\modules\transactions\edittype;

use orangins\lib\helpers\OranginsUtil;
use orangins\modules\transactions\bulk\type\BulkTokenizerParameterType;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorEdgeEditType
 * @package orangins\modules\transactions\edittype
 * @author 陈妙威
 */
final class PhabricatorEdgeEditType extends PhabricatorPHIDListEditType
{

    /**
     * @var
     */
    private $edgeOperation;
    /**
     * @var
     */
    private $valueDescription;

    /**
     * @param $edge_operation
     * @return $this
     * @author 陈妙威
     */
    public function setEdgeOperation($edge_operation)
    {
        $this->edgeOperation = $edge_operation;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEdgeOperation()
    {
        return $this->edgeOperation;
    }

    /**
     * @param PhabricatorApplicationTransaction $template
     * @param array $spec
     * @return array|mixed
     * @author 陈妙威
     */
    public function generateTransactions(
        PhabricatorApplicationTransaction $template,
        array $spec)
    {

        $value = ArrayHelper::getValue($spec, 'value');

        if ($this->getEdgeOperation() !== null) {
            $value = array_fuse($value);
            $value = array(
                $this->getEdgeOperation() => $value,
            );
        }

        $xaction = $this->newTransaction($template)
            ->setNewValue($value);

        return array($xaction);
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function newBulkParameterType()
    {
        if (!$this->getDatasource()) {
            return null;
        }

        return (new BulkTokenizerParameterType())
            ->setDatasource($this->getDatasource());
    }

}
