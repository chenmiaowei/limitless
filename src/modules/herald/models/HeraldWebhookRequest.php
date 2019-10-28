<?php

namespace orangins\modules\herald\models;

use AphrontQueryException;
use Exception;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\herald\phid\HeraldWebhookRequestPHIDType;
use orangins\modules\herald\query\HeraldWebhookRequestQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use Throwable;
use Yii;
use yii\db\IntegrityException;

/**
 * This is the model class for table "herald_webhookrequest".
 *
 * @property int $id
 * @property string $phid
 * @property string $webhook_phid
 * @property string $object_phid
 * @property string $status
 * @property string $properties
 * @property string $last_request_result
 * @property string $last_request_epoch
 * @property int $created_at
 * @property int $updated_at
 */
class HeraldWebhookRequest extends ActiveRecordPHID
    implements PhabricatorPolicyInterface
{
    /**
     * @var string
     */
    private $webhook = self::ATTACHABLE;

    /**
     *
     */
    const RETRY_NEVER = 'never';
    /**
     *
     */
    const RETRY_FOREVER = 'forever';

    /**
     *
     */
    const STATUS_QUEUED = 'queued';
    /**
     *
     */
    const STATUS_FAILED = 'failed';
    /**
     *
     */
    const STATUS_SENT = 'sent';

    /**
     *
     */
    const RESULT_NONE = 'none';
    /**
     *
     */
    const RESULT_OKAY = 'okay';
    /**
     *
     */
    const RESULT_FAIL = 'fail';

    /**
     *
     */
    const ERRORTYPE_HOOK = 'hook';
    /**
     *
     */
    const ERRORTYPE_HTTP = 'http';
    /**
     *
     */
    const ERRORTYPE_TIMEOUT = 'timeout';

    /**
     *
     */
    const ERROR_SILENT = 'silent';
    /**
     *
     */
    const ERROR_DISABLED = 'disabled';
    /**
     *
     */
    const ERROR_URI = 'uri';
    /**
     *
     */
    const ERROR_OBJECT = 'object';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'herald_webhookrequest';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['webhook_phid', 'object_phid', 'status', 'properties', 'last_request_result', 'last_request_epoch'], 'required'],
            [['created_at', 'updated_at'], 'integer'],
            [['phid', 'status', 'properties'], 'string', 'max' => 64],
            [['webhook_phid'], 'string', 'max' => 128],
            [['object_phid'], 'string', 'max' => 255],
            [['last_request_result', 'last_request_epoch'], 'string', 'max' => 32],
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
            'webhook_phid' => Yii::t('app', 'Webhook Phid'),
            'object_phid' => Yii::t('app', 'Object Phid'),
            'status' => Yii::t('app', 'Status'),
            'properties' => Yii::t('app', 'Properties'),
            'last_request_result' => Yii::t('app', 'Last Request Result'),
            'last_request_epoch' => Yii::t('app', 'Last Request Epoch'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string
     */
    public function getWebhookPHID()
    {
        return $this->webhook_phid;
    }

    /**
     * @param string $webhook_phid
     * @return self
     */
    public function setWebhookPHID($webhook_phid)
    {
        $this->webhook_phid = $webhook_phid;
        return $this;
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
     * @return array
     */
    public function getProperties()
    {
        return $this->properties === null ? [] : phutil_json_decode($this->properties);
    }

    /**
     * @param string $target
     * @return self
     * @throws Exception
     */
    public function setProperties($target)
    {
        $this->properties = $target === null ? null : phutil_json_encode($target);
        return $this;
    }


    /**
     * @return string
     */
    public function getLastRequestResult()
    {
        return $this->last_request_result;
    }

    /**
     * @param string $last_request_result
     * @return self
     */
    public function setLastRequestResult($last_request_result)
    {
        $this->last_request_result = $last_request_result;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastRequestEpoch()
    {
        return $this->last_request_epoch;
    }

    /**
     * @param string $last_request_epoch
     * @return self
     */
    public function setLastRequestEpoch($last_request_epoch)
    {
        $this->last_request_epoch = $last_request_epoch;
        return $this;
    }

    /**
     * @param HeraldWebhook $hook
     * @return HeraldWebhookRequest
     * @throws Exception
     * @author 陈妙威
     */
    public static function initializeNewWebhookRequest(HeraldWebhook $hook)
    {
        return (new self())
            ->setWebhookPHID($hook->getPHID())
            ->attachWebhook($hook)
            ->setStatus(self::STATUS_QUEUED)
            ->setRetryMode(self::RETRY_NEVER)
            ->setLastRequestResult(self::RESULT_NONE)
            ->setLastRequestEpoch(0);
    }

    /**
     * @return mixed
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getWebhook()
    {
        return $this->assertAttached($this->webhook);
    }

    /**
     * @param HeraldWebhook $hook
     * @return $this
     * @author 陈妙威
     */
    public function attachWebhook(HeraldWebhook $hook)
    {
        $this->webhook = $hook;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    protected function setProperty($key, $value)
    {
        $properties = $this->getProperties();
        $properties[$key] = $value;
        $this->setProperties($properties);
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return object
     * @author 陈妙威
     */
    protected function getProperty($key, $default = null)
    {
        return idx($this->getProperties(), $key, $default);
    }

    /**
     * @param $mode
     * @return HeraldWebhookRequest
     * @throws Exception
     * @author 陈妙威
     */
    public function setRetryMode($mode)
    {
        return $this->setProperty('retry', $mode);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getRetryMode()
    {
        return $this->getProperty('retry');
    }

    /**
     * @param $error_type
     * @return HeraldWebhookRequest
     * @throws Exception
     * @author 陈妙威
     */
    public function setErrorType($error_type)
    {
        return $this->setProperty('errorType', $error_type);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getErrorType()
    {
        return $this->getProperty('errorType');
    }

    /**
     * @param $error_code
     * @return HeraldWebhookRequest
     * @throws Exception
     * @author 陈妙威
     */
    public function setErrorCode($error_code)
    {
        return $this->setProperty('errorCode', $error_code);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getErrorCode()
    {
        return $this->getProperty('errorCode');
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getErrorTypeForDisplay()
    {
        $map = array(
            self::ERRORTYPE_HOOK => pht('Hook Error'),
            self::ERRORTYPE_HTTP => pht('HTTP Status Code'),
            self::ERRORTYPE_TIMEOUT => pht('Request Timeout'),
        );

        $type = $this->getErrorType();
        return idx($map, $type, $type);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getErrorCodeForDisplay()
    {
        $code = $this->getErrorCode();

        if ($this->getErrorType() !== self::ERRORTYPE_HOOK) {
            return $code;
        }

        $spec = $this->getHookErrorSpec($code);
        return idx($spec, 'display', $code);
    }

    /**
     * @param array $phids
     * @return HeraldWebhookRequest
     * @throws Exception
     * @author 陈妙威
     */
    public function setTransactionPHIDs(array $phids)
    {
        return $this->setProperty('transactionPHIDs', $phids);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getTransactionPHIDs()
    {
        return $this->getProperty('transactionPHIDs', array());
    }

    /**
     * @param array $phids
     * @return HeraldWebhookRequest
     * @throws Exception
     * @author 陈妙威
     */
    public function setTriggerPHIDs(array $phids)
    {
        return $this->setProperty('triggerPHIDs', $phids);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getTriggerPHIDs()
    {
        return $this->getProperty('triggerPHIDs', array());
    }

    /**
     * @param $bool
     * @return HeraldWebhookRequest
     * @throws Exception
     * @author 陈妙威
     */
    public function setIsSilentAction($bool)
    {
        return $this->setProperty('silent', $bool);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getIsSilentAction()
    {
        return $this->getProperty('silent', false);
    }

    /**
     * @param $bool
     * @return HeraldWebhookRequest
     * @throws Exception
     * @author 陈妙威
     */
    public function setIsTestAction($bool)
    {
        return $this->setProperty('test', $bool);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getIsTestAction()
    {
        return $this->getProperty('test', false);
    }

    /**
     * @param $bool
     * @return HeraldWebhookRequest
     * @throws Exception
     * @author 陈妙威
     */
    public function setIsSecureAction($bool)
    {
        return $this->setProperty('secure', $bool);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getIsSecureAction()
    {
        return $this->getProperty('secure', false);
    }

    /**
     * @return $this
     * @throws AphrontQueryException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws Throwable
     * @throws IntegrityException
     * @author 陈妙威
     */
    public function queueCall()
    {
        PhabricatorWorker::scheduleTask(
            'HeraldWebhookWorker',
            array(
                'webhookRequestPHID' => $this->getPHID(),
            ),
            array(
                'objectPHID' => $this->getPHID(),
            ));

        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function newStatusIcon()
    {
        switch ($this->getStatus()) {
            case self::STATUS_QUEUED:
                $icon = 'fa-refresh';
                $color = 'blue';
                $tooltip = pht('Queued');
                break;
            case self::STATUS_SENT:
                $icon = 'fa-check';
                $color = 'green';
                $tooltip = pht('Sent');
                break;
            case self::STATUS_FAILED:
            default:
                $icon = 'fa-times';
                $color = 'red';
                $tooltip = pht('Failed');
                break;

        }

        return (new PHUIIconView())
            ->setIcon($icon, $color)
            ->setTooltip($tooltip);
    }

    /**
     * @param $code
     * @return object
     * @author 陈妙威
     */
    private function getHookErrorSpec($code)
    {
        $map = $this->getHookErrorMap();
        return idx($map, $code, array());
    }

    /**
     * @return array
     * @author 陈妙威
     */
    private function getHookErrorMap()
    {
        return array(
            self::ERROR_SILENT => array(
                'display' => pht('In Silent Mode'),
            ),
            self::ERROR_DISABLED => array(
                'display' => pht('Hook Disabled'),
            ),
            self::ERROR_URI => array(
                'display' => pht('Invalid URI'),
            ),
            self::ERROR_OBJECT => array(
                'display' => pht('Invalid Object'),
            ),
        );
    }


    /**
     * {@inheritdoc}
     * @return HeraldWebhookRequestQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new HeraldWebhookRequestQuery(get_called_class());
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return HeraldWebhookRequestPHIDType::class;
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

}
