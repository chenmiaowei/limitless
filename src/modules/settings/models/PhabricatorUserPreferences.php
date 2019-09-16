<?php

namespace orangins\modules\settings\models;

use orangins\lib\helpers\OranginsUtil;
use orangins\lib\request\AphrontRequest;
use orangins\lib\validators\JsonEncodeValidator;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\settings\editors\PhabricatorUserPreferencesEditor;
use orangins\modules\settings\query\PhabricatorUserPreferencesQuery;
use orangins\modules\settings\setting\PhabricatorSetting;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;
use Yii;
use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\settings\phid\PhabricatorUserPreferencesPHIDType;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "user_preferences".
 *
 * @property int $id
 * @property string $user_phid
 * @property array $preferences
 * @property string $phid
 * @property string $builtin_key
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorUserPreferences extends ActiveRecordPHID
    implements PhabricatorPolicyInterface,
    PhabricatorApplicationTransactionInterface
{

    /**
     *
     */
    const BUILTIN_GLOBAL_DEFAULT = 'global';

    /**
     * @var PhabricatorUser
     */
    public $user = self::ATTACHABLE;

    /**
     * @var self
     */
    public $defaultSettings;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_preferences';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['preferences'], JsonEncodeValidator::class],
            [['preferences'], 'string'],
            [['preferences'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['user_phid', 'phid'], 'string', 'max' => 64],
            [['builtin_key'], 'string', 'max' => 32],
            [['phid'], 'unique'],
            [['user_phid'], 'unique'],
            [['builtin_key'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_phid' => Yii::t('app', 'User Phid'),
            'preferences' => Yii::t('app', 'Preferences'),
            'phid' => Yii::t('app', 'Phid'),
            'builtin_key' => Yii::t('app', 'Builtin Key'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     * @throws \PhutilJSONParserException

     * @author 陈妙威
     */
    public function getPreference($key, $default = null) {
        return ArrayHelper::getValue($this->getPreferences(), $key, $default);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     * @throws \PhutilJSONParserException

     * @throws \Exception
     * @author 陈妙威
     */
    public function setPreference($key, $value) {
        $preferences = $this->getPreferences();
        $preferences[$key] = $value;
        $this->preferences = phutil_json_encode($preferences);
        return $this;
    }

    /**
     * @param $key
     * @return $this
     * @throws \PhutilJSONParserException

     * @throws \Exception
     * @author 陈妙威
     */
    public function unsetPreference($key) {
        $preferences = $this->getPreferences();
        unset($preferences[$key]);
        $this->preferences = phutil_json_encode($preferences);
        return $this;
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorUserPreferencesPHIDType::class;
    }

    /**
     * @return PhabricatorUserPreferencesQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public static function find()
    {
        return Yii::createObject(PhabricatorUserPreferencesQuery::class, [get_called_class()]);
    }

    /**
     * Load or create a preferences object for the given user.
     *
     * @param PhabricatorUser $user
     * @return PhabricatorUserPreferences
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     */
    public static function loadUserPreferences(PhabricatorUser $user)
    {
        return PhabricatorUserPreferences::find()
            ->setViewer($user)
            ->withUsers(array($user))
            ->needSyntheticPreferences(true)
            ->executeOne();
    }

    /**
     * @return array

     * @throws \PhutilJSONParserException
     */
    public function getPreferences()
    {
        return $this->preferences ? OranginsUtil::phutil_json_decode($this->preferences) : [];
    }



    /**
     * @return PhabricatorUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param PhabricatorUser $user
     * @return static
     */
    public function setUser(PhabricatorUser $user)
    {
        $this->user = $user;
        return $this;
    }


    /**
     * Load or create a global preferences object.
     *
     * If no global preferences exist, an empty preferences object is returned.
     *
     * @param PhabricatorUser $viewer
     * @return PhabricatorUserPreferences
     * @throws \yii\base\InvalidConfigException
     */
    public static function loadGlobalPreferences(PhabricatorUser $viewer)
    {
        $global = PhabricatorUserPreferences::find()
            ->setViewer($viewer)
            ->withBuiltinKeys(
                array(
                    self::BUILTIN_GLOBAL_DEFAULT,
                ))
            ->one();

        if (!$global) {
            $global = (new self())
                ->attachUser(new PhabricatorUser());
        }

        return $global;
    }

    /**
     * @param PhabricatorUser|null $user
     * @return $this
     * @author 陈妙威
     */
    public function attachUser(PhabricatorUser $user = null)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param $key
     * @return mixed|null
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    public function getSettingValue($key)
    {
        if (array_key_exists($key, $this->getPreferences())) {
            return $this->getPreferences()[$key];
        }

        return $this->getDefaultValue($key);
    }

    /**
     * @param $key
     * @return mixed|null
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    public function getDefaultValue($key)
    {
        if ($this->defaultSettings) {
            return $this->defaultSettings->getSettingValue($key);
        }
        $setting = self::getSettingObject($key);
        if (!$setting) {
            return null;
        }
        $phabricatorSetting = clone $setting;
        $phabricatorSetting->setViewer($this->getUser());

        return $phabricatorSetting->getSettingDefaultValue();
    }

    /**
     * @param $key
     * @return PhabricatorSetting
     * @author 陈妙威
     */
    private static function getSettingObject($key)
    {
        $settings = PhabricatorSetting::getAllSettings();
        return ArrayHelper::getValue($settings, $key);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function hasManagedUser() {
        $user_phid = $this->getUserPHID();
        if (!$user_phid) {
            return false;
        }

        $user = $this->getUser();
        if ($user->getIsSystemAgent() || $user->getIsMailingList()) {
            return true;
        }

        return false;
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
    public function getBuiltinKey()
    {
        return $this->builtin_key;
    }

    /**
     * @param string $builtin_key
     * @return self
     */
    public function setBuiltinKey($builtin_key)
    {
        $this->builtin_key = $builtin_key;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return \orangins\modules\transactions\models\PhabricatorApplicationTransaction
     * @throws \PhutilJSONParserException
     * @throws \Exception
     * @author 陈妙威
     */
    public function newTransaction($key, $value) {
        $setting_property = PhabricatorUserPreferencesTransaction::PROPERTY_SETTING;
        $xaction_type = PhabricatorUserPreferencesTransaction::TYPE_SETTING;

        $transaction = clone $this->getApplicationTransactionTemplate();
        return $transaction
            ->setTransactionType($xaction_type)
            ->setMetadataValue($setting_property, $key)
            ->setNewValue($value);
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
     * @throws \Exception
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                $user_phid = $this->getUserPHID();
                if ($user_phid) {
                    return $user_phid;
                }

                return PhabricatorPolicies::getMostOpenPolicy();
            case PhabricatorPolicyCapability::CAN_EDIT:
                if ($this->hasManagedUser()) {
                    return PhabricatorPolicies::POLICY_ADMIN;
                }
                $user_phid = $this->getUserPHID();
                if ($user_phid) {
                    return $user_phid;
                }
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
        if ($this->hasManagedUser()) {
            if ($viewer->getIsAdmin()) {
                return true;
            }
        }

        switch ($this->getBuiltinKey()) {
            case self::BUILTIN_GLOBAL_DEFAULT:
                // NOTE: Without this policy exception, the logged-out viewer can not
                // see global preferences.
                return true;
        }

        return false;
    }


    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return PhabricatorUserPreferencesEditor|\orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorUserPreferencesEditor();
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
     * @return PhabricatorUserPreferencesTransaction|\orangins\modules\transactions\models\PhabricatorApplicationTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorUserPreferencesTransaction();
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
}
