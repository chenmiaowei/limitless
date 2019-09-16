<?php

namespace orangins\modules\search\models;

use Yii;

/**
 * This is the model class for table "search_document".
 *
 * @property int $id
 * @property string $phid
 * @property string $document_type
 * @property string $document_title
 * @property string $created_at
 * @property string $updated_at
 */
class SearchDocument extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'search_document';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['document_type', 'document_title'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid'], 'string', 'max' => 64],
            [['document_type'], 'string', 'max' => 4],
            [['document_title'], 'string', 'max' => 255],
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
            'document_type' => Yii::t('app', 'Document Type'),
            'document_title' => Yii::t('app', 'Document Title'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return SearchDocumentQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SearchDocumentQuery(get_called_class());
    }
}
