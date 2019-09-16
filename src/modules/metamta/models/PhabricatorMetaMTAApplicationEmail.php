<?php

namespace orangins\modules\metamta\models;

use orangins\modules\metamta\query\PhabricatorMetaMTAApplicationEmailQuery;
use Yii;

/**
 * This is the model class for table "metamta_applicationemail".
 *
 * @property int $id
 * @property string $phid
 * @property string $application_phid
 * @property string $address
 * @property string $space_phid
 * @property string $config_data
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorMetaMTAApplicationEmail extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'metamta_applicationemail';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phid', 'application_phid', 'address', 'config_data'], 'required'],
            [['config_data'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'application_phid', 'space_phid'], 'string', 'max' => 64],
            [['address'], 'string', 'max' => 128],
            [['phid'], 'unique'],
            [['address'], 'unique'],
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
            'application_phid' => Yii::t('app', 'Application Phid'),
            'address' => Yii::t('app', 'Address'),
            'space_phid' => Yii::t('app', 'Space Phid'),
            'config_data' => Yii::t('app', 'Config Data'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return PhabricatorMetaMTAApplicationEmailQuery|object|\yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public static function find()
    {
        return Yii::createObject(PhabricatorMetaMTAApplicationEmailQuery::class, [get_called_class()]);
    }
}
