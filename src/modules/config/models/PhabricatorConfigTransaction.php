<?php

namespace orangins\modules\config\models;

use orangins\modules\config\phid\PhabricatorConfigConfigPHIDType;
use orangins\modules\config\query\PhabricatorConfigTransactionQuery;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use Yii;

/**
 * This is the model class for table "config_transactions".
 *
 * @property int $id
 * @property string $phid
 * @property string $author_phid 作者
 * @property string $object_phid 对象
 * @property string $view_policy 显示权限
 * @property string $edit_policy 编辑权限
 * @property string $comment_phid 评论
 * @property int $comment_version 评论
 * @property string $transaction_type 交易类型
 * @property string $old_value 旧值
 * @property string $new_value 新值
 * @property string $metadata 额外数据
 * @property string $content_source 内容
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorConfigTransaction extends PhabricatorApplicationTransaction
{
    const TYPE_EDIT = 'config:edit';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'config_transactions';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'phid' => Yii::t('app', 'Phid'),
            'author_phid' => Yii::t('app', '作者'),
            'object_phid' => Yii::t('app', '对象'),
            'view_policy' => Yii::t('app', '显示权限'),
            'edit_policy' => Yii::t('app', '编辑权限'),
            'comment_phid' => Yii::t('app', '评论'),
            'comment_version' => Yii::t('app', '评论'),
            'transaction_type' => Yii::t('app', '交易类型'),
            'old_value' => Yii::t('app', '旧值'),
            'new_value' => Yii::t('app', '新值'),
            'metadata' => Yii::t('app', '额外数据'),
            'content_source' => Yii::t('app', '内容'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return PhabricatorConfigTransactionQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorConfigTransactionQuery(get_called_class());
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorConfigConfigPHIDType::TYPECONST;
    }
}
