<?php

namespace orangins\modules\daemon\models;

use orangins\lib\db\ActiveRecord;
use orangins\modules\daemon\query\PhabricatorDaemonLogQuery;
use Yii;

/**
 * This is the model class for table "daemon_log".
 *
 * @property int $id
 * @property string $daemon
 * @property string $host
 * @property int $pid
 * @property string $argv
 * @property string $explicit_argv
 * @property string $running_as_user
 * @property string $daemon_id
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorDaemonLog extends ActiveRecord
{
    /**
     *
     */
    const STATUS_UNKNOWN = 'unknown';
    /**
     *
     */
    const STATUS_RUNNING = 'run';
    /**
     *
     */
    const STATUS_DEAD    = 'dead';
    /**
     *
     */
    const STATUS_WAIT    = 'wait';
    /**
     *
     */
    const STATUS_EXITING  = 'exiting';
    /**
     *
     */
    const STATUS_EXITED  = 'exit';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'daemon_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pid', 'status'], 'integer'],
            [['argv', 'explicit_argv'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['daemon', 'host', 'running_as_user'], 'string', 'max' => 255],
            [['daemon_id'], 'string', 'max' => 64],
            [['status'], 'string', 'max' => 16],
            [['daemon_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'daemon' => Yii::t('app', 'Daemon'),
            'host' => Yii::t('app', 'Host'),
            'pid' => Yii::t('app', 'Pid'),
            'argv' => Yii::t('app', 'Argv'),
            'explicit_argv' => Yii::t('app', 'Explicit Argv'),
            'running_as_user' => Yii::t('app', 'Running As User'),
            'daemon_id' => Yii::t('app', 'Daemon ID'),
            'status' => Yii::t('app', 'State'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return PhabricatorDaemonLogQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorDaemonLogQuery(get_called_class());
    }

    /**
     * @return string
     */
    public function getDaemon()
    {
        return $this->daemon;
    }

    /**
     * @param string $daemon
     * @return self
     */
    public function setDaemon($daemon)
    {
        $this->daemon = $daemon;
        return $this;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return self
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @param int $pid
     * @return self
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
        return $this;
    }

    /**
     * @return string
     */
    public function getArgv()
    {
        return $this->argv;
    }

    /**
     * @param string $argv
     * @return self
     */
    public function setArgv($argv)
    {
        $this->argv = $argv;
        return $this;
    }

    /**
     * @return string
     */
    public function getExplicitArgv()
    {
        return $this->explicit_argv;
    }

    /**
     * @param string $explicit_argv
     * @return self
     */
    public function setExplicitArgv($explicit_argv)
    {
        $this->explicit_argv = $explicit_argv;
        return $this;
    }

    /**
     * @return string
     */
    public function getRunningAsUser()
    {
        return $this->running_as_user;
    }

    /**
     * @param string $running_as_user
     * @return self
     */
    public function setRunningAsUser($running_as_user)
    {
        $this->running_as_user = $running_as_user;
        return $this;
    }

    /**
     * @return string
     */
    public function getDaemonID()
    {
        return $this->daemon_id;
    }

    /**
     * @param string $daemon_id
     * @return self
     */
    public function setDaemonID($daemon_id)
    {
        $this->daemon_id = $daemon_id;
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
}
