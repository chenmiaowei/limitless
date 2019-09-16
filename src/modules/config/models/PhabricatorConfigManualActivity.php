<?php

namespace orangins\modules\config\models;

use Yii;

/**
 * This is the model class for table "config_manualactivity".
 *
 * @property int $id
 * @property string $activity_type
 * @property string $parameters
 */
class PhabricatorConfigManualActivity extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'config_manualactivity';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['activity_type', 'parameters'], 'required'],
            [['parameters'], 'string'],
            [['activity_type'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'activity_type' => Yii::t('app', 'Activity Type'),
            'parameters' => Yii::t('app', 'Parameters'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return ConfigManualactivityQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ConfigManualactivityQuery(get_called_class());
    }
}
