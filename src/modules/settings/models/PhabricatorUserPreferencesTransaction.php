<?php

namespace orangins\modules\settings\models;

use orangins\modules\settings\phid\PhabricatorUserPreferencesPHIDType;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\phid\TransactionPHIDType;
use Yii;

/**
 * This is the model class for table "user_preferencestransaction".
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
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorUserPreferencesTransaction extends PhabricatorApplicationTransaction
{
    /**
     *
     */
    const TYPE_SETTING = 'setting';

    /**
     *
     */
    const PROPERTY_SETTING = 'setting.key';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_preferencestransaction';
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'user';
    }

    /**
     * @return null|void
     * @author 陈妙威
     */
    public function getApplicationTransactionCommentObject()
    {
        return null;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorUserPreferencesPHIDType::TYPECONST;
    }

}
