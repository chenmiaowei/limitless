<?php

namespace orangins\modules\search\models;

use Yii;

/**
 * This is the model class for table "search_documentfield".
 *
 * @property int $id
 * @property string $phid
 * @property string $phid_type
 * @property string $field
 * @property string $aux_phid
 * @property string $corpus
 * @property string $stemmed_corpus
 * @property string $created_at
 * @property string $updated_at
 */
class SearchDocumentfield extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'search_documentfield';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phid_type', 'field'], 'required'],
            [['corpus', 'stemmed_corpus'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'aux_phid'], 'string', 'max' => 64],
            [['phid_type', 'field'], 'string', 'max' => 4],
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
            'phid_type' => Yii::t('app', 'Phid Type'),
            'field' => Yii::t('app', 'Field'),
            'aux_phid' => Yii::t('app', 'Aux Phid'),
            'corpus' => Yii::t('app', 'Corpus'),
            'stemmed_corpus' => Yii::t('app', 'Stemmed Corpus'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return SearchDocumentfieldQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SearchDocumentfieldQuery(get_called_class());
    }
}
