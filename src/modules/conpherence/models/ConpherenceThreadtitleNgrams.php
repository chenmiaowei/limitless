<?php

namespace orangins\modules\conpherence\models;

use Yii;

/**
 * This is the model class for table "conpherence_threadtitle_ngrams".
 *
 * @property int $id
 * @property int $object_id
 * @property string $ngram
 */
class ConpherenceThreadtitleNgrams extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conpherence_threadtitle_ngrams';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['object_id', 'ngram'], 'required'],
            [['object_id'], 'integer'],
            [['ngram'], 'string', 'max' => 3],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'object_id' => Yii::t('app', 'Object ID'),
            'ngram' => Yii::t('app', 'Ngram'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return ConpherenceThreadtitleNgramsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ConpherenceThreadtitleNgramsQuery(get_called_class());
    }
}
