<?php

namespace orangins\modules\feed\models;

use Yii;

/**
 * This is the model class for table "feed_storyreference".
 *
 * @property int $id
 * @property string $object_phid
 * @property int $chronological_key
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorFeedStoryReference extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feed_storyreference';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['object_phid', 'chronological_key'], 'required'],
            [['chronological_key'], 'integer'],
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
            'chronological_key' => Yii::t('app', 'Chronological Key'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
