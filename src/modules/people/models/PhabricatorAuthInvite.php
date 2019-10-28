<?php

namespace orangins\modules\people\models;

use orangins\modules\people\query\PhabricatorAuthInviteQuery;
use Yii;

/**
 * This is the model class for table "user_authinvite".
 *
 * @property int $id
 * @property string $phid
 * @property string $author_phid
 * @property string $email_address
 * @property string $verification_hash
 * @property string $accepted_by_phid
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorAuthInvite extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_authinvite';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phid', 'author_phid', 'email_address', 'verification_hash'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'author_phid', 'accepted_by_phid'], 'string', 'max' => 64],
            [['email_address'], 'string', 'max' => 128],
            [['verification_hash'], 'string', 'max' => 12],
            [['phid'], 'unique'],
            [['email_address'], 'unique'],
            [['verification_hash'], 'unique'],
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
            'email_address' => Yii::t('app', 'Email Address'),
            'verification_hash' => Yii::t('app', 'Verification Hash'),
            'accepted_by_phid' => Yii::t('app', 'Accepted By Phid'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }


    /**
     * @return PhabricatorAuthInviteQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public static function find()
    {
        return Yii::createObject(PhabricatorAuthInviteQuery::class, [get_called_class()]);
    }

    /**
     * @return string
     */
    public function getPHID()
    {
        return $this->phid;
    }

    /**
     * @param string $phid
     * @return self
     */
    public function setPHID($phid)
    {
        $this->phid = $phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorPHID()
    {
        return $this->author_phid;
    }

    /**
     * @param string $author_phid
     * @return self
     */
    public function setAuthorPHID($author_phid)
    {
        $this->author_phid = $author_phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->email_address;
    }

    /**
     * @param string $email_address
     * @return self
     */
    public function setEmailAddress($email_address)
    {
        $this->email_address = $email_address;
        return $this;
    }

    /**
     * @return string
     */
    public function getVerificationHash()
    {
        return $this->verification_hash;
    }

    /**
     * @param string $verification_hash
     * @return self
     */
    public function setVerificationHash($verification_hash)
    {
        $this->verification_hash = $verification_hash;
        return $this;
    }

    /**
     * @return string
     */
    public function getAcceptedByPHID()
    {
        return $this->accepted_by_phid;
    }

    /**
     * @param string $accepted_by_phid
     * @return self
     */
    public function setAcceptedByPHID($accepted_by_phid)
    {
        $this->accepted_by_phid = $accepted_by_phid;
        return $this;
    }
}
