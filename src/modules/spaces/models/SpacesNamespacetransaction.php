<?php

namespace orangins\modules\spaces\models;

use Yii;

/**
 * This is the model class for table "spaces_namespacetransaction".
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
class SpacesNamespacetransaction extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'spaces_namespacetransaction';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phid', 'object_phid', 'transaction_type', 'old_value', 'new_value', 'content_source', 'metadata', 'author_phid', 'view_policy', 'edit_policy'], 'required'],
            [['comment_version'], 'integer'],
            [['old_value', 'new_value', 'content_source', 'metadata'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
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
            'object_phid' => Yii::t('app', '对象ID'),
            'comment_phid' => Yii::t('app', '评论'),
            'comment_version' => Yii::t('app', '评论版本'),
            'transaction_type' => Yii::t('app', '类型'),
            'old_value' => Yii::t('app', '旧值'),
            'new_value' => Yii::t('app', '新值'),
            'content_source' => Yii::t('app', '内容'),
            'metadata' => Yii::t('app', '数据'),
            'author_phid' => Yii::t('app', '作者'),
            'view_policy' => Yii::t('app', '显示权限'),
            'edit_policy' => Yii::t('app', '编辑权限'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
