<?php

namespace orangins\modules\search\models;

use orangins\modules\search\phidtype\PhabricatorProfileMenuItemPHIDType;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use Yii;

/**
 * This is the model class for table "search_profilepanelconfigurationtransaction".
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
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorProfileMenuItemConfigurationTransaction extends PhabricatorApplicationTransaction
{
    /**
     *
     */
    const TYPE_PROPERTY = 'profilepanel.property';
    /**
     *
     */
    const TYPE_ORDER = 'profilepanel.order';
    /**
     *
     */
    const TYPE_VISIBILITY = 'profilepanel.visibility';


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'search_profilepanelconfigurationtransaction';
    }


    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorProfileMenuItemPHIDType::TYPECONST;
    }

    /**
     * @return null|void
     * @author 陈妙威
     */
    public function getApplicationTransactionCommentObject()
    {
        return null;
    }

}
