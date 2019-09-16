<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/18
 * Time: 4:09 PM
 */

namespace orangins\modules\people\models;

use AphrontWriteGuard;
use Filesystem;
use orangins\lib\db\ActiveRecord;
use orangins\lib\infrastructure\edges\interfaces\PhabricatorEdgeInterface;
use orangins\lib\request\AphrontRequest;
use orangins\modules\auth\engine\PhabricatorAuthPasswordEngine;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\models\PhabricatorAuthPassword;
use orangins\modules\auth\models\PhabricatorAuthSession;
use orangins\modules\auth\password\PhabricatorAuthPasswordHashInterface;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\lib\env\PhabricatorEnv;
use orangins\modules\auth\provider\PhabricatorAuthProvider;
use orangins\modules\auth\provider\PhabricatorMobileAuthProvider;
use orangins\modules\people\cache\PhabricatorUserProfileImageCacheType;
use orangins\modules\people\cache\PhabricatorUserRbacCacheType;
use orangins\modules\people\editors\PhabricatorUserTransactionEditor;
use orangins\modules\people\search\PhabricatorUserFulltextEngine;
use orangins\modules\search\interfaces\PhabricatorFulltextInterface;
use orangins\modules\settings\setting\PhabricatorTranslationSetting;
use orangins\modules\auth\sshkey\PhabricatorSSHPublicKeyInterface;
use app\task\models\PhabricatorTaskIdentity;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;
use orangins\modules\phid\view\PHUIHandleListView;
use orangins\modules\phid\view\PHUIHandleView;
use orangins\lib\exception\ActiveRecordException;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomFieldAttachment;
use orangins\lib\infrastructure\customfield\interfaces\PhabricatorCustomFieldInterface;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\cache\PhabricatorCaches;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\people\cache\PhabricatorUserBadgesCacheType;
use orangins\modules\people\cache\PhabricatorUserCacheType;
use orangins\modules\people\cache\PhabricatorUserMessageCountCacheType;
use orangins\modules\people\cache\PhabricatorUserNotificationCountCacheType;
use orangins\modules\people\cache\PhabricatorUserPreferencesCacheType;
use orangins\modules\people\customfield\PhabricatorUserCustomField;
use orangins\modules\people\phid\PhabricatorPeopleUserPHIDType;
use orangins\modules\people\query\PhabricatorPeopleQuery;
use orangins\modules\people\search\PhabricatorUserFerretEngine;
use orangins\modules\phid\handles\pool\PhabricatorHandleList;
use orangins\modules\phid\handles\pool\PhabricatorHandlePool;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\search\ferret\PhabricatorFerretInterface;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use orangins\modules\settings\setting\PhabricatorSetting;
use orangins\modules\settings\setting\PhabricatorTimezoneSetting;
use PhutilOpaqueEnvelope;
use DateTime;
use DateTimeZone;
use Yii;
use Exception;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;

/**
 * Class Admins
 * @property string $username
 * @property string $profile_image_phid
 * @property string $availability_cache
 * @property string $availability_cache_ttl
 * @property string $default_profile_image_version
 * @property string $default_profile_image_phid
 * @property string $real_name
 * @property string $phid
 * @property string $account_secret
 * @property string $conduit_certificate
 * @property int $is_email_verified
 * @property bool $is_admin
 * @property bool $is_mailing_list
 * @property bool $is_enrolled_in_multi_factor
 * @property bool $is_approved
 * @property bool $is_system_agent
 * @property bool $is_manager
 * @property bool $is_merchant
 * @property integer $is_disabled
 * @property integer $id
 * @package orangins\modules\people\models
 */
