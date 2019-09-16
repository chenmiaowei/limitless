<?php

namespace orangins\modules\conpherence\models;

use Yii;

/**
 * This is the model class for table "conpherence_transaction_comment".
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
 * @property int $created_at
 * @property int $updated_at
 */
class ConpherenceTransactionComment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conpherence_transaction_comment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phid', 'author_phid', 'view_policy', 'edit_policy', 'content', 'content_source', 'is_deleted'], 'required'],
            [['comment_version', 'is_deleted', 'created_at', 'updated_at'], 'integer'],
            [['content', 'content_source'], 'string'],
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
            'author_phid' => Yii::t('app', 'Author Phid'),
            'view_policy' => Yii::t('app', 'View Policy'),
            'edit_policy' => Yii::t('app', 'Edit Policy'),
            'comment_version' => Yii::t('app', 'Comment Version'),
            'content' => Yii::t('app', 'Content'),
            'content_source' => Yii::t('app', 'Content Source'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return ConpherenceTransactionCommentQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ConpherenceTransactionCommentQuery(get_called_class());
    }
}
