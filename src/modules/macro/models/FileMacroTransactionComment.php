<?php

namespace orangins\modules\macro\models;

use Yii;

/**
 * This is the model class for table "file_macro_transaction_comment".
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
class FileMacroTransactionComment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'file_macro_transaction_comment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phid', 'author_phid', 'view_policy', 'edit_policy', 'content', 'content_source', 'is_deleted'], 'required'],
            [['comment_version', 'is_deleted'], 'integer'],
            [['content', 'content_source'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'transaction_phid', 'author_phid', 'view_policy', 'edit_policy'], 'string', 'max' => 64],
            [['phid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'phid' => Yii::t('app', 'Phid'),
            'transaction_phid' => Yii::t('app', 'Transaction Phid'),
            'author_phid' => Yii::t('app', '作者'),
            'view_policy' => Yii::t('app', '显示权限'),
            'edit_policy' => Yii::t('app', '编辑权限'),
            'comment_version' => Yii::t('app', '评论版本'),
            'content' => Yii::t('app', 'Content'),
            'content_source' => Yii::t('app', 'Content Source'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return FileMacroTransactionCommentQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new FileMacroTransactionCommentQuery(get_called_class());
    }
}
