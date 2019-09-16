<?php

namespace orangins\modules\rbac\models;

use Yii;

/**
 * This is the model class for table "rbac_role_capability".
 *
 * @property int $id
 * @property string $object_phid
 * @property string $capability
 * @property int $created_at
 * @property int $updated_at
 */
class RbacRoleCapability extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'rbac_role_capability';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['object_phid', 'capability'], 'required'],
            [['created_at', 'updated_at'], 'integer'],
            [['object_phid', 'capability'], 'string', 'max' => 64],
            [['object_phid', 'capability'], 'unique', 'targetAttribute' => ['object_phid', 'capability']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'object_phid' => Yii::t('app', 'Object Phid'),
            'capability' => Yii::t('app', 'Capability'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
