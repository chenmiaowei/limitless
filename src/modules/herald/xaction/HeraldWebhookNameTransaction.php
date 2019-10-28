<?php

namespace orangins\modules\herald\xaction;

use orangins\modules\herald\models\HeraldRule;
use orangins\modules\herald\xaction\heraldwebhook\HeraldWebhookTransactionType;
use PhutilInvalidStateException;
use PhutilJSONParserException;
use PhutilNumber;
use ReflectionException;

/**
 * Class HeraldWebhookNameTransaction
 * @package orangins\modules\herald\xaction
 * @author 陈妙威
 */
final class HeraldWebhookNameTransaction
    extends HeraldWebhookTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'name';

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
            '%s renamed this webhook from %s to %s.',
            $this->renderAuthor(),
            $this->renderOldValue(),
            $this->renderNewValue());
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        return pht(
            '%s renamed %s from %s to %s.',
            $this->renderAuthor(),
            $this->renderObject(),
            $this->renderOldValue(),
            $this->renderNewValue());
    }

    /**
     * @param HeraldRule $object
     * @param array $xactions
     * @return array
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        $errors = array();
        $viewer = $this->getActor();

        if ($this->isEmptyTextTransaction($object->getName(), $xactions)) {
            $errors[] = $this->newRequiredError(
                pht('Webhooks must have a name.'));
            return $errors;
        }

        $max_length = $object->getColumnMaximumByteLength('name');
        foreach ($xactions as $xaction) {
            $old_value = $this->generateOldValue($object);
            $new_value = $xaction->getNewValue();

            $new_length = strlen($new_value);
            if ($new_length > $max_length) {
                $errors[] = $this->newInvalidError(
                    pht(
                        'Webhook names can be no longer than %s characters.',
                        new PhutilNumber($max_length)));
            }
        }

        return $errors;
    }

}
