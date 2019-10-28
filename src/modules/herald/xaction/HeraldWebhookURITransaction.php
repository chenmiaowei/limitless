<?php

namespace orangins\modules\herald\xaction;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\modules\herald\models\HeraldWebhook;
use orangins\modules\herald\xaction\heraldwebhook\HeraldWebhookTransactionType;
use PhutilNumber;

/**
 * Class HeraldWebhookURITransaction
 * @package orangins\modules\herald\xaction
 * @author 陈妙威
 */
final class HeraldWebhookURITransaction
    extends HeraldWebhookTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'uri';

    /**
     * @param HeraldWebhook $object
     * @return mixed
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return $object->getWebhookURI();
    }

    /**
     * @param HeraldWebhook $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setWebhookURI($value);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTitle()
    {
        return pht(
            '%s changed the URI for this webhook from %s to %s.',
            $this->renderAuthor(),
            $this->renderOldValue(),
            $this->renderNewValue());
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        return pht(
            '%s changed the URI for %s from %s to %s.',
            $this->renderAuthor(),
            $this->renderObject(),
            $this->renderOldValue(),
            $this->renderNewValue());
    }

    /**
     * @param HeraldWebhook $object
     * @param array $xactions
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        $errors = array();
        $viewer = $this->getActor();

        if ($this->isEmptyTextTransaction($object->getName(), $xactions)) {
            $errors[] = $this->newRequiredError(
                pht('Webhooks must have a URI.'));
            return $errors;
        }

        $max_length = $object->getColumnMaximumByteLength('webhookURI');
        foreach ($xactions as $xaction) {
            $old_value = $this->generateOldValue($object);
            $new_value = $xaction->getNewValue();

            $new_length = strlen($new_value);
            if ($new_length > $max_length) {
                $errors[] = $this->newInvalidError(
                    pht(
                        'Webhook URIs can be no longer than %s characters.',
                        new PhutilNumber($max_length)),
                    $xaction);
            }

            try {
                PhabricatorEnv::requireValidRemoteURIForFetch(
                    $new_value,
                    array(
                        'http',
                        'https',
                    ));
            } catch (Exception $ex) {
                $errors[] = $this->newInvalidError(
                    $ex->getMessage(),
                    $xaction);
            }
        }

        return $errors;
    }

}
