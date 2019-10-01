<?php

namespace orangins\lib\infrastructure\daemon\workers\storage;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\infrastructure\daemon\contentsource\PhabricatorBulkContentSource;
use orangins\lib\infrastructure\daemon\workers\bulk\PhabricatorWorkerBulkJobType;
use orangins\lib\infrastructure\daemon\workers\editor\PhabricatorWorkerBulkJobEditor;
use orangins\lib\infrastructure\daemon\workers\phid\PhabricatorWorkerBulkJobPHIDType;
use orangins\lib\infrastructure\daemon\workers\query\PhabricatorWorkerBulkJobQuery;
use orangins\lib\infrastructure\edges\interfaces\PhabricatorEdgeInterface;
use orangins\lib\request\AphrontRequest;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\subscriptions\interfaces\PhabricatorSubscribableInterface;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\system\interfaces\PhabricatorDestructibleInterface;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * This is the model class for table "worker_bulkjob".
 *
 * @property int $id
 * @property string $phid
 * @property string $author_phid
 * @property string $job_type_key
 * @property string $status
 * @property string $parameters
 * @property int $size
 * @property int $is_silent
 * @property string $created_at
 * @property string $updated_at
 */
final class PhabricatorWorkerBulkJob
    extends ActiveRecordPHID
    implements
    PhabricatorPolicyInterface,
    PhabricatorSubscribableInterface,
    PhabricatorApplicationTransactionInterface,
    PhabricatorDestructibleInterface,
    PhabricatorEdgeInterface
{

    /**
     *
     */
    const STATUS_CONFIRM = 'confirm';
    /**
     *
     */
    const STATUS_WAITING = 'waiting';
    /**
     *
     */
    const STATUS_RUNNING = 'running';
    /**
     *
     */
    const STATUS_COMPLETE = 'complete';


    /**
     * @var string
     */
    private $jobImplementation = self::ATTACHABLE;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'worker_bulkjob';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parameters'], 'string'],
            [['size', 'is_silent'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'author_phid'], 'string', 'max' => 64],
            [['job_type_key', 'status'], 'string', 'max' => 32],
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
            'phid' => Yii::t('app', 'PHID'),
            'author_phid' => Yii::t('app', 'Author PHID'),
            'job_type_key' => Yii::t('app', 'Job Type Key'),
            'status' => Yii::t('app', 'State'),
            'parameters' => Yii::t('app', 'Parameters'),
            'size' => Yii::t('app', 'Size'),
            'is_silent' => Yii::t('app', 'Is Silent'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param PhabricatorUser $actor
     * @param PhabricatorWorkerBulkJobType $type
     * @param array $parameters
     * @return mixed
     * @author 陈妙威
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \Exception
     */
    public static function initializeNewJob(
        PhabricatorUser $actor,
        PhabricatorWorkerBulkJobType $type,
        array $parameters)
    {

        $job = (new PhabricatorWorkerBulkJob())
            ->setAuthorPHID($actor->getPHID())
            ->setJobTypeKey($type->getBulkJobTypeKey())
            ->setParameters($parameters)
            ->attachJobImplementation($type)
            ->setIsSilent(0);

        $job->setSize($job->computeSize());

        return $job;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    public function generatePHID()
    {
        return PhabricatorPHID::generateNewPHID(
            PhabricatorWorkerBulkJobPHIDType::TYPECONST);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMonitorURI()
    {
        return Url::to([
            '/daemon/bulk/monitor',
            'id' => $this->getID(),
        ]);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getManageURI()
    {
        return Url::to([
            '/daemon/bulk/view',
            'id' => $this->getID(),
        ]);
    }

    /**
     * @param string $parameters
     * @return self
     * @throws \Exception
     */
    public function setParameters($parameters)
    {
        $this->parameters = phutil_json_encode($parameters);
        return $this;
    }


    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters === null ? [] : phutil_json_decode($this->parameters);
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     * @author 陈妙威
     */
    public function getParameter($key, $default = null)
    {
        return ArrayHelper::getValue($this->getParameters(), $key, $default);
    }

    /**
     * @param $key
     * @param $value
     * @return self
     * @throws \Exception
     */
    public function setParameter($key, $value)
    {
        $parameters = $this->getParameters();
        $parameters[$key] = $value;
        $this->parameters = phutil_json_encode($parameters);
        return $this;
    }

    /**
     * @author 陈妙威
     * @throws \yii\db\Exception
     */
    public function loadTaskStatusCounts()
    {
        $table = new PhabricatorWorkerBulkTask();
        $rows = $table->getDb()->createCommand("SELECT status, COUNT(*) N FROM {$table::tableName()} WHERE bulk_job_phid = :bulk_job_phid GROUP BY status", [
            ":bulk_job_phid" => $this->getPHID()
        ])->queryAll();
        return ipull($rows, 'N', 'status');
    }

    /**
     * @return PhabricatorContentSource
     * @throws \ReflectionException
     */
    public function newContentSource()
    {
        return PhabricatorContentSource::newForSource(
            PhabricatorBulkContentSource::SOURCECONST,
            array(
                'jobID' => $this->getID(),
            ));
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getStatusIcon()
    {
        $map = array(
            self::STATUS_CONFIRM => 'fa-question',
            self::STATUS_WAITING => 'fa-clock-o',
            self::STATUS_RUNNING => 'fa-clock-o',
            self::STATUS_COMPLETE => 'fa-check grey',
        );

        return ArrayHelper::getValue($map, $this->getStatus(), 'none');
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getStatusName()
    {
        $map = array(
            self::STATUS_CONFIRM => \Yii::t("app",'Confirming'),
            self::STATUS_WAITING => \Yii::t("app",'Waiting'),
            self::STATUS_RUNNING => \Yii::t("app",'Running'),
            self::STATUS_COMPLETE => \Yii::t("app",'Complete'),
        );

        return ArrayHelper::getValue($map, $this->getStatus(), $this->getStatus());
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isConfirming()
    {
        return ($this->getStatus() == self::STATUS_CONFIRM);
    }


    /* -(  Job Implementation  )------------------------------------------------- */


    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    protected function getJobImplementation()
    {
        return $this->assertAttached($this->jobImplementation);
    }

    /**
     * @param PhabricatorWorkerBulkJobType $type
     * @return $this
     * @author 陈妙威
     */
    public function attachJobImplementation(PhabricatorWorkerBulkJobType $type)
    {
        $this->jobImplementation = $type;
        return $this;
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    private function computeSize()
    {
        return $this->getJobImplementation()->getJobSize($this);
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getCancelURI()
    {
        return $this->getJobImplementation()->getCancelURI($this);
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getDoneURI()
    {
        return $this->getJobImplementation()->getDoneURI($this);
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getDescriptionForConfirm()
    {
        return $this->getJobImplementation()->getDescriptionForConfirm($this);
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function createTasks()
    {
        return $this->getJobImplementation()->createTasks($this);
    }

    /**
     * @param PhabricatorUser $actor
     * @param PhabricatorWorkerBulkTask $task
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function runTask(
        PhabricatorUser $actor,
        PhabricatorWorkerBulkTask $task)
    {
        return $this->getJobImplementation()->runTask($actor, $this, $task);
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getJobName()
    {
        return $this->getJobImplementation()->getJobName($this);
    }

    /**
     * @param PhabricatorUser $viewer
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getCurtainActions(PhabricatorUser $viewer)
    {
        return $this->getJobImplementation()->getCurtainActions($viewer, $this);
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
                return PhabricatorPolicies::getMostOpenPolicy();
            case PhabricatorPolicyCapability::CAN_EDIT:
                return $this->getAuthorPHID();
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

    /**
     * @param $capability
     * @return null|string
     * @author 陈妙威
     */
    public function describeAutomaticCapability($capability)
    {
        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_EDIT:
                return \Yii::t("app",'Only the owner of a bulk job can edit it.');
            default:
                return null;
        }
    }


    /* -(  PhabricatorSubscribableInterface  )----------------------------------- */


    /**
     * @param $phid
     * @return bool
     * @author 陈妙威
     */
    public function isAutomaticallySubscribed($phid)
    {
        return false;
    }


    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return PhabricatorWorkerBulkJobEditor|\orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorWorkerBulkJobEditor();
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
     * @return PhabricatorWorkerBulkJobTransaction|\orangins\modules\transactions\models\PhabricatorApplicationTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorWorkerBulkJobTransaction();
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

    /* -(  PhabricatorDestructibleInterface  )----------------------------------- */


    /**
     * @param PhabricatorDestructionEngine $engine
     * @return mixed|void
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function destroyObjectPermanently(
        PhabricatorDestructionEngine $engine)
    {

        $this->openTransaction();

        // We're only removing the actual task objects. This may leave stranded
        // workers in the queue itself, but they'll just flush out automatically
        // when they can't load bulk job data.

        PhabricatorWorkerBulkTask::deleteAll([
            'bulk_job_phid' => $this->getPHID()
        ]);
        $this->delete();
        $this->saveTransaction();
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
    public function getJobTypeKey()
    {
        return $this->job_type_key;
    }

    /**
     * @param string $job_type_key
     * @return self
     */
    public function setJobTypeKey($job_type_key)
    {
        $this->job_type_key = $job_type_key;
        return $this;
    }


    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int $size
     * @return self
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * @return int
     */
    public function getisSilent()
    {
        return $this->is_silent;
    }

    /**
     * @param int $is_silent
     * @return self
     */
    public function setIsSilent($is_silent)
    {
        $this->is_silent = $is_silent;
        return $this;
    }

    /**
     * @return PhabricatorWorkerBulkJobQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorWorkerBulkJobQuery(get_called_class());
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorWorkerBulkJobPHIDType::className();
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
     * @author 陈妙威
     */
    public function edgeBaseTableName()
    {
        return 'worker';
    }
}
