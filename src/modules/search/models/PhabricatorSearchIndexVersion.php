<?php

namespace orangins\modules\search\models;

use Yii;

/**
 * This is the model class for table "search_indexversion".
 *
 * @property int $id
 * @property string $object_phid
 * @property string $extension_key
 * @property string $version
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorSearchIndexVersion extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'search_indexversion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['extension_key', 'version'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['object_phid', 'extension_key'], 'string', 'max' => 64],
            [['version'], 'string', 'max' => 128],
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
            'extension_key' => Yii::t('app', 'Extension Key'),
            'version' => Yii::t('app', 'Version'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return SearchIndexversionQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SearchIndexversionQuery(get_called_class());
    }
}
