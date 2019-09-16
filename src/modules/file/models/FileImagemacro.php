<?php

namespace orangins\modules\file\models;

use Yii;

/**
 * This is the model class for table "file_imagemacro".
 *
 * @property int $id
 * @property string $phid
 * @property string $author_phid
 * @property string $file_phid
 * @property string $name
 * @property int $is_disabled
 * @property string $audio_phid
 * @property string $audio_behavior
 * @property string $mail_key
 * @property string $created_at
 * @property string $updated_at
 */
class FileImagemacro extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'file_imagemacro';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phid', 'file_phid', 'name', 'is_disabled', 'audio_behavior', 'mail_key'], 'required'],
            [['is_disabled'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'author_phid', 'file_phid', 'audio_phid', 'audio_behavior'], 'string', 'max' => 64],
            [['name'], 'string', 'max' => 128],
            [['mail_key'], 'string', 'max' => 20],
            [['phid'], 'unique'],
            [['name'], 'unique'],
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
            'author_phid' => Yii::t('app', 'Author Phid'),
            'file_phid' => Yii::t('app', 'File Phid'),
            'name' => Yii::t('app', 'Name'),
            'is_disabled' => Yii::t('app', 'Is Disabled'),
            'audio_phid' => Yii::t('app', 'Audio Phid'),
            'audio_behavior' => Yii::t('app', 'Audio Behavior'),
            'mail_key' => Yii::t('app', 'Mail Key'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return FileImagemacroQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new FileImagemacroQuery(get_called_class());
    }
}
