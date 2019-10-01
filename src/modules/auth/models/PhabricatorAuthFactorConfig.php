<?php

namespace orangins\modules\auth\models;

use orangins\modules\auth\query\PhabricatorAuthFactorConfigQuery;
use Yii;

/**
 * This is the model class for table "auth_factorconfig".
 *
 * @property int $id
 * @property string $phid
 * @property string $user_phid
 * @property string $factor_key
 * @property string $factor_name
 * @property string $factor_secret
 * @property string $properties
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorAuthFactorConfig extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_factorconfig';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_phid', 'factor_key', 'factor_name', 'factor_secret', 'properties'], 'required'],
            [['factor_name', 'factor_secret', 'properties'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'user_phid', 'factor_key'], 'string', 'max' => 64],
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
            'phid' => Yii::t('app', 'PHID'),
            'user_phid' => Yii::t('app', 'User PHID'),
            'factor_key' => Yii::t('app', 'Factor Key'),
            'factor_name' => Yii::t('app', 'Factor Name'),
            'factor_secret' => Yii::t('app', 'Factor Secret'),
            'properties' => Yii::t('app', 'Properties'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string
     */
    public function getUserPHID()
    {
        return $this->user_phid;
    }

    /**
     * @param string $user_phid
     * @return self
     */
    public function setUserPHID($user_phid)
    {
        $this->user_phid = $user_phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getFactorKey()
    {
        return $this->factor_key;
    }

    /**
     * @param string $factor_key
     * @return self
     */
    public function setFactorKey($factor_key)
    {
        $this->factor_key = $factor_key;
        return $this;
    }

    /**
     * @return string
     */
    public function getFactorName()
    {
        return $this->factor_name;
    }

    /**
     * @param string $factor_name
     * @return self
     */
    public function setFactorName($factor_name)
    {
        $this->factor_name = $factor_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getFactorSecret()
    {
        return $this->factor_secret;
    }

    /**
     * @param string $factor_secret
     * @return self
     */
    public function setFactorSecret($factor_secret)
    {
        $this->factor_secret = $factor_secret;
        return $this;
    }

    /**
     * @return string
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param string $properties
     * @return self
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @return PhabricatorAuthFactorConfigQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorAuthFactorConfigQuery(get_called_class());
    }
}
