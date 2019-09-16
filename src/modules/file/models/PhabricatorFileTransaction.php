<?php

namespace orangins\modules\file\models;

use orangins\modules\file\phid\PhabricatorFileFilePHIDType;
use orangins\modules\file\query\PhabricatorFileTransactionQuery;
use orangins\modules\file\xaction\PhabricatorFileTransactionType;
use orangins\modules\transactions\models\PhabricatorModularTransaction;

/**
 * This is the model class for table "file_transaction".
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
class PhabricatorFileTransaction extends PhabricatorModularTransaction
{
    /**
     * @return string
     * @author 陈妙威
     */
    public static function tableName()
    {
        return "file_transaction";
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'file';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorFileFilePHIDType::TYPECONST;
    }

    /**
     * @return PhabricatorFileTransactionComment
     * @author 陈妙威
     */
    public function getApplicationTransactionCommentObject()
    {
        return new PhabricatorFileTransactionComment();
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getBaseTransactionClass()
    {
        return PhabricatorFileTransactionType::class;
    }

    /**
     * @return PhabricatorFileTransactionQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorFileTransactionQuery(get_called_class());
    }
}
