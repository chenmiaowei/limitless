<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/4
 * Time: 10:43 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\conduit\xaction;


use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\conduit\models\PhabricatorConduitToken;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorConduitTokenIPTransaction
 * @package orangins\modules\conduit\xaction
 * @author 陈妙威
 */
class PhabricatorConduitTokenIPTransaction  extends PhabricatorConduitTokenTransactionType
{
    /**
     *
     */
    const TRANSACTIONTYPE = 'conduittoken:ip';

    /**
     * @param ActiveRecord|PhabricatorConduitToken $object
     * @return mixed
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return ArrayHelper::getValue($object->getParameters(), 'ip', []);
    }

    /**
     * @param ActiveRecord|PhabricatorConduitToken $object
     * @param $value
     * @author 陈妙威
     * @throws \Exception
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setParameter('ip', $value);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        return  \Yii::t("app",
            '{0} updated ip of {1}.',
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
            '{0} updated ip of {1}.',
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