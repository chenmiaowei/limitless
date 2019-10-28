<?php

namespace orangins\modules\auth\models;

use orangins\modules\auth\editor\PhabricatorAuthProviderConfigEditor;
use orangins\modules\auth\phid\PhabricatorAuthAuthProviderPHIDType;
use orangins\modules\auth\provider\PhabricatorAuthProvider;
use orangins\modules\auth\query\PhabricatorAuthProviderConfigQuery;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\request\AphrontRequest;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "auth_providerconfig".
 *
 * @property int $id
 * @property string $phid
 * @property string $provider_class
 * @property string $provider_type
 * @property string $provider_domain
 * @property int $is_enabled
 * @property int $should_allow_login
 * @property int $should_allow_registration
 * @property int $should_allow_link
 * @property int $should_allow_unlink
 * @property int $should_trust_emails
 * @property int $should_auto_login
 * @property string $properties
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorAuthProviderConfig extends ActiveRecordPHID
    implements PhabricatorApplicationTransactionInterface,
    PhabricatorPolicyInterface
{

    /**
     * @var PhabricatorAuthProvider
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_providerconfig';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['provider_class', 'provider_type', 'provider_domain', 'is_enabled', 'should_allow_login', 'should_allow_registration', 'should_allow_link', 'should_allow_unlink'], 'required'],
            [['is_enabled', 'should_allow_login', 'should_allow_registration', 'should_allow_link', 'should_allow_unlink', 'should_trust_emails', 'should_auto_login'], 'integer'],
            [['properties'], 'string'],
            [['properties'], 'default', 'value' => '[]'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid'], 'string', 'max' => 64],
            [['provider_class', 'provider_domain'], 'string', 'max' => 128],
            [['provider_type'], 'string', 'max' => 32],
            [['phid'], 'unique'],
            [['provider_type', 'provider_domain'], 'unique', 'targetAttribute' => ['provider_type', 'provider_domain']],
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
            'provider_class' => Yii::t('app', 'Provider Class'),
            'provider_type' => Yii::t('app', 'Provider Type'),
            'provider_domain' => Yii::t('app', 'Provider Domain'),
            'is_enabled' => Yii::t('app', 'Is Enabled'),
            'should_allow_login' => Yii::t('app', 'Should Allow Login'),
            'should_allow_registration' => Yii::t('app', 'Should Allow Registration'),
            'should_allow_link' => Yii::t('app', 'Should Allow Link'),
            'should_allow_unlink' => Yii::t('app', 'Should Allow Unlink'),
            'should_trust_emails' => Yii::t('app', 'Should Trust Emails'),
            'should_auto_login' => Yii::t('app', 'Should Auto Login'),
            'properties' => Yii::t('app', 'Properties'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return PhabricatorAuthProvider
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getProvider()
    {
        if (!$this->provider) {
            $base = PhabricatorAuthProvider::getAllBaseProviders();
            $found = null;
            foreach ($base as $provider) {
                if ($provider->getClassShortName() == $this->provider_class) {
                    $found = $provider;
                    break;
                }
            }
            if ($found) {
                $phabricatorAuthProvider = clone $found;
                $this->provider = $phabricatorAuthProvider->attachProviderConfig($this);
            }
        }
        return $this->provider;
    }


    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorAuthAuthProviderPHIDType::className();
    }

    /**
     * @return PhabricatorAuthProviderConfigQuery|object
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorAuthProviderConfigQuery(get_called_class());
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
    public function getProviderClass()
    {
        return $this->provider_class;
    }

    /**
     * @param string $provider_class
     * @return self
     */
    public function setProviderClass($provider_class)
    {
        $this->provider_class = $provider_class;
        return $this;
    }

    /**
     * @return string
     */
    public function getProviderType()
    {
        return $this->provider_type;
    }

    /**
     * @param string $provider_type
     * @return self
     */
    public function setProviderType($provider_type)
    {
        $this->provider_type = $provider_type;
        return $this;
    }

    /**
     * @return string
     */
    public function getProviderDomain()
    {
        return $this->provider_domain;
    }

    /**
     * @param string $provider_domain
     * @return self
     */
    public function setProviderDomain($provider_domain)
    {
        $this->provider_domain = $provider_domain;
        return $this;
    }

    /**
     * @return int
     */
    public function getisEnabled()
    {
        return $this->is_enabled;
    }

    /**
     * @param int $is_enabled
     * @return self
     */
    public function setIsEnabled($is_enabled)
    {
        $this->is_enabled = $is_enabled;
        return $this;
    }

    /**
     * @return int
     */
    public function getShouldAllowLogin()
    {
        return $this->should_allow_login;
    }

    /**
     * @param int $should_allow_login
     * @return self
     */
    public function setShouldAllowLogin($should_allow_login)
    {
        $this->should_allow_login = $should_allow_login;
        return $this;
    }

    /**
     * @return int
     */
    public function getShouldAllowRegistration()
    {
        return $this->should_allow_registration;
    }

    /**
     * @param int $should_allow_registration
     * @return self
     */
    public function setShouldAllowRegistration($should_allow_registration)
    {
        $this->should_allow_registration = $should_allow_registration;
        return $this;
    }

    /**
     * @return int
     */
    public function getShouldAllowLink()
    {
        return $this->should_allow_link;
    }

    /**
     * @param int $should_allow_link
     * @return self
     */
    public function setShouldAllowLink($should_allow_link)
    {
        $this->should_allow_link = $should_allow_link;
        return $this;
    }

    /**
     * @return int
     */
    public function getShouldAllowUnlink()
    {
        return $this->should_allow_unlink;
    }

    /**
     * @param int $should_allow_unlink
     * @return self
     */
    public function setShouldAllowUnlink($should_allow_unlink)
    {
        $this->should_allow_unlink = $should_allow_unlink;
        return $this;
    }

    /**
     * @return int
     */
    public function getShouldTrustEmails()
    {
        return $this->should_trust_emails;
    }

    /**
     * @param int $should_trust_emails
     * @return self
     */
    public function setShouldTrustEmails($should_trust_emails)
    {
        $this->should_trust_emails = $should_trust_emails;
        return $this;
    }

    /**
     * @return int
     */
    public function getShouldAutoLogin()
    {
        return $this->should_auto_login;
    }

    /**
     * @param int $should_auto_login
     * @return self
     */
    public function setShouldAutoLogin($should_auto_login)
    {
        $this->should_auto_login = $should_auto_login;
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
     * @return PhabricatorAuthProviderConfig
     * @throws \Exception
     */
    public function setProperty($key, $value)
    {
        $array = $this->properties === null ? [] : phutil_json_decode($this->properties);
        $array[$key] = $value;
        $this->properties = phutil_json_encode($array);
        return $this;
    }


    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return PhabricatorAuthProviderConfigEditor|\orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorAuthProviderConfigEditor();
    }

    /**
     * @return $this|\orangins\lib\db\ActiveRecord
     * @author 陈妙威
     */
    public function getApplicationTransactionObject()
    {
        return $this;
    }

    /**
     * @return PhabricatorAuthProviderConfigTransaction|\orangins\modules\transactions\models\PhabricatorApplicationTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorAuthProviderConfigTransaction();
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


    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */


    /**
     * @return array|string[]
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
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                return PhabricatorPolicies::POLICY_USER;
            case PhabricatorPolicyCapability::CAN_EDIT:
                return PhabricatorPolicies::POLICY_ADMIN;
        }
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
}
