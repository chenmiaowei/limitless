<?php

namespace orangins\modules\people\models;

use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\auth\models\PhabricatorAuthProviderConfig;
use orangins\modules\auth\query\PhabricatorExternalAccountQuery;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\people\phid\PhabricatorPeopleExternalPHIDType;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "user_externalaccount".
 *
 * @property int $id
 * @property string $phid
 * @property string $user_phid
 * @property string $account_type
 * @property string $account_domain
 * @property string $account_secret
 * @property string $account_id
 * @property string $display_name
 * @property string $username
 * @property string $real_name
 * @property string $email
 * @property int $email_verified
 * @property string $account_uri
 * @property string $profile_image_phid
 * @property string $provider_config_phid
 * @property string $properties
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorExternalAccount extends ActiveRecordPHID
    implements PhabricatorPolicyInterface
{

    const MOBILE_TYPE_ACCOUNT = 'mobile';

    /**
     * @var string
     */
    private $profileImageFile = self::ATTACHABLE;
    /**
     * @var string
     */
    private $providerConfig = self::ATTACHABLE;

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getProfileImageFile()
    {
        return $this->assertAttached($this->profileImageFile);
    }

    /**
     * @param PhabricatorFile $file
     * @return $this
     * @author 陈妙威
     */
    public function attachProfileImageFile(PhabricatorFile $file)
    {
        $this->profileImageFile = $file;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_externalaccount';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['account_type', 'account_domain', 'account_id'], 'required'],
            ['properties', 'default', 'value' => '[]'],
            [['account_secret', 'properties'], 'string'],
            [['email_verified'], 'integer'],
            [['email_verified'], 'default', 'value' => 0],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'user_phid', 'account_domain', 'account_id', 'profile_image_phid'], 'string', 'max' => 64],
            [['account_type'], 'string', 'max' => 16],
            [['display_name', 'username', 'real_name', 'email', 'account_uri'], 'string', 'max' => 255],
            [['phid'], 'unique'],
            [['account_type', 'account_domain', 'account_id'], 'unique', 'targetAttribute' => ['account_type', 'account_domain', 'account_id']],
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
            'account_type' => Yii::t('app', 'Account Type'),
            'account_domain' => Yii::t('app', 'Account Domain'),
            'account_secret' => Yii::t('app', 'Account Secret'),
            'account_id' => Yii::t('app', 'Account ID'),
            'display_name' => Yii::t('app', 'Display Name'),
            'username' => Yii::t('app', 'Username'),
            'real_name' => Yii::t('app', 'Real Name'),
            'email' => Yii::t('app', 'Email'),
            'email_verified' => Yii::t('app', 'Email Verified'),
            'account_uri' => Yii::t('app', 'Account Uri'),
            'profile_image_phid' => Yii::t('app', 'Profile Image PHID'),
            'properties' => Yii::t('app', 'Properties'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorPeopleExternalPHIDType::class;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getDisplayName()
    {
        if (strlen($this->display_name)) {
            return $this->display_name;
        }

        // TODO: Figure out how much identifying information we're going to show
        // to users about external accounts. For now, just show a string which is
        // clearly not an error, but don't disclose any identifying information.

        $map = array(
            'email' => \Yii::t("app", 'Email User'),
        );

        $type = $this->account_type;

        return ArrayHelper::getValue($map, $type, \Yii::t("app", '"%s" User', $type));
    }

    /**
     * @return PhabricatorExternalAccountQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorExternalAccountQuery(get_called_class());
    }

    /**
     * @return string
     */
    public function getProviderConfigPHID()
    {
        return $this->provider_config_phid;
    }

    /**
     * @param string $provider_config_phid
     * @return self
     */
    public function setProviderConfigPHID($provider_config_phid)
    {
        $this->provider_config_phid = $provider_config_phid;
        return $this;
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
    public function getAccountType()
    {
        return $this->account_type;
    }

    /**
     * @param string $account_type
     * @return self
     */
    public function setAccountType($account_type)
    {
        $this->account_type = $account_type;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccountDomain()
    {
        return $this->account_domain;
    }

    /**
     * @param string $account_domain
     * @return self
     */
    public function setAccountDomain($account_domain)
    {
        $this->account_domain = $account_domain;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccountSecret()
    {
        return $this->account_secret;
    }

    /**
     * @param string $account_secret
     * @return self
     */
    public function setAccountSecret($account_secret)
    {
        $this->account_secret = $account_secret;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccountId()
    {
        return $this->account_id;
    }

    /**
     * @param string $account_id
     * @return self
     */
    public function setAccountId($account_id)
    {
        $this->account_id = $account_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return self
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getRealName()
    {
        return $this->real_name;
    }

    /**
     * @param string $real_name
     * @return self
     */
    public function setRealName($real_name)
    {
        $this->real_name = $real_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return self
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return int
     */
    public function getEmailVerified()
    {
        return $this->email_verified;
    }

    /**
     * @param int $email_verified
     * @return self
     */
    public function setEmailVerified($email_verified)
    {
        $this->email_verified = $email_verified;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccountUri()
    {
        return $this->account_uri;
    }

    /**
     * @param string $account_uri
     * @return self
     */
    public function setAccountUri($account_uri)
    {
        $this->account_uri = $account_uri;
        return $this;
    }

    /**
     * @return string
     */
    public function getProfileImagePHID()
    {
        return $this->profile_image_phid;
    }

    /**
     * @param string $profile_image_phid
     * @return self
     */
    public function setProfileImagePHID($profile_image_phid)
    {
        $this->profile_image_phid = $profile_image_phid;
        return $this;
    }


    /**
     * @param $string
     * @param $default
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    public function getProperty($string, $default = null)
    {
        $array = $this->properties === null ? [] : phutil_json_decode($this->properties);
        return ArrayHelper::getValue($array, $string, $default);
    }

    /**
     * @author 陈妙威
     * @param $key
     * @param $value
     * @return PhabricatorExternalAccount
     * @throws \Exception
     */
    public function setProperty($key, $value)
    {
        $array = $this->properties === null ? [] : phutil_json_decode($this->properties);
        $array[$key] = $value;
        $this->properties = phutil_json_encode($array);
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getProviderKey()
    {
        return $this->getAccountType() . ':' . $this->getAccountDomain();
    }

    /**
     * @return bool
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function isUsableForLogin()
    {
        $config = $this->getProviderConfig();
        if (!$config->getIsEnabled()) {
            return false;
        }

        $provider = $config->getProvider();
        if (!$provider->shouldAllowLogin()) {
            return false;
        }

        return true;
    }


    /**
     * @param PhabricatorAuthProviderConfig $config
     * @return $this
     * @author 陈妙威
     */
    public function attachProviderConfig(PhabricatorAuthProviderConfig $config)
    {
        $this->providerConfig = $config;
        return $this;
    }

    /**
     * @return PhabricatorAuthProviderConfig
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getProviderConfig()
    {
        return $this->assertAttached($this->providerConfig);
    }


    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */


    /**
     * @return array
     * @author 陈妙威
     */
    public function getCapabilities()
    {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
        );
    }

    /**
     * @param $capability
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                return PhabricatorPolicies::getMostOpenPolicy();
            case PhabricatorPolicyCapability::CAN_EDIT:
                return PhabricatorPolicies::POLICY_NOONE;
        }
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return ($viewer->getPHID() == $this->getUserPHID());
    }

    /**
     * @param $capability
     * @return null|string
     * @author 陈妙威
     */
    public function describeAutomaticCapability($capability)
    {
        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                return null;
            case PhabricatorPolicyCapability::CAN_EDIT:
                return pht(
                    'External accounts can only be edited by the account owner.');
        }
    }
}
