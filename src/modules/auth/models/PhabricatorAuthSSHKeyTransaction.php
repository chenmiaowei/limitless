<?php

namespace orangins\modules\auth\models;

use orangins\modules\auth\phid\PhabricatorAuthSSHKeyPHIDType;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * This is the model class for table "auth_sshkeytransaction".
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
class PhabricatorAuthSSHKeyTransaction  extends PhabricatorApplicationTransaction
{
    const TYPE_NAME = 'sshkey.name';
    const TYPE_KEY = 'sshkey.key';
    const TYPE_DEACTIVATE = 'sshkey.deactivate';


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_sshkeytransaction';
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorAuthSSHKeyPHIDType::TYPECONST;
    }

}
