<?php

namespace orangins\modules\metamta\models;

use Yii;

/**
 * This is the model class for table "metamta_mailproperties".
 *
 * @property int $id
 * @property string $object_phid
 * @property string $mail_properties
 * @property string $created_at
 * @property string $updated_at
 */
class MetamtaMailproperties extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'metamta_mailproperties';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['object_phid', 'mail_properties'], 'required'],
            [['mail_properties'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['object_phid'], 'string', 'max' => 64],
            [['object_phid'], 'unique'],
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
            'mail_properties' => Yii::t('app', 'Mail Properties'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
