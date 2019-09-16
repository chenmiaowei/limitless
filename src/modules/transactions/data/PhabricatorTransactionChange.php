<?php

namespace orangins\modules\transactions\data;

use orangins\lib\OranginsObject;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorTransactionChange
 * @package orangins\modules\transactions\data
 * @author 陈妙威
 */
abstract class PhabricatorTransactionChange extends OranginsObject
{

    /**
     * @var
     */
    private $transaction;
    /**
     * @var
     */
    private $oldValue;
    /**
     * @var
     */
    private $newValue;

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return $this
     * @author 陈妙威
     */
    final public function setTransaction(
        PhabricatorApplicationTransaction $xaction)
    {
        $this->transaction = $xaction;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * @param $old_value
     * @return $this
     * @author 陈妙威
     */
    final public function setOldValue($old_value)
    {
        $this->oldValue = $old_value;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getOldValue()
    {
        return $this->oldValue;
    }

    /**
     * @param $new_value
     * @return $this
     * @author 陈妙威
     */
    final public function setNewValue($new_value)
    {
        $this->newValue = $new_value;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getNewValue()
    {
        return $this->newValue;
    }

}
