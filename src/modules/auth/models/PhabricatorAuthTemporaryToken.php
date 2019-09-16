<?php

namespace orangins\modules\auth\models;

use orangins\lib\time\PhabricatorTime;
use orangins\modules\auth\query\PhabricatorAuthTemporaryTokenQuery;
use orangins\modules\auth\tokentype\PhabricatorAuthTemporaryTokenType;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "auth_temporarytoken".
 *
 * @property int $id
 * @property string $token_resource
 * @property string $token_type
 * @property int $token_expires
 * @property string $token_code
 * @property string $user_phid
 * @property string $properties
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorAuthTemporaryToken extends \orangins\lib\db\ActiveRecord
    implements PhabricatorPolicyInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_temporarytoken';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['token_resource', 'token_type', 'token_expires', 'token_code'], 'required'],
            [['token_expires'], 'integer'],
            [['properties'], 'default', 'value' => '[]'],
            [['properties'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['token_resource', 'token_type', 'token_code', 'user_phid'], 'string', 'max' => 64],
            [['token_resource', 'token_type', 'token_code'], 'unique', 'targetAttribute' => ['token_resource', 'token_type', 'token_code']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'token_resource' => Yii::t('app', 'Token Resource'),
            'token_type' => Yii::t('app', 'Token Type'),
            'token_expires' => Yii::t('app', 'Token Expires'),
            'token_code' => Yii::t('app', 'Token Code'),
            'user_phid' => Yii::t('app', 'User Phid'),
            'properties' => Yii::t('app', 'Properties'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \orangins\lib\infrastructure\query\PhabricatorQuery|PhabricatorAuthTemporaryTokenQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorAuthTemporaryTokenQuery(get_called_class());
    }


    /**
     * @return string
     */
    public function getTokenResource()
    {
        return $this->token_resource;
    }

    /**
     * @param string $token_resource
     * @return self
     */
    public function setTokenResource($token_resource)
    {
        $this->token_resource = $token_resource;
        return $this;
    }

    /**
     * @return string
     */
    public function getTokenType()
    {
        return $this->token_type;
    }

    /**
     * @param string $token_type
     * @return self
     */
    public function setTokenType($token_type)
    {
        $this->token_type = $token_type;
        return $this;
    }

    /**
     * @return int
     */
    public function getTokenExpires()
    {
        return $this->token_expires;
    }

    /**
     * @param int $token_expires
     * @return self
     */
    public function setTokenExpires($token_expires)
    {
        $this->token_expires = $token_expires;
        return $this;
    }

    /**
     * @return string
     */
    public function getTokenCode()
    {
        return $this->token_code;
    }

    /**
     * @param string $token_code
     * @return self
     */
    public function setTokenCode($token_code)
    {
        $this->token_code = $token_code;
        return $this;
    }

    /**
     * @return string
     */
    public function getUserPhid()
    {
        return $this->user_phid;
    }

    /**
     * @param string $user_phid
     * @return self
     */
    public function setUserPhid($user_phid)
    {
        $this->user_phid = $user_phid;
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



    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */


    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getCapabilities()
    {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
        );
    }

    /**
     * @param $capability
     * @return mixed|string
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        // We're just implement this interface to get access to the standard
        // query infrastructure.
        return PhabricatorPolicies::getMostOpenPolicy();
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool|mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return false;
    }


    /**
     * @param PhabricatorUser $viewer
     * @param array $token_resources
     * @param array $token_types
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public static function revokeTokens(
        PhabricatorUser $viewer,
        array $token_resources,
        array $token_types)
    {

        /** @var self[] $tokens */
        $tokens = PhabricatorAuthTemporaryToken::find()
            ->setViewer($viewer)
            ->withTokenResources($token_resources)
            ->withTokenTypes($token_types)
            ->withExpired(false)
            ->execute();

        foreach ($tokens as $token) {
            $token->revokeToken();
        }
    }

    /**
     * @return $this
     * @throws \yii\db\IntegrityException
     * @throws \AphrontQueryException
     * @author 陈妙威
     */
    public function revokeToken()
    {
        if ($this->isRevocable()) {
            $this->setTokenExpires(PhabricatorTime::getNow() - 1)->save();
        }
        return $this;
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function isRevocable()
    {
        if ($this->token_expires < time()) {
            return false;
        }

        $type = $this->newTokenTypeImplementation();
        if ($type) {
            return $type->isTokenRevocable($this);
        }

        return false;
    }

    /**
     * @return PhabricatorAuthTemporaryTokenType
     * @author 陈妙威
     */
    private function newTokenTypeImplementation()
    {
        $types = PhabricatorAuthTemporaryTokenType::getAllTypes();

        $type = ArrayHelper::getValue($types, $this->token_type);
        if ($type) {
            return clone $type;
        }

        return null;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTokenReadableTypeName()
    {
        $type = $this->newTokenTypeImplementation();
        if ($type) {
            return $type->getTokenReadableTypeName($this);
        }

        return $this->token_type;
    }
}
