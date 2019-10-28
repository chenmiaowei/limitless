<?php


namespace orangins\modules\herald\xaction\heraldrule;


use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;
use Yii;

class HeraldRuleMustMatchAllTransactionType  extends HeraldRuleTransactionType
{
    /**
     *
     */
    const TRANSACTIONTYPE = 'herald_rule:must_match_all';

    /**
     * @param ActiveRecordPHID $object
     * @return mixed
     */
    public function generateOldValue($object)
    {
        return $object->getAttribute("must_match_all");
    }

    /**
     * @param ActiveRecordPHID $object
     * @param $value
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setAttribute("must_match_all", $value);
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
            $normalizeNumber = StringHelper::normalizeNumber($value);
            if (!preg_match('/^\s*[+-]?\d+\s*$/', $normalizeNumber)) {
                $errors[] = $this->newRequiredError(Yii::t("app", '{0} of {1} must be a Integer.', ['Must Match All', 'Herald Rule']));
            }
        }

        return $errors;
    }
}
