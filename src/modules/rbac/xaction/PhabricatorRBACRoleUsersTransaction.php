<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/4
 * Time: 10:43 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\rbac\xaction;

use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\rbac\models\RbacRoleCapability;
use orangins\modules\rbac\models\RbacUser;
use orangins\modules\userservice\models\PhabricatorUserService;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorUserServiceAPITransaction
 * @package orangins\modules\rbac\xaction
 * @author 陈妙威
 */
class PhabricatorRBACRoleUsersTransaction extends PhabricatorRBACRoleTransactionType
{
    /**
     *
     */
    const TRANSACTIONTYPE = 'role:users';

    /**
     * @param ActiveRecord|PhabricatorUserService $object
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    public function generateOldValue($object)
    {
        $activeRecords = RbacUser::find()->andWhere(['object_phid' => $object->getPHID()])->all();
        return ArrayHelper::getColumn($activeRecords, 'user_phid', []);
    }

    /**
     * @param ActiveRecord|PhabricatorUserService $object
     * @param $value
     * @author 陈妙威
     * @throws \Exception
     */
    public function applyInternalEffects($object, $value)
    {
        $add = ArrayHelper::remove( $value, '+', []);
        $rem = ArrayHelper::remove( $value, '-', []);
        foreach ($add as $item) {
            $arr = [
                'object_phid' => $object->getPHID(),
                'user_phid' => $item,
            ];
            \Yii::$app->getDb()->createCommand()->upsert(RbacUser::tableName(), $arr, [
                'object_phid' => new \yii\db\Expression('VALUES(object_phid)'),
                'user_phid' => new \yii\db\Expression('VALUES(user_phid)'),
            ])->execute();
        }
        foreach ($rem as $item) {
            RbacUser::deleteAll([
                'object_phid' => $object->getPHID(),
                'user_phid' => $item
            ]);
        }
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        return \Yii::t("app",
            '{0} updated capabilities of {1}.',
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
        return \Yii::t("app",
            '{0} updated capabilities of {1}.',
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