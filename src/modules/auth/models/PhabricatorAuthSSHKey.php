<?php

namespace orangins\modules\auth\models;

use orangins\modules\auth\query\PhabricatorAuthSSHKeyQuery;
use Yii;

/**
 * This is the model class for table "auth_sshkey".
 *
 * @property int $id
 * @property string $phid
 * @property string $object_phid
 * @property string $name
 * @property string $key_type
 * @property string $key_body
 * @property string $key_comment
 * @property string $key_index
 * @property int $is_trusted
 * @property int $is_active
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorAuthSSHKey extends \orangins\lib\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_sshkey';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phid', 'object_phid', 'name', 'key_type', 'key_body', 'key_comment', 'key_index', 'is_trusted'], 'required'],
            [['key_body'], 'string'],
            [['is_trusted', 'is_active'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'object_phid'], 'string', 'max' => 64],
            [['name', 'key_type', 'key_comment'], 'string', 'max' => 255],
            [['key_index'], 'string', 'max' => 12],
            [['phid'], 'unique'],
            [['key_index', 'is_active'], 'unique', 'targetAttribute' => ['key_index', 'is_active']],
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
            'object_phid' => Yii::t('app', 'Object PHID'),
            'name' => Yii::t('app', 'Name'),
            'key_type' => Yii::t('app', 'Key Type'),
            'key_body' => Yii::t('app', 'Key Body'),
            'key_comment' => Yii::t('app', 'Key Comment'),
            'key_index' => Yii::t('app', 'Key Index'),
            'is_trusted' => Yii::t('app', 'Is Trusted'),
            'is_active' => Yii::t('app', 'Is Active'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return PhabricatorAuthSSHKeyQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorAuthSSHKeyQuery(get_called_class());
    }

    /**
     * @return string
     */
    public function getObjectPHID()
    {
        return $this->object_phid;
    }

    /**
     * @param string $object_phid
     * @return self
     */
    public function setObjectPHID($object_phid)
    {
        $this->object_phid = $object_phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getKeyType()
    {
        return $this->key_type;
    }

    /**
     * @param string $key_type
     * @return self
     */
    public function setKeyType($key_type)
    {
        $this->key_type = $key_type;
        return $this;
    }

    /**
     * @return string
     */
    public function getKeyBody()
    {
        return $this->key_body;
    }

    /**
     * @param string $key_body
     * @return self
     */
    public function setKeyBody($key_body)
    {
        $this->key_body = $key_body;
        return $this;
    }

    /**
     * @return string
     */
    public function getKeyComment()
    {
        return $this->key_comment;
    }

    /**
     * @param string $key_comment
     * @return self
     */
    public function setKeyComment($key_comment)
    {
        $this->key_comment = $key_comment;
        return $this;
    }

    /**
     * @return string
     */
    public function getKeyIndex()
    {
        return $this->key_index;
    }

    /**
     * @param string $key_index
     * @return self
     */
    public function setKeyIndex($key_index)
    {
        $this->key_index = $key_index;
        return $this;
    }

    /**
     * @return int
     */
    public function getisTrusted()
    {
        return $this->is_trusted;
    }

    /**
     * @param int $is_trusted
     * @return self
     */
    public function setIsTrusted($is_trusted)
    {
        $this->is_trusted = $is_trusted;
        return $this;
    }

    /**
     * @return int
     */
    public function getisActive()
    {
        return $this->is_active;
    }

    /**
     * @param int $is_active
     * @return self
     */
    public function setIsActive($is_active)
    {
        $this->is_active = $is_active;
        return $this;
    }
}
