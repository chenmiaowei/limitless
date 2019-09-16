<?php

namespace orangins\modules\file\models;

use orangins\modules\search\ngrams\PhabricatorSearchNgrams;
use Yii;

/**
 * This is the model class for table "file_filename_ngrams".
 *
 * @property int $id
 * @property int $object_id
 * @property string $ngram
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorFileNameNgrams extends PhabricatorSearchNgrams
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'file_filename_ngrams';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['object_id', 'ngram'], 'required'],
            [['object_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
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
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return FileFilenameNgramsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new FileFilenameNgramsQuery(get_called_class());
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getNgramKey()
    {
        return 'filename';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getColumnName()
    {
        return 'name';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'file';
    }
}
