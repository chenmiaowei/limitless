<?php

namespace orangins\modules\herald\xaction;


use orangins\modules\herald\models\HeraldRule;
use orangins\modules\herald\xaction\heraldrule\HeraldRuleTransactionType;

/**
 * Class HeraldRuleDisableTransaction
 * @package orangins\modules\herald\xaction
 * @author 陈妙威
 */
final class HeraldRuleDisableTransaction
    extends HeraldRuleTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'herald:disable';

    /**
     * @param HeraldRule $object
     * @return bool|void
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return (bool)$object->getIsDisabled();
    }

    /**
     * @param $object
     * @param $value
     * @return bool|mixed
     * @author 陈妙威
     */
    public function generateNewValue($object, $value)
    {
        return (bool)$value;
    }

    /**
     * @param HeraldRule $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setIsDisabled((int)$value);
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getTitle()
    {
        if ($this->getNewValue()) {
            return pht(
                '%s disabled this rule.',
                $this->renderAuthor());
        } else {
            return pht(
                '%s enabled this rule.',
                $this->renderAuthor());
        }
    }

}
