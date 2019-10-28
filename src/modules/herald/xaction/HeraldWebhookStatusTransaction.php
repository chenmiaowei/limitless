<?php

namespace orangins\modules\herald\xaction;

use orangins\modules\herald\models\HeraldWebhook;
use orangins\modules\herald\xaction\heraldwebhook\HeraldWebhookTransactionType;
use PhutilInvalidStateException;
use ReflectionException;

/**
 * Class HeraldWebhookStatusTransaction
 * @package orangins\modules\herald\xaction
 * @author 陈妙威
 */
final class HeraldWebhookStatusTransaction
    extends HeraldWebhookTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'status';

    /**
     * @param HeraldWebhook $object
     * @return string
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return $object->getStatus();
    }

    /**
     * @param HeraldWebhook $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setStatus($value);
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getTitle()
    {
        $old_value = $this->getOldValue();
        $new_value = $this->getNewValue();

        $old_status = HeraldWebhook::getDisplayNameForStatus($old_value);
        $new_status = HeraldWebhook::getDisplayNameForStatus($new_value);

        return pht(
            '%s changed hook status from %s to %s.',
            $this->renderAuthor(),
            $this->renderValue($old_status),
            $this->renderValue($new_status));
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        $old_value = $this->getOldValue();
        $new_value = $this->getNewValue();

        $old_status = HeraldWebhook::getDisplayNameForStatus($old_value);
        $new_status = HeraldWebhook::getDisplayNameForStatus($new_value);

        return pht(
            '%s changed %s from %s to %s.',
            $this->renderAuthor(),
            $this->renderObject(),
            $this->renderValue($old_status),
            $this->renderValue($new_status));
    }

    /**
     * @param HeraldWebhook $object
     * @param array $xactions
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        $errors = array();
        $viewer = $this->getActor();

        $options = HeraldWebhook::getStatusDisplayNameMap();

        foreach ($xactions as $xaction) {
            $new_value = $xaction->getNewValue();

            if (!isset($options[$new_value])) {
                $errors[] = $this->newInvalidError(
                    pht(
                        'Webhook status "%s" is not valid. Valid statuses are: %s.',
                        $new_value,
                        implode(', ', array_keys($options))),
                    $xaction);
            }
        }

        return $errors;
    }

}
