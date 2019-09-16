<?php

namespace orangins\modules\rbac\models;

use Yii;

/**
 * This is the model class for table "rbac_user".
 *
 * @property int $id
 * @property string $user_phid
 * @property string $object_phid
 * @property int $created_at
 * @property int $updated_at
 */
class RbacUser extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'rbac_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_phid', 'object_phid'], 'required'],
            [['created_at', 'updated_at'], 'integer'],
            [['user_phid', 'object_phid'], 'string', 'max' => 64],
            [['object_phid', 'user_phid'], 'unique', 'targetAttribute' => ['object_phid', 'user_phid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_phid' => Yii::t('app', 'User Phid'),
            'object_phid' => Yii::t('app', 'Object Phid'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