class PhabricatorUser extends ActiveRecordPHID
    implements
    PhabricatorPolicyInterface,
    PhabricatorFerretInterface,
    PhabricatorCustomFieldInterface,
    PhabricatorAuthPasswordHashInterface,
    PhabricatorEdgeInterface,
    PhabricatorApplicationTransactionInterface,
    PhabricatorSSHPublicKeyInterface,
    PhabricatorFulltextInterface,
    IdentityInterface
{
    /**
     *
     */
    const MAXIMUM_USERNAME_LENGTH = 64;

    /**
     *
     */
    const TYPE_WEB = 'web';
    /**
     *
     */
    const TYPE_CONDUIT = 'conduit';

    /**
     *
     */
    const POLICY_USERS = "users";
    /**
     *
     */
    const POLICY_ADMIN = "admin";
    /**
     *
     */
    const POLICY_NONE = "none";
    /**
     * @var PhabricatorHandlePool
     */
    public $handlePool;

    /**
     * @var bool
     */
    public $omnipotent = false;

    /**
     * @var UserProfiles
     */
    public $user_profile;
    /**
     * @var array
     */
    public $badgePHIDs = [];

    /**
     * @var bool
     */
    private $ephemeral = false;

    /**
     * @var array
     */
    private $rawCacheData = array();
    /**
     * @var array
     */
    private $usableCacheData = array();
    /**
     * @var array
     */
    private $settingCacheKeys = array();
    /**
     * @var array
     */
    private $settingCache = array();

    /**
     * @var array
     */
    private $rbacCache = array();
    /**
     * @var
     */
    private $allowInlineCacheGeneration = true;

    /**
     * @var array
     */
    private $availability = self::ATTACHABLE;
    /**
     * @var string
     */
    private $session = self::ATTACHABLE;

    /**
     * @var string
     */
    private $customFields = self::ATTACHABLE;
    /**
     * @var
     */
    protected $availabilityCache;
    /**
     * @var
     */
    protected $availabilityCacheTTL;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @param $address
     * @return array|null|\yii\db\ActiveRecord
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public static function loadOneWithEmailAddress($address)
    {
        $email = PhabricatorUserEmail::find()->andWhere(
            'address = :address', [
            ':address' => $address
        ])->one();
        if (!$email) {
            return null;
        }
        return PhabricatorUser::find()->andWhere(
            'phid = :phid', [
            ':phid' => $email->getUserPHID()
        ])->one();
    }

    /**
     * @param $address
     * @return array|null|\yii\db\ActiveRecord
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public static function loadOneWithMobile($address)
    {
        $providers = PhabricatorAuthProvider::getAllEnabledProviders();
        /** @var PhabricatorAuthProvider[] $providers */
        $providers = mpull($providers, null, 'className');
        /** @var PhabricatorAuthProvider $provider */
        $provider = $providers[PhabricatorMobileAuthProvider::className()];

        $account = $account = PhabricatorExternalAccount::find()->andWhere([
            'account_type' => $provider->getProviderType(),
            'account_id' => $address,
        ])->one();
        if ($account) {
            $user = PhabricatorUser::find()->andWhere(
                'phid = :phid', [
                ':phid' => $account->getUserPHID()
            ])->one();
        } else {
            $user = null;
        }
        return $user;
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public static function describeValidUsername()
    {
        return \Yii::t("app",
            'Usernames must contain only numbers, letters, period, underscore and ' .
            'hyphen, and can not end with a period. They must have no more than {0} ' .
            'characters.', [self::MAXIMUM_USERNAME_LENGTH]);
    }

    /**
     * @return mixed
     * @throws ActiveRecordException
     * @throws \AphrontQueryException
     * @throws \PhutilAggregateException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \orangins\modules\file\exception\PhabricatorFileStorageConfigurationException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\IntegrityException
     * @throws Exception
     * @author 陈妙威
     */
    public static function getDefaultProfileImageURI()
    {
        return PhabricatorFile::loadBuiltin('avatar/avatar.png')->getViewURI();
    }


    /**
     * Returns true if this user is omnipotent. Omnipotent users bypass all policy
     * checks.
     *
     * @return bool True if the user bypasses policy checks.
     */
    public function isOmnipotent()
    {
        return $this->omnipotent;
    }


    /**
     * Get an omnipotent user object for use in contexts where there is no acting
     * user, notably daemons.
     *
     * @return PhabricatorUser An omnipotent user.
     */
    public static function getOmnipotentUser()
    {
        static $user = null;
        if (!$user) {
            $user = new PhabricatorUser();
            $user->omnipotent = true;
            $user->makeEphemeral();
        }
        return $user;
    }

    /**
     * @return PhabricatorUser|null
     * @author 陈妙威
     */
    public static function getGuestUser()
    {
        static $guestUser = null;
        if (!$guestUser) {
            $guestUser = new PhabricatorUser();
            $guestUser->makeEphemeral();
        }
        return $guestUser;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function hasSession()
    {
        return ($this->session !== self::ATTACHABLE);
    }


    /**
     * Make an object read-only.
     *
     * Making an object ephemeral indicates that you will be changing state in
     * such a way that you would never ever want it to be written back to the
     * storage.
     */
    public function makeEphemeral()
    {
        $this->ephemeral = true;
        return $this;
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIsDisabled()
    {
        return $this->is_disabled;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIsSystemAgent()
    {
        return $this->is_system_agent;
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getFullName()
    {
        if (strlen($this->real_name)) {
            return $this->username . ' (' . $this->real_name . ')';
        } else {
            return $this->username;
        }
    }


    /**
     * @return bool
     */
    public function getIsAdmin()
    {
        return $this->is_admin;
    }

    /**
     * @return bool
     */
    public function getIsManager()
    {
        return $this->is_manager;
    }

    /**
     * @return bool
     */
    public function isMerchant()
    {
        return $this->is_merchant;
    }

    /**
     * @param bool $is_merchant
     * @return self
     */
    public function setIsMerchant($is_merchant)
    {
        $this->is_merchant = $is_merchant;
        return $this;
    }

    /**
     * @param bool $is_manager
     * @return self
     */
    public function setIsManager($is_manager)
    {
        $this->is_manager = $is_manager;
        return $this;
    }

    /**
     * @param bool $is_admin
     * @return self
     */
    public function setIsAdmin($is_admin)
    {
        $this->is_admin = $is_admin;
        return $this;
    }

    /**
     * @return string
     */
    public function getConduitCertificate()
    {
        return $this->conduit_certificate;
    }

    /**
     * @param string $conduit_certificate
     * @return self
     */
    public function setConduitCertificate($conduit_certificate)
    {
        $this->conduit_certificate = $conduit_certificate;
        return $this;
    }

    /**
     * @param int $is_email_verified
     * @return self
     */
    public function setIsEmailVerified($is_email_verified)
    {
        $this->is_email_verified = $is_email_verified;
        return $this;
    }

    /**
     * @param bool $is_mailing_list
     * @return self
     */
    public function setIsMailingList($is_mailing_list)
    {
        $this->is_mailing_list = $is_mailing_list;
        return $this;
    }

    /**
     * @param bool $is_system_agent
     * @return self
     */
    public function setIsSystemAgent($is_system_agent)
    {
        $this->is_system_agent = $is_system_agent;
        return $this;
    }

    /**
     * @param int $is_disabled
     * @return self
     */
    public function setIsDisabled($is_disabled)
    {
        $this->is_disabled = $is_disabled;
        return $this;
    }


    /**
     * @return bool
     */
    public function getIsEnrolledInMultiFactor()
    {
        return $this->is_enrolled_in_multi_factor;
    }

    /**
     * @param bool $is_enrolled_in_multi_factor
     * @return self
     */
    public function setIsEnrolledInMultiFactor($is_enrolled_in_multi_factor)
    {
        $this->is_enrolled_in_multi_factor = $is_enrolled_in_multi_factor;
        return $this;
    }


    /**
     * @return array|PhabricatorUserEmail|null|\yii\db\ActiveRecord
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    public function loadPrimaryEmail()
    {
        $userEmail = PhabricatorUserEmail::find()->where([
            "user_phid" => $this->getPHID(),
            "is_primary" => 1,
        ])->one();

        return $userEmail;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIsMailingList()
    {
        return $this->is_mailing_list;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getUsername()
    {
        return $this->username;
    }


    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function setID($name)
    {
        $this->id = $name;
        return $this;
    }

    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function setPHID($name)
    {
        $this->phid = $name;
        return $this;
    }


    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function setUsername($name)
    {
        $this->username = $name;
        return $this;
    }

    /**
     * @return array|mixed
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getRecentBadgeAwards()
    {
        $badges_key = PhabricatorUserBadgesCacheType::KEY_BADGES;
        return $this->requireCacheData($badges_key);
    }

    /**
     * @task cache
     * @param $key
     * @return PhabricatorUser
     */
    public function clearCacheData($key)
    {
        unset($this->rawCacheData[$key]);
        unset($this->usableCacheData[$key]);
        return $this;
    }

    /**
     * Test if a given setting is set to a particular value.
     *
     * @param $key
     * @param $value
     * @return bool True if the setting has the specified value.
     * @throws Exception
     * @throws \ReflectionException
     * @task settings
     */
    public function compareUserSetting($key, $value)
    {
        $actual = $this->getUserSetting($key);
        return ($actual == $value);
    }

    /**
     * @param PhabricatorAuthSession $session
     * @return $this
     * @author 陈妙威
     */
    public function attachSession(PhabricatorAuthSession $session)
    {
        $this->session = $session;
        return $this;
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getDefaultProfileImagePHID()
    {
        return $this->default_profile_image_phid;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getProfileImagePHID()
    {
        return $this->profile_image_phid;
    }

    /**
     * @return PhabricatorAuthSession
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getSession()
    {
        return $this->assertAttached($this->session);
    }


    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function canEstablishAPISessions()
    {
        if ($this->getIsDisabled()) {
            return false;
        }

        // Intracluster requests are permitted even if the user is logged out:
        // in particular, public users are allowed to issue intracluster requests
        // when browsing Diffusion.
        if (PhabricatorEnv::isClusterRemoteAddress()) {
            if (!$this->isLoggedIn()) {
                return true;
            }
        }

        if (!$this->isUserActivated()) {
            return false;
        }

//        if ($this->getIsMailingList()) {
//            return false;
//        }

        return true;
    }


    /**
     * @throws Exception
     * @author 陈妙威
     */
    private function isEphemeralCheck()
    {
        if ($this->ephemeral) {
            throw new Exception();
        }
    }

    /**
     * @return array
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['phid', 'real_name'], 'string', 'max' => 64],
            [['is_approved', 'is_admin', 'is_manager'], 'integer'],
            ['profile_image_phid', 'safe'],
        ]);
    }

    /**
     * @param bool $insert
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function beforeSave($insert)
    {
        if (!$this->getConduitCertificate()) {
            $this->setConduitCertificate($this->generateConduitCertificate());
        }

        if (!strlen($this->getAccountSecret())) {
            $this->setAccountSecret(Filesystem::readRandomCharacters(64));
        }
        return parent::beforeSave($insert);
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    private function generateConduitCertificate()
    {
        return Filesystem::readRandomCharacters(255);
    }

    /**
     * @throws \yii\base\Exception
     * @throws \Exception
     * @return PhabricatorFile
     */
    public function getAvatar()
    {
        if (!$this->profile_image_phid) {
            $envConfig = PhabricatorEnv::getEnvConfig("people.default-avatars");
            $envConfig = array_values($envConfig);
            $avator = $envConfig[rand(0, count($envConfig) - 1)];
            $loadBuiltin = PhabricatorFile::loadBuiltin($avator, $this);
            $this->profile_image_phid = $loadBuiltin->phid;
            if (!$this->save(false)) {
                throw new ActiveRecordException(\Yii::t('app', "FileFiles save error"), $this->getErrorSummary(true));
            }
            return $loadBuiltin;
        } else {
            /** @var PhabricatorFile $file */
            $file = PhabricatorFile::find()->where(['phid' => $this->profile_image_phid])->one();
            return $file;
        }
    }

    /**
     * @return string
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getProfileImageURI()
    {
        $uri_key = PhabricatorUserProfileImageCacheType::KEY_URI;
        return $this->requireCacheData($uri_key);
    }

    /**
     * @return UserProfiles
     * @author 陈妙威
     */
    public function getUserProfile()
    {
        $adminProfiles = UserProfiles::find()->where(['user_phid' => $this->phid])->one();
        if (!$adminProfiles) {
            $adminProfiles = new UserProfiles();
            $adminProfiles->user_phid = $this->getPHID();
            $adminProfiles->save();
        }
        return $adminProfiles;
    }

    /**
     * 获取认证信息
     * @return PhabricatorTaskIdentity
     * @throws \yii\base\InvalidConfigException
     * @author 赵圆丽
     */
    public function getUserIdentity()
    {
        $identity = PhabricatorTaskIdentity::find()
            ->andWhere(['user_phid' => $this->phid])
            ->andWhere(['status' => PhabricatorTaskIdentity::STATUS_SUCCESS])
            ->one();
        if (!$identity) {
            return new PhabricatorTaskIdentity();
        }
        /** @var PhabricatorTaskIdentity $identity */
        return $identity;
    }

    /**
     * @param PhabricatorUser $admin
     * @return void
     * @throws Exception
     * @throws \AphrontQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function sendWelcomeEmail(PhabricatorUser $admin)
    {

        if (!$this->canEstablishWebSessions()) {
            throw new Exception(
                \Yii::t("app",
                    'Can not send welcome mail to users who can not establish ' .
                    'web sessions!'));
        }

        $admin_username = $admin->getUserName();
        $admin_realname = $admin->getRealName();
        $user_username = $this->getUserName();
        $is_serious = PhabricatorEnv::getEnvConfig('orangins.serious-business');

        $base_uri = PhabricatorEnv::getProductionURI('/');

        $engine = new PhabricatorAuthSessionEngine();
        $uri = $engine->getOneTimeLoginURI(
            $this,
            $this->loadPrimaryEmail(),
            PhabricatorAuthSessionEngine::ONETIME_WELCOME);

        $siteName = PhabricatorEnv::getEnvConfig("orangins.site-name");
        $body = \Yii::t("app",
            "Welcome to {0}!\n\n" .
            "{1} ({2}) has created an account for you.\n\n" .
            "  Username: {3}\n\n" .
            "To login to {4}, follow this link and set a password:\n\n" .
            "  {5}\n\n" .
            "After you have set a password, you can login in the future by " .
            "going here:\n\n" .
            "  {6}\n", [
                $siteName,
                $admin_username,
                $admin_realname,
                $user_username,
                $siteName,
                $uri,
                $base_uri
            ]);

        if (!$is_serious) {
            $body .= sprintf(
                "\n%s\n",
                Yii::t("app", "Love,\n{0}", [$siteName]));
        }

        (new PhabricatorMetaMTAMail())
            ->addTos(array($this->getPHID()))
            ->setForceDelivery(true)
            ->setSubject(\Yii::t("app", '[{0}] Welcome to {1}', $siteName, $siteName))
            ->setBody($body)
            ->saveAndSend();
    }

    /**
     * @return PhabricatorPeopleQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public static function find()
    {
        return Yii::createObject(PhabricatorPeopleQuery::class, [get_called_class()]);
    }

    /**
     * @return string[]
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
     * @return mixed
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                return PhabricatorPolicies::POLICY_PUBLIC;
            case PhabricatorPolicyCapability::CAN_EDIT:
                if ($this->getIsSystemAgent() || $this->getIsMailingList()) {
                    return PhabricatorPolicies::POLICY_ADMIN;
                } else {
                    return PhabricatorPolicies::POLICY_NOONE;
                }
        }
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return $this->getPHID() && ($viewer->getPHID() === $this->getPHID());
    }


    /**
     * @param $key
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getUserSetting($key)
    {
        // NOTE: We store available keys and cached values separately to make it
        // faster to check for `null` in the cache, which is common.
        if (isset($this->settingCacheKeys[$key])) {
            return $this->settingCache[$key];
        }

        $settings_key = PhabricatorUserPreferencesCacheType::KEY_PREFERENCES;
        if ($this->getPHID()) {
            $settings = $this->requireCacheData($settings_key);
        } else {
            $settings = $this->loadGlobalSettings();
        }

        if (array_key_exists($key, $settings)) {
            $value = $settings[$key];
            return $this->writeUserSettingCache($key, $value);
        }

        $cache = PhabricatorCaches::getRuntimeCache();
        $cache_key = "settings.defaults({$key})";
        $cache_map = $cache->getKeys(array($cache_key));

        if ($cache_map) {
            $value = $cache_map[$cache_key];
        } else {
            $defaults = PhabricatorSetting::getAllSettings();
            if (isset($defaults[$key])) {
                /** @var PhabricatorSetting $setting */
                $setting = clone $defaults[$key];
                $value = $setting
                    ->setViewer($this)
                    ->getSettingDefaultValue();
            } else {
                $value = null;
            }

            $cache->setKey($cache_key, $value);
        }

        return $this->writeUserSettingCache($key, $value);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    private function writeUserSettingCache($key, $value)
    {
        $this->settingCacheKeys[$key] = true;
        $this->settingCache[$key] = $value;
        return $value;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public static function getGlobalSettingsCacheKey()
    {
        return 'user.settings.globals.v1';
    }

    /**
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    private function loadGlobalSettings()
    {
        $cache_key = self::getGlobalSettingsCacheKey();
        $cache = PhabricatorCaches::getMutableStructureCache();

        $settings = $cache->getKey($cache_key);
        if (!$settings) {
            $preferences = PhabricatorUserPreferences::loadGlobalPreferences($this);
            $settings = $preferences->getPreferences();
            $cache->setKey($cache_key, $settings);
        }

        return $settings;
    }


    /**
     * @task cache
     * @param $key
     * @return array|mixed
     * @throws Exception
     * @throws \ReflectionException
     */
    protected function requireCacheData($key)
    {
        if (isset($this->usableCacheData[$key])) {
            return $this->usableCacheData[$key];
        }

        $type = PhabricatorUserCacheType::requireCacheTypeForKey($key);

        if (isset($this->rawCacheData[$key])) {
            $raw_value = $this->rawCacheData[$key];

            $usable_value = $type->getValueFromStorage($raw_value);
            $this->usableCacheData[$key] = $usable_value;

            return $usable_value;
        }

        // By default, we throw if a cache isn't available. This is consistent
        // with the standard `needX()` + `attachX()` + `getX()` interaction.
        if (!$this->allowInlineCacheGeneration) {
            throw new PhabricatorDataNotAttachedException($this);
        }

        $user_phid = $this->getPHID();

        // Try to read the actual cache before we generate a new value. We can
        // end up here via Conduit, which does not use normal sessions and can
        // not pick up a free cache load during session identification.
        if ($user_phid) {
            $raw_data = PhabricatorUserCache::readCaches(
                $type,
                $key,
                array($user_phid));
            if (array_key_exists($user_phid, $raw_data)) {
                $raw_value = $raw_data[$user_phid];
                $usable_value = $type->getValueFromStorage($raw_value);
                $this->rawCacheData[$key] = $raw_value;
                $this->usableCacheData[$key] = $usable_value;
                return $usable_value;
            }
        }

        $usable_value = $type->getDefaultValue();

        if ($user_phid) {
            $map = $type->newValueForUsers($key, array($this));
            if (array_key_exists($user_phid, $map)) {
                $raw_value = $map[$user_phid];
                $usable_value = $type->getValueFromStorage($raw_value);

                $this->rawCacheData[$key] = $raw_value;
                PhabricatorUserCache::writeCache(
                    $type,
                    $key,
                    $user_phid,
                    $raw_value);
            }
        }

        $this->usableCacheData[$key] = $usable_value;

        return $usable_value;
    }

    /**
     * @param $allow_cache_generation
     * @return $this
     * @author 陈妙威
     */
    public function setAllowInlineCacheGeneration($allow_cache_generation)
    {
        $this->allowInlineCacheGeneration = $allow_cache_generation;
        return $this;
    }


    /**
     * @return null
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getDisplayAvailability()
    {
        $availability = $this->availability;

        $this->assertAttached($availability);
        if (!$availability) {
            return null;
        }

        $busy = PhabricatorCalendarEventInvitee::AVAILABILITY_BUSY;

        return ArrayHelper::getValue($availability, 'availability', $busy);
    }

    /**
     * Get the timestamp the user is away until, if they are currently away.
     *
     * @return int|null Epoch timestamp, or `null` if the user is not away.
     * @task availability
     * @throws PhabricatorDataNotAttachedException
     */
    public function getAwayUntil()
    {
        $availability = $this->availability;

        $this->assertAttached($availability);
        if (!$availability) {
            return null;
        }

        return ArrayHelper::getValue($availability, 'until');
    }


    /**
     * Is this a user who we can reasonably expect to respond to requests?
     *
     * This is used to provide a grey "disabled/unresponsive" dot cue when
     * rendering handles and tags, so it isn't a surprise if you get ignored
     * when you ask things of users who will not receive notifications or could
     * not respond to them (because they are disabled, unapproved, do not have
     * verified email addresses, etc).
     *
     * @return bool True if this user can receive and respond to requests from
     *   other humans.
     * @throws Exception
     */
    public function isResponsive()
    {
        if (!$this->isUserActivated()) {
            return false;
        }

        if (!$this->is_email_verified) {
            return false;
        }

        return true;
    }


    /**
     * Is this a live account which has passed required approvals? Returns true
     * if this is an enabled, verified (if required), approved (if required)
     * account, and false otherwise.
     *
     * @return bool True if this is a standard, usable account.
     * @throws Exception
     */
    public function isUserActivated()
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        if ($this->isOmnipotent()) {
            return true;
        }

        if ($this->getIsDisabled()) {
            return false;
        }

        if (!$this->is_approved) {
            return false;
        }

        if (PhabricatorUserEmail::isEmailVerificationRequired()) {
            if (!$this->is_email_verified) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isLoggedIn()
    {
        return !($this->getPHID() === null);
    }


    /**
     * @param UserProfiles $profile
     * @return $this
     * @author 陈妙威
     */
    public function attachUserProfile(UserProfiles $profile)
    {
        $this->user_profile = $profile;
        return $this;
    }


    /**
     * Get cached availability, if present.
     *
     * @return array|null Cache data, or null if no cache is available.
     * @task availability
     */
    public function getAvailabilityCache()
    {
        $now = PhabricatorTime::getNow();
        if ($this->availabilityCacheTTL <= $now) {
            return null;
        }

        try {
            return phutil_json_decode($this->availabilityCache);
        } catch (Exception $ex) {
            return null;
        }
    }

    /**
     * @task availability
     * @param array $availability
     * @return PhabricatorUser
     */
    public function attachAvailability(array $availability)
    {
        $this->availability = $availability;
        return $this;
    }


    /**
     * @task cache
     * @param array $data
     * @return PhabricatorUser
     */
    public function attachRawCacheData(array $data)
    {
        $this->rawCacheData = $data + $this->rawCacheData;
        return $this;
    }

    /**
     * Write to the availability cache.
     *
     * @param array Availability cache data.
     * @param int|null Cache TTL.
     * @return static
     * @task availability
     * @throws Exception
     */
    public function writeAvailabilityCache(array $availability, $ttl)
    {
        if (PhabricatorEnv::isReadOnly()) {
            return $this;
        }
        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        PhabricatorUser::updateAll([
            'availability_cache' => phutil_json_encode($availability),
            'availability_cache_ttl' => $ttl
        ], [
            'id' => $this->getID()
        ]);
        unset($unguarded);
        return $this;
    }

    /**
     * @return array|UserProfiles|null|\yii\db\ActiveRecord
     * @throws Exception
     * @author 陈妙威
     */
    public function loadUserProfile()
    {
        if ($this->user_profile) {
            return $this->user_profile;
        }
        $this->user_profile = UserProfiles::find()->where(['user_phid' => $this->getPHID()])->one();
        if (!$this->user_profile) {
            $this->user_profile = UserProfiles::initializeNewProfile($this);
        }
        return $this->user_profile;
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getTranslation()
    {
        return $this->getUserSetting(PhabricatorTranslationSetting::SETTINGKEY);
    }


    /**
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getTimezoneIdentifier()
    {
        return $this->getUserSetting(PhabricatorTimezoneSetting::SETTINGKEY);
    }

    /**
     * @return int
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getTimeZoneOffset()
    {
        $timezone = $this->getTimeZone();
        $now = new DateTime('@' . PhabricatorTime::getNow());
        $offset = $timezone->getOffset($now);

        // Javascript offsets are in minutes and have the opposite sign.
        $offset = -(int)($offset / 60);

        return $offset;
    }

    /**
     * @return DateTimeZone
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getTimeZone()
    {
        return new DateTimeZone($this->getTimezoneIdentifier());
    }

    /**
     * @return array|mixed
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getUnreadMessageCount()
    {
        $message_key = PhabricatorUserMessageCountCacheType::KEY_COUNT;
        return $this->requireCacheData($message_key);
    }

    /**
     * @return array|mixed
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getUnreadNotificationCount()
    {
        $notification_key = PhabricatorUserNotificationCountCacheType::KEY_COUNT;
        return $this->requireCacheData($notification_key);
    }

    /**
     * @return array|mixed
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getRbacSettings()
    {
        $notification_key = PhabricatorUserRbacCacheType::KEY_PREFERENCES;
        return $this->requireCacheData($notification_key);
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getIsEmailVerified()
    {
        return $this->is_email_verified;
    }

    /**
     * @param $phid
     * @return mixed
     * @author 陈妙威
     */
    public function setProfileImagePHID($phid)
    {
        return $this->profile_image_phid = $phid;
    }

    /**
     * @return bool
     */
    public function getIsApproved()
    {
        return $this->is_approved;
    }

    /**
     * @param bool $is_approved
     * @return self
     */
    public function setIsApproved($is_approved)
    {
        $this->is_approved = $is_approved;
        return $this;
    }

    /**
     * Returns `true` if this is a standard user who is logged in. Returns `false`
     * for logged out, anonymous, or external users.
     *
     * @return bool `true` if the user is a standard user who is logged in with
     *              a normal session.
     * @throws Exception
     */
    public function getIsStandardUser()
    {
        $type_user = PhabricatorPeopleUserPHIDType::TYPECONST;
        return $this->getPHID() && (PhabricatorPHID::phid_get_type($this->getPHID()) == $type_user);
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
     * @param string $default_profile_image_phid
     * @return self
     */
    public function setDefaultProfileImagePhid($default_profile_image_phid)
    {
        $this->default_profile_image_phid = $default_profile_image_phid;
        return $this;
    }

    /**
     * @param string $default_profile_image_version
     * @return self
     */
    public function setDefaultProfileImageVersion($default_profile_image_version)
    {
        $this->default_profile_image_version = $default_profile_image_version;
        return $this;
    }

    /**
     * @return bool
     * @throws \AphrontQueryException
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function saveWithoutIndex()
    {
        return $this->save();
    }

    /**
     * Get a scalar string identifying this user.
     *
     * This is similar to using the PHID, but distinguishes between omnipotent
     * and public users explicitly. This allows safe construction of cache keys
     * or cache buckets which do not conflate public and omnipotent users.
     *
     * @return string Scalar identifier.
     * @throws Exception
     */
    public function getCacheFragment()
    {
        if ($this->isOmnipotent()) {
            return 'u.omnipotent';
        }

        $phid = $this->getPHID();
        if ($phid) {
            return 'u.' . $phid;
        }

        return 'u.public';
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function canEstablishWebSessions()
    {
        if ($this->getIsMailingList()) {
            return false;
        }

        if ($this->getIsSystemAgent()) {
            return false;
        }

        return true;
    }



    /* -(  PhabricatorCustomFieldInterface  )------------------------------------ */


    /**
     * @param $role
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getCustomFieldSpecificationForRole($role)
    {
        return PhabricatorEnv::getEnvConfig('user.fields');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getCustomFieldBaseClass()
    {
        return PhabricatorUserCustomField::class;
    }

    /**
     * @return mixed
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getCustomFields()
    {
        return $this->assertAttached($this->customFields);
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
     * @param PhabricatorCustomFieldAttachment $fields
     * @return $this|mixed
     * @author 陈妙威
     */
    public function attachCustomFields(PhabricatorCustomFieldAttachment $fields)
    {
        $this->customFields = $fields;
        return $this;
    }


    /**
     * @param $username
     * @return bool
     * @author 陈妙威
     */
    public static function validateUsername($username)
    {
        // NOTE: If you update this, make sure to update:
        //
        //  - Remarkup rule for @mentions.
        //  - Routing rule for "/p/username/".
        //  - Unit tests, obviously.
        //  - describeValidUsername() method, above.

        if (strlen($username) > self::MAXIMUM_USERNAME_LENGTH) {
            return false;
        }

        return (bool)preg_match('/^[a-zA-Z0-9._-]*[a-zA-Z0-9_-]\z/', $username);
    }


    /* -(  PhabricatorAuthPasswordHashInterface  )------------------------------- */


    /**
     * @param PhutilOpaqueEnvelope $envelope
     * @param PhabricatorAuthPassword $password
     * @return PhutilOpaqueEnvelope|mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function newPasswordDigest(
        PhutilOpaqueEnvelope $envelope,
        PhabricatorAuthPassword $password)
    {

        // Before passwords are hashed, they are digested. The goal of digestion
        // is twofold: to reduce the length of very long passwords to something
        // reasonable; and to salt the password in case the best available hasher
        // does not include salt automatically.

        // Users may choose arbitrarily long passwords, and attackers may try to
        // attack the system by probing it with very long passwords. When large
        // inputs are passed to hashers -- which are intentionally slow -- it
        // can result in unacceptably long runtimes. The classic attack here is
        // to try to log in with a 64MB password and see if that locks up the
        // machine for the next century. By digesting passwords to a standard
        // length first, the length of the raw input does not impact the runtime
        // of the hashing algorithm.

        // Some hashers like bcrypt are self-salting, while other hashers are not.
        // Applying salt while digesting passwords ensures that hashes are salted
        // whether we ultimately select a self-salting hasher or not.

        // For legacy compatibility reasons, old VCS and Account password digest
        // algorithms are significantly more complicated than necessary to achieve
        // these goals. This is because they once used a different hashing and
        // salting process. When we upgraded to the modern modular hasher
        // infrastructure, we just bolted it onto the end of the existing pipelines
        // so that upgrading didn't break all users' credentials.

        // New implementations can (and, generally, should) safely select the
        // simple HMAC SHA256 digest at the bottom of the function, which does
        // everything that a digest callback should without any needless legacy
        // baggage on top.

        if ($password->getLegacyDigestFormat() == 'v1') {
            switch ($password->getPasswordType()) {
                case PhabricatorAuthPassword::PASSWORD_TYPE_VCS:
                    // Old VCS passwords use an iterated HMAC SHA1 as a digest algorithm.
                    // They originally used this as a hasher, but it became a digest
                    // algorithm once hashing was upgraded to include bcrypt.
                    $digest = $envelope->openEnvelope();
                    $salt = $this->getPHID();
                    for ($ii = 0; $ii < 1000; $ii++) {
                        $digest = PhabricatorHash::weakDigest($digest, $salt);
                    }
                    return new PhutilOpaqueEnvelope($digest);
                case PhabricatorAuthPassword::PASSWORD_TYPE_ACCOUNT:
                    // Account passwords previously used this weird mess of salt and did
                    // not digest the input to a standard length.

                    // Beyond this being a weird special case, there are two actual
                    // problems with this, although neither are particularly severe:

                    // First, because we do not normalize the length of passwords, this
                    // algorithm may make us vulnerable to DOS attacks where an attacker
                    // attempts to use a very long input to slow down hashers.

                    // Second, because the username is part of the hash algorithm,
                    // renaming a user breaks their password. This isn't a huge deal but
                    // it's pretty silly. There's no security justification for this
                    // behavior, I just didn't think about the implication when I wrote
                    // it originally.

                    $parts = array(
                        $this->getUsername(),
                        $envelope->openEnvelope(),
                        $this->getPHID(),
                        $password->getPasswordSalt(),
                    );

                    return new PhutilOpaqueEnvelope(implode('', $parts));
            }
        }

        // For passwords which do not have some crazy legacy reason to use some
        // other digest algorithm, HMAC SHA256 is an excellent choice. It satisfies
        // the digest requirements and is simple.

        $digest = PhabricatorHash::digestHMACSHA256(
            $envelope->openEnvelope(),
            $password->getPasswordSalt());

        return new PhutilOpaqueEnvelope($digest);
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorAuthPasswordEngine $engine
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function newPasswordBlocklist(
        PhabricatorUser $viewer,
        PhabricatorAuthPasswordEngine $engine)
    {

        $list = array();
        $list[] = $this->getUsername();
        $list[] = $this->getRealName();

        $emails = PhabricatorUserEmail::find()->andWhere(['user_phid' => $this->getPHID()])->all();
        foreach ($emails as $email) {
            $list[] = $email->getAddress();
        }

        return $list;
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorPeopleUserPHIDType::class;
    }

    /**
     * Finds an identity by the given ID.
     * @param string|int $id the ID to be looked for
     * @return IdentityInterface the identity object that matches the given ID.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentity($id)
    {
        return PhabricatorUser::findOne($id);
    }

    /**
     * Finds an identity by the given token.
     * @param mixed $token the token to be looked for
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\yii\filters\auth\HttpBearerAuth]] will set this parameter to be `yii\filters\auth\HttpBearerAuth`.
     * @return IdentityInterface the identity object that matches the given token.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        // TODO: Implement findIdentityByAccessToken() method.
    }

    /**
     * Returns a key that can be used to check the validity of a given identity ID.
     *
     * The key should be unique for each individual user, and should be persistent
     * so that it can be used to check the validity of the user identity.
     *
     * The space of such keys should be big enough to defeat potential identity attacks.
     *
     * This is required if [[User::enableAutoLogin]] is enabled.
     * @return string a key that is used to check the validity of a given identity ID.
     * @see validateAuthKey()
     */
    public function getAuthKey()
    {
        // TODO: Implement getAuthKey() method.
    }

    /**
     * Validates the given auth key.
     *
     * This is required if [[User::enableAutoLogin]] is enabled.
     * @param string $authKey the given auth key
     * @return void whether the given auth key is valid.
     * @see getAuthKey()
     */
    public function validateAuthKey($authKey)
    {
        // TODO: Implement validateAuthKey() method.
    }




    /* -(  Managing Handles  )--------------------------------------------------- */


    /**
     * Get a @{class:PhabricatorHandleList} which benefits from this viewer's
     * internal handle pool.
     *
     * @param array $phids
     * @return PhabricatorHandleList Handle list object.
     * @task handle
     */
    public function loadHandles(array $phids)
    {
        if ($this->handlePool === null) {
            $this->handlePool = (new PhabricatorHandlePool())
                ->setViewer($this);
        }

        $list = $this->handlePool->newHandleList($phids);
        return $list;
    }


    /**
     * Get a @{class:PHUIHandleView} for a single handle.
     *
     * This benefits from the viewer's internal handle pool.
     *
     * @param string PHID to render a handle for.
     * @return PHUIHandleView View of the handle.
     * @throws Exception
     * @task handle
     */
    public function renderHandle($phid)
    {
        return $this->loadHandles(array($phid))->renderHandle($phid);
    }


    /**
     * Get a @{class:PHUIHandleListView} for a list of handles.
     *
     * This benefits from the viewer's internal handle pool.
     *
     * @param array<phid> List of PHIDs to render.
     * @return PHUIHandleListView View of the handles.
     * @task handle
     */
    public function renderHandleList(array $phids)
    {
        return $this->loadHandles($phids)->renderList();
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function attachBadgePHIDs(array $phids)
    {
        $this->badgePHIDs = $phids;
        return $this;
    }

    /**
     * @return mixed
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getBadgePHIDs()
    {
        return $this->assertAttached($this->badgePHIDs);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function edgeBaseTableName()
    {
        return "user";
    }


    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return PhabricatorUserTransactionEditor|PhabricatorApplicationTransactionEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorUserTransactionEditor();
    }

    /**
     * @return $this|ActiveRecord
     * @author 陈妙威
     */
    public function getApplicationTransactionObject()
    {
        return $this;
    }

    /**
     * @return PhabricatorUserTransaction|PhabricatorApplicationTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorUserTransaction();
    }

    /**
     * @param PhabricatorApplicationTransactionView $timeline
     * @param AphrontRequest $request
     * @return PhabricatorApplicationTransactionView
     * @author 陈妙威
     */
    public function willRenderTimeline(
        PhabricatorApplicationTransactionView $timeline,
        AphrontRequest $request)
    {
        return $timeline;
    }


    /* -(  PhabricatorSSHPublicKeyInterface  )----------------------------------- */


    /**
     * @param PhabricatorUser $viewer
     * @return string
     * @author 陈妙威
     */
    public function getSSHPublicKeyManagementURI(PhabricatorUser $viewer)
    {
        if ($viewer->getPHID() == $this->getPHID()) {
            // If the viewer is managing their own keys, take them to the normal
            // panel.
            return '/settings/panel/ssh/';
        } else {
            // Otherwise, take them to the administrative panel for this user.
            return '/settings/user/' . $this->getUsername() . '/page/ssh/';
        }
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSSHKeyDefaultName()
    {
        return 'id_rsa_phabricator';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getSSHKeyNotifyPHIDs()
    {
        return array(
            $this->getPHID(),
        );
    }

    /* -(  PhabricatorFulltextInterface  )--------------------------------------- */


    /**
     * @return PhabricatorUserFulltextEngine|\orangins\modules\search\index\PhabricatorFulltextEngine
     * @author 陈妙威
     */
    public function newFulltextEngine()
    {
        return new PhabricatorUserFulltextEngine();
    }



    /* -(  PhabricatorFerretInterface  )----------------------------------------- */


    /**
     * @return PhabricatorUserFerretEngine
     * @author 陈妙威
     */
    public function newFerretEngine()
    {
        return new PhabricatorUserFerretEngine();
    }


    /**
     * @return array
     * @author 陈妙威
     */
    public function attributes()
    {
        $count = count(PhabricatorUserCacheType::getAllCacheTypes());

        $attr = [];
        for ($i = 1; $i <= $count; $i++) {
            $attr[] = "ucache_{$i}";
        }
        $merge = ArrayHelper::merge(parent::attributes(), $attr, [
            's_id',
            's_session_expires',
            's_session_start',
            's_high_security_until',
            's_is_partial',
            's_signed_legalpad_documents',
        ]);
        return $merge;
    }
}
