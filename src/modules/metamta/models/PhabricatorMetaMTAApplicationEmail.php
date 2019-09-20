<?php

namespace orangins\modules\metamta\models;

use orangins\lib\db\ActiveRecord;
use orangins\lib\PhabricatorApplication;
use orangins\modules\metamta\query\PhabricatorMetaMTAApplicationEmailQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use PhutilEmailAddress;
use Yii;
use yii\helpers\ArrayHelper;

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
class PhabricatorMetaMTAApplicationEmail extends ActiveRecord
{
    /**
     * @var string
     */
    private $application = self::ATTACHABLE;

    /**
     *
     */
    const CONFIG_DEFAULT_AUTHOR = 'config:default:author';

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


    /**
     * @param PhabricatorUser $actor
     * @return mixed
     * @author 陈妙威
     */
    public static function initializeNewAppEmail(PhabricatorUser $actor)
    {
        return (new PhabricatorMetaMTAApplicationEmail())
            ->setSpacePHID($actor->getDefaultSpacePHID())
            ->setConfigData(array());
    }

    /**
     * @param PhabricatorApplication $app
     * @return $this
     * @author 陈妙威
     */
    public function attachApplication(PhabricatorApplication $app)
    {
        $this->application = $app;
        return $this;
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getApplication()
    {
        return self::assertAttached($this->application);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     * @throws \Exception
     * @author 陈妙威æ
     */
    public function setConfigValue($key, $value)
    {
        $config_data = $this->config_data === null ? [] : phutil_json_decode($this->config_data);
        $config_data[$key] = $value;
        $this->config_data = phutil_json_encode($config_data);
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return object
     * @author 陈妙威
     */
    public function getConfigValue($key, $default = null)
    {
        return ArrayHelper::getValue($this->config_data === null ? [] : phutil_json_decode($this->config_data), $key, $default);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getDefaultAuthorPHID()
    {
        return $this->getConfigValue(self::CONFIG_DEFAULT_AUTHOR);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getInUseMessage()
    {
        $applications = PhabricatorApplication::getAllApplications();
        $applications = mpull($applications, null, 'getPHID');
        $application = ArrayHelper::getValue(
            $applications,
            $this->getApplicationPHID());
        if ($application) {
            $message = pht(
                'The address %s is configured to be used by the %s Application.',
                $this->getAddress(),
                $application->getName());
        } else {
            $message = pht(
                'The address %s is configured to be used by an application.',
                $this->getAddress());
        }

        return $message;
    }

    /**
     * @return PhutilEmailAddress
     * @author 陈妙威
     */
    public function newAddress()
    {
        return new PhutilEmailAddress($this->getAddress());
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
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        return $this->getApplication()->getPolicy($capability);
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function hasAutomaticCapability(
        $capability,
        PhabricatorUser $viewer)
    {

        return $this->getApplication()->hasAutomaticCapability(
            $capability,
            $viewer);
    }

    /**
     * @param $capability
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function describeAutomaticCapability($capability)
    {
        return $this->getApplication()->describeAutomaticCapability($capability);
    }


    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return PhabricatorMetaMTAApplicationEmailEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorMetaMTAApplicationEmailEditor();
    }

    /**
     * @return PhabricatorMetaMTAApplicationEmailTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorMetaMTAApplicationEmailTransaction();
    }


    /* -(  PhabricatorDestructibleInterface  )----------------------------------- */


    /**
     * @param PhabricatorDestructionEngine $engine
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function destroyObjectPermanently(
        PhabricatorDestructionEngine $engine)
    {
        $this->delete();
    }


    /* -(  PhabricatorSpacesInterface  )----------------------------------------- */


    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSpacePHID()
    {
        return $this->space_phid;
    }

    /**
     * @param string $space_phid
     * @return self
     */
    public function setSpacePHID($space_phid)
    {
        $this->space_phid = $space_phid;
        return $this;
    }
}
