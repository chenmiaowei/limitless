<?php

namespace orangins\modules\search\models;

use Yii;

/**
 * This is the model class for table "stopwords".
 *
 * @property int $id
 * @property string $value
 * @property string $created_at
 * @property string $updated_at
 */
class Stopwords extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stopwords';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['value'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['value'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'value' => Yii::t('app', 'Value'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return StopwordsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new StopwordsQuery(get_called_class());
    }
}
