<?php

namespace orangins\modules\search\models;

use Yii;

/**
 * This is the model class for table "search_documentrelationship".
 *
 * @property int $id
 * @property string $phid
 * @property string $related_phid
 * @property string $relation
 * @property string $related_type
 * @property int $related_time
 * @property string $created_at
 * @property string $updated_at
 */
class SearchDocumentrelationship extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'search_documentrelationship';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['related_phid', 'relation', 'related_type', 'related_time'], 'required'],
            [['related_time'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'related_phid'], 'string', 'max' => 64],
            [['relation', 'related_type'], 'string', 'max' => 4],
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
            'related_phid' => Yii::t('app', 'Related Phid'),
            'relation' => Yii::t('app', 'Relation'),
            'related_type' => Yii::t('app', 'Related Type'),
            'related_time' => Yii::t('app', 'Related Time'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return SearchDocumentrelationshipQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SearchDocumentrelationshipQuery(get_called_class());
    }
}
