<?php

namespace orangins\modules\herald\models;

use Exception;
use Filesystem;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\herald\phid\HeraldWebhookPHIDType;
use orangins\modules\herald\query\HeraldWebhookQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use PhutilInvalidStateException;
use ReflectionException;
use Yii;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\interfaces\PhabricatorEditableInterface;
use orangins\modules\herald\editors\HeraldWebhookEditor;
use yii\base\InvalidConfigException;
use yii\helpers\Url;

/**
 * This is the model class for table "herald_webhook".
 *
 * @property int $id
 * @property string $phid
 * @property string $name
 * @property string $webhook_uri
 * @property string $view_policy
 * @property string $edit_policy
 * @property string $status
 * @property string $hmac_key
 * @property int $created_at
 * @property int $updated_at
 */
class HeraldWebhook extends ActiveRecordPHID
    implements PhabricatorPolicyInterface
    , PhabricatorApplicationTransactionInterface
    , PhabricatorEditableInterface
{

    /**
     *
     */
    const HOOKSTATUS_FIREHOSE = 'firehose';
    /**
     *
     */
    const HOOKSTATUS_ENABLED = 'enabled';
    /**
     *
     */
    const HOOKSTATUS_DISABLED = 'disabled';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'herald_webhook';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'webhook_uri', 'view_policy', 'edit_policy', 'status', 'hmac_key'], 'required'],
            [['created_at', 'updated_at'], 'integer'],
            [['phid', 'view_policy', 'edit_policy'], 'string', 'max' => 64],
            [['name'], 'string', 'max' => 128],
            [['webhook_uri'], 'string', 'max' => 255],
            [['status', 'hmac_key'], 'string', 'max' => 32],
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
            'phid' => Yii::t('app', 'Phid'),
            'name' => Yii::t('app', 'Name'),
            'webhook_uri' => Yii::t('app', 'Webhook Uri'),
            'view_policy' => Yii::t('app', 'View Policy'),
            'edit_policy' => Yii::t('app', 'Edit Policy'),
            'status' => Yii::t('app', 'Status'),
            'hmac_key' => Yii::t('app', 'Hmac Key'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
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
    public function getWebhookURI()
    {
        return $this->webhook_uri;
    }

    /**
     * @param string $webhook_uri
     * @return self
     */
    public function setWebhookURI($webhook_uri)
    {
        $this->webhook_uri = $webhook_uri;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getHmacKey()
    {
        return $this->hmac_key;
    }

    /**
     * @param string $hmac_key
     * @return self
     */
    public function setHmacKey($hmac_key)
    {
        $this->hmac_key = $hmac_key;
        return $this;
    }

    /**
     * @return string
     */
    public function getViewPolicy()
    {
        return $this->view_policy;
    }

    /**
     * @param string $view_policy
     * @return self
     */
    public function setViewPolicy($view_policy)
    {
        $this->view_policy = $view_policy;
        return $this;
    }

    /**
     * @return string
     */
    public function getEditPolicy()
    {
        return $this->edit_policy;
    }

    /**
     * @param string $edit_policy
     * @return self
     */
    public function setEditPolicy($edit_policy)
    {
        $this->edit_policy = $edit_policy;
        return $this;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public static function initializeNewWebhook(PhabricatorUser $viewer)
    {
        return (new self())
            ->setStatus(self::HOOKSTATUS_ENABLED)
            ->setViewPolicy(PhabricatorPolicies::getMostOpenPolicy())
            ->setEditPolicy($viewer->getPHID())
            ->regenerateHMACKey();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isDisabled()
    {
        return ($this->getStatus() === self::HOOKSTATUS_DISABLED);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public static function getStatusDisplayNameMap()
    {
        $specs = self::getStatusSpecifications();
        return ipull($specs, 'name', 'key');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private static function getStatusSpecifications()
    {
        $specs = array(
            array(
                'key' => self::HOOKSTATUS_FIREHOSE,
                'name' => pht('Firehose'),
                'color' => 'orange',
                'icon' => 'fa-star-o',
            ),
            array(
                'key' => self::HOOKSTATUS_ENABLED,
                'name' => pht('Enabled'),
                'color' => 'bluegrey',
                'icon' => 'fa-check',
            ),
            array(
                'key' => self::HOOKSTATUS_DISABLED,
                'name' => pht('Disabled'),
                'color' => 'dark',
                'icon' => 'fa-ban',
            ),
        );

        return ipull($specs, null, 'key');
    }


    /**
     * @param $status
     * @return array
     * @author 陈妙威
     */
    private static function getSpecificationForStatus($status)
    {
        $specs = self::getStatusSpecifications();

        if (isset($specs[$status])) {
            return $specs[$status];
        }

        return array(
            'key' => $status,
            'name' => pht('Unknown ("%s")', $status),
            'icon' => 'fa-question',
            'color' => 'indigo',
        );
    }

    /**
     * @param $status
     * @return mixed
     * @author 陈妙威
     */
    public static function getDisplayNameForStatus($status)
    {
        $spec = self::getSpecificationForStatus($status);
        return $spec['name'];
    }

    /**
     * @param $status
     * @return mixed
     * @author 陈妙威
     */
    public static function getIconForStatus($status)
    {
        $spec = self::getSpecificationForStatus($status);
        return $spec['icon'];
    }

    /**
     * @param $status
     * @return mixed
     * @author 陈妙威
     */
    public static function getColorForStatus($status)
    {
        $spec = self::getSpecificationForStatus($status);
        return $spec['color'];
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getStatusDisplayName()
    {
        $status = $this->getStatus();
        return self::getDisplayNameForStatus($status);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getStatusIcon()
    {
        $status = $this->getStatus();
        return self::getIconForStatus($status);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getStatusColor()
    {
        $status = $this->getStatus();
        return self::getColorForStatus($status);
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getErrorBackoffWindow()
    {
        return phutil_units('5 minutes in seconds');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getErrorBackoffThreshold()
    {
        return 10;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return bool
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    public function isInErrorBackoff(PhabricatorUser $viewer)
    {
        $backoff_window = $this->getErrorBackoffWindow();
        $backoff_threshold = $this->getErrorBackoffThreshold();

        $now = PhabricatorTime::getNow();

        $window_start = ($now - $backoff_window);

        $requests = HeraldWebhookRequest::find()
            ->setViewer($viewer)
            ->withWebhookPHIDs(array($this->getPHID()))
            ->withLastRequestEpochBetween($window_start, null)
            ->withLastRequestResults(
                array(
                    HeraldWebhookRequest::RESULT_FAIL,
                ))
            ->execute();

        if (count($requests) >= $backoff_threshold) {
            return true;
        }

        return false;
    }

    /**
     * @return HeraldWebhook
     * @author 陈妙威
     */
    public function regenerateHMACKey()
    {
        return $this->setHMACKey(Filesystem::readRandomCharacters(32));
    }


    /**
     * {@inheritdoc}
     * @return HeraldWebhookQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new HeraldWebhookQuery(get_called_class());
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return HeraldWebhookPHIDType::class;
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
        return PhabricatorPolicies::POLICY_PUBLIC;
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return true;
    }

    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */
    /**
     * @return HeraldWebhookEditor
     */
    public function getApplicationTransactionEditor()
    {
        return new HeraldWebhookEditor();
    }

    /**
     * @return $this
     */
    public function getApplicationTransactionObject()
    {
        return $this;
    }

    /**
     * @return HeraldWebhookTransaction
     */
    public function getApplicationTransactionTemplate()
    {
        return new HeraldWebhookTransaction();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMonogram()
    {
        return $this->getID();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getInfoURI()
    {
        return Url::to(['/herald/webhook/view', 'id' => $this->getID()]);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getURI()
    {
        return Url::to(['/herald/webhook/view', 'id' => $this->getID()]);
    }
}
