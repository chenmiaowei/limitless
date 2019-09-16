<?php

namespace orangins\modules\file\models;

use orangins\modules\transactions\models\PhabricatorApplicationTransactionComment;
use Yii;

/**
 * This is the model class for table "file_transaction_comment".
 *
 * @property int $id
 * @property string $phid
 * @property string $transaction_phid
 * @property string $author_phid 作者
 * @property string $view_policy 显示权限
 * @property string $edit_policy 编辑权限
 * @property int $comment_version 评论版本
 * @property string $content
 * @property string $content_source
 * @property int $is_deleted
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorFileTransactionComment extends PhabricatorApplicationTransactionComment
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'file_transaction_comment';
    }

    /**
     * {@inheritdoc}
     * @return FileTransactionCommentQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new FileTransactionCommentQuery(get_called_class());
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationTransactionObject()
    {
        return new PhabricatorFileTransaction();
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        // TODO: Implement getPHIDTypeClassName() method.
    }
}
