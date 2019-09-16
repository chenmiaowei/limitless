<?php

namespace orangins\modules\userservice\models;

use orangins\modules\transactions\models\PhabricatorModularTransaction;
use orangins\modules\userservice\phid\PhabricatorUserServicesUserServicePHIDType;
use orangins\modules\userservice\xaction\PhabricatorUserServiceTransactionType;
use Yii;

/**
 * This is the model class for table "userservice_transactions".
 *
 * @property int $id
 * @property string $phid
 * @property string $object_phid 对象ID
 * @property string $comment_phid 评论
 * @property int $comment_version 评论版本
 * @property string $transaction_type 类型
 * @property string $old_value 旧值
 * @property string $new_value 新值
 * @property string $content_source 内容
 * @property string $metadata 数据
 * @property string $author_phid 作者
 * @property string $view_policy 显示权限
 * @property string $edit_policy 编辑权限
 * @property int $created_at
 * @property int $updated_at
 */
class PhabricatorUserServiceTransaction  extends PhabricatorModularTransaction
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'userservice_transactions';
    }


    /**
     * {@inheritdoc}
     * @return UserserviceTransactionsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UserserviceTransactionsQuery(get_called_class());
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorUserServicesUserServicePHIDType::TYPECONST;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBaseTransactionClass()
    {
        return PhabricatorUserServiceTransactionType::className();
    }
}
