<?php

namespace orangins\modules\meta\models;

use Yii;

/**
 * This is the model class for table "application".
 *
 * @property int $id
 * @property string $phid
 * @property int $created_at
 * @property int $updated_at
 */
class Application extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'application';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phid'], 'required'],
            [['created_at', 'updated_at'], 'integer'],
            [['phid'], 'string', 'max' => 64],
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
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
