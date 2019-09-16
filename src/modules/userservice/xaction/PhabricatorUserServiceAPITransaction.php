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

class PhabricatorUserServiceAPITransaction  extends PhabricatorUserServiceTransactionType
{
    /**
     *
     */
    const TRANSACTIONTYPE = 'userservice:apis';

    /**
     * @param ActiveRecord|PhabricatorUserService $object
     * @return mixed
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return ArrayHelper::getValue($object->getParameters(), 'apis', []);
    }

    /**
     * @param ActiveRecord|PhabricatorUserService $object
     * @param $value
     * @author 陈妙威
     * @throws \Exception
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setParameter('apis', $value);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        return  \Yii::t("app",
            '{0} updated apis of {1}.',
            [
                $this->renderAuthor(),
                $this->renderObject()
            ]);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        return  \Yii::t("app",
            '{0} updated apis of {1}.',
            [
                $this->renderAuthor(),
                $this->renderObject()
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