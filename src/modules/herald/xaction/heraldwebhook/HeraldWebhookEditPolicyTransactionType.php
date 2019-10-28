<?php


namespace orangins\modules\herald\xaction\heraldwebhook;


use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;
use Yii;

class HeraldWebhookEditPolicyTransactionType  extends HeraldWebhookTransactionType
{
    /**
     *
     */
    const TRANSACTIONTYPE = 'herald_webhook:edit_policy';

    /**
     * @param ActiveRecordPHID $object
     * @return mixed
     */
    public function generateOldValue($object)
    {
        return $object->getAttribute("edit_policy");
    }

    /**
     * @param ActiveRecordPHID $object
     * @param $value
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setAttribute("edit_policy", $value);
    }


    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction[] $xactions
     * @return array
     * @throws \ReflectionException
     * @throws \PhutilJSONParserException
     */
    public function validateTransactions($object, array $xactions)
    {
        $errors = array();

        foreach ($xactions as $xaction) {
            $value = $xaction->getNewValue();
            if (!strlen($value)) {
                $errors[] = $this->newRequiredError(Yii::t("app", '{0} of {1} must be a String.', ['Edit Policy', 'Herald Webhook']));
            }
        }
        return $errors;
    }
}
