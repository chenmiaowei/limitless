<?php

namespace orangins\modules\auth\models;

use orangins\modules\auth\phid\PhabricatorAuthPasswordPHIDType;
use orangins\modules\auth\xaction\PhabricatorAuthPasswordTransactionType;
use orangins\modules\transactions\models\PhabricatorModularTransaction;
use Yii;

/**
 * This is the model class for table "auth_passwordtransaction".
 *
 * @property int $id
 * @property string $phid
 * @property string $object_phid 对象_id
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
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorAuthPasswordTransaction extends PhabricatorModularTransaction
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_passwordtransaction';
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorAuthPasswordPHIDType::TYPECONST;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBaseTransactionClass()
    {
       return PhabricatorAuthPasswordTransactionType::class;
    }
}
