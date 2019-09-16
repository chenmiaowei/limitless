<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/4
 * Time: 10:43 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\userservice\xaction;


use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\userservice\models\PhabricatorUserService;
use PhutilNumber;
use yii\helpers\ArrayHelper;

class PhabricatorUserServiceUserTransaction  extends PhabricatorUserServiceTransactionType
{
    /**
     *
     */
    const TRANSACTIONTYPE = 'userservice:user';

    /**
     * @param ActiveRecord|PhabricatorUserService $object
     * @return mixed
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return $object->getAttribute("user_phid");
    }

    /**
     * @param ActiveRecord|PhabricatorUserService $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setAttribute("user_phid", $value);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        return \Yii::t("app",
            '{0} updated the amount for this file from "{1}" to "{2}".',
            [
                $this->renderAuthor(),
                $this->renderOldValue(),
                $this->renderNewValue()
            ]);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        return \Yii::t("app",
            '{0} updated the amount of {1} from "{2}" to "{3}".',
            [
                $this->renderAuthor(),
                $this->renderObject(),
                $this->renderOldValue(),
                $this->renderNewValue()
            ]);
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        $errors = array();

        if ($this->isEmptyTextTransaction(ArrayHelper::getValue($object, 'amount'), $xactions)) {
            $errors[] = $this->newRequiredError(\Yii::t("app", 'Files must have a amount.'));
        }
        if (!preg_match("/^-?(?:\d+|\d*\.\d+)$/", ArrayHelper::getValue($object, 'amount'), $match)) {
            $errors[] = $this->newRequiredError(\Yii::t("app", '充值金额不是有效的数字'));
        }
        return $errors;
    }
}