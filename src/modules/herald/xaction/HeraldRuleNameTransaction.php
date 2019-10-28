<?php

namespace orangins\modules\herald\xaction;

use orangins\modules\herald\models\HeraldRule;
use orangins\modules\herald\xaction\heraldrule\HeraldRuleTransactionType;
use PhutilNumber;

/**
 * Class HeraldRuleNameTransaction
 * @package orangins\modules\herald\xaction
 * @author 陈妙威
 */
final class HeraldRuleNameTransaction
    extends HeraldRuleTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'herald:name';

    /**
     * @param HeraldRule $object
     * @return string
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return $object->getName();
    }

    /**
     * @param HeraldRule $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setName($value);
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getTitle()
    {
        return pht(
            '%s renamed this rule from %s to %s.',
            $this->renderAuthor(),
            $this->renderOldValue(),
            $this->renderNewValue());
    }

    /**
     * @param HeraldRule $object
     * @param array $xactions
     * @return array
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        $errors = array();

        if ($this->isEmptyTextTransaction($object->getName(), $xactions)) {
            $errors[] = $this->newRequiredError(
                pht('Rules must have a name.'));
        }

        $max_length = $object->getColumnMaximumByteLength('name');
        foreach ($xactions as $xaction) {
            $new_value = $xaction->getNewValue();

            $new_length = strlen($new_value);
            if ($new_length > $max_length) {
                $errors[] = $this->newInvalidError(
                    pht(
                        'Rule names can be no longer than %s characters.',
                        new PhutilNumber($max_length)));
            }
        }

        return $errors;
    }

}
