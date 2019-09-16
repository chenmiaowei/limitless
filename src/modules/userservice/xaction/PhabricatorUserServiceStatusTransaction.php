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

class PhabricatorUserServiceStatusTransaction  extends PhabricatorUserServiceTransactionType
{
    /**
     *
     */
    const TRANSACTIONTYPE = 'userservice:status';

    /**
     * @param ActiveRecord|PhabricatorUserService $object
     * @return mixed
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return $object->getAttribute("status");
    }

    /**
     * @param ActiveRecord|PhabricatorUserService $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setAttribute("status", $value);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        return \Yii::t("app",
            '{0} updated the status for this file from "{1}" to "{2}".',
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
            '{0} updated the status of {1} from "{2}" to "{3}".',
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
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        $errors = array();
        return $errors;
    }
}