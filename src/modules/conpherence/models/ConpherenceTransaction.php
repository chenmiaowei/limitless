<?php

namespace orangins\modules\conpherence\models;

use Yii;

/**
 * This is the model class for table "conpherence_transaction".
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
 * @property int $created_at
 * @property int $updated_at
 */
class ConpherenceTransaction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conpherence_transaction';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phid', 'object_phid', 'transaction_type', 'old_value', 'new_value', 'content_source', 'metadata', 'author_phid', 'view_policy', 'edit_policy'], 'required'],
            [['comment_version', 'created_at', 'updated_at'], 'integer'],
            [['old_value', 'new_value', 'content_source', 'metadata'], 'string'],
            [['phid', 'object_phid', 'comment_phid', 'author_phid', 'view_policy', 'edit_policy'], 'string', 'max' => 64],
            [['transaction_type'], 'string', 'max' => 32],
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
            'object_phid' => Yii::t('app', 'Object Phid'),
            'comment_phid' => Yii::t('app', 'Comment Phid'),
            'comment_version' => Yii::t('app', 'Comment Version'),
            'transaction_type' => Yii::t('app', 'Transaction Type'),
            'old_value' => Yii::t('app', 'Old Value'),
            'new_value' => Yii::t('app', 'New Value'),
            'content_source' => Yii::t('app', 'Content Source'),
            'metadata' => Yii::t('app', 'Metadata'),
            'author_phid' => Yii::t('app', 'Author Phid'),
            'view_policy' => Yii::t('app', 'View Policy'),
            'edit_policy' => Yii::t('app', 'Edit Policy'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return ConpherenceTransactionQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ConpherenceTransactionQuery(get_called_class());
    }
}
