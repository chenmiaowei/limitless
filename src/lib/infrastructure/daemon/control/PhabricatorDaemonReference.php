<?php

namespace orangins\lib\infrastructure\daemon\control;

use AphrontQueryException;
use Filesystem;
use orangins\lib\OranginsObject;
use orangins\modules\daemon\models\PhabricatorDaemonLog;
use orangins\modules\people\models\PhabricatorUser;
use PhutilJSONParserException;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorDaemonReference
 * @package orangins\lib\infrastructure\daemon\control
 * @author 陈妙威
 */
final class PhabricatorDaemonReference extends OranginsObject
{

    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $argv;
    /**
     * @var
     */
    private $pid;
    /**
     * @var
     */
    private $start;
    /**
     * @var
     */
    private $pidFile;

    /**
     * @var
     */
    private $daemonLog;

    /**
     * @param $path
     * @return array
     * @throws \FilesystemException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public static function loadReferencesFromFile($path)
    {
        $pid_data = Filesystem::readFile($path);

        try {
            $dict = phutil_json_decode($pid_data);
        } catch (PhutilJSONParserException $ex) {
            $dict = array();
        }

        $refs = array();
        $daemons = ArrayHelper::getValue($dict, 'daemons', array());

        $logs = array();

        $daemon_ids = ipull($daemons, 'id');
        if ($daemon_ids) {
            try {
                $logs = PhabricatorDaemonLog::find()
                    ->setViewer(PhabricatorUser::getOmnipotentUser())
                    ->withDaemonIDs($daemon_ids)
                    ->execute();
            } catch (AphrontQueryException $ex) {
                // Ignore any issues here; getting this information only allows us
                // to provide a more complete picture of daemon status, and we want
                // these commands to work if the database is inaccessible.
            }

            $logs = mpull($logs, null, 'getDaemonID');
        }

        // Support PID files that use the old daemon format, where each overseer
        // had exactly one daemon. We can eventually remove this; they will still
        // be stopped by `phd stop --force` even if we don't identify them here.
        if (!$daemons && ArrayHelper::getValue($dict, 'name')) {
            $daemons = array(
                array(
                    'config' => array(
                        'class' => ArrayHelper::getValue($dict, 'name'),
                        'argv' => ArrayHelper::getValue($dict, 'argv', array()),
                    ),
                ),
            );
        }

        foreach ($daemons as $daemon) {
            $ref = new PhabricatorDaemonReference();

            // NOTE: This is the overseer PID, not the actual daemon process PID.
            // This is correct for checking status and sending signals (the only
            // things we do with it), but might be confusing. $daemon['pid'] has
            // the daemon PID, and we could expose that if we had some use for it.

            $ref->pid = ArrayHelper::getValue($dict, 'pid');
            $ref->start = ArrayHelper::getValue($dict, 'start');

            $config = ArrayHelper::getValue($daemon, 'config', array());
            $ref->name = ArrayHelper::getValue($config, 'class');
            $ref->argv = ArrayHelper::getValue($config, 'argv', array());

            $log = ArrayHelper::getValue($logs, ArrayHelper::getValue($daemon, 'id'));
            if ($log) {
                $ref->daemonLog = $log;
            }

            $ref->pidFile = $path;
            $refs[] = $ref;
        }

        return $refs;
    }

    /**
     * @param $new_status
     * @author 陈妙威
     */
    public function updateStatus($new_status)
    {
        if (!$this->daemonLog) {
            return;
        }

        try {
            $this->daemonLog
                ->setStatus($new_status)
                ->save();
        } catch (AphrontQueryException $ex) {
            // Ignore anything that goes wrong here.
        }
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPID()
    {
        return $this->pid;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getArgv()
    {
        return $this->argv;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEpochStarted()
    {
        return $this->start;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPIDFile()
    {
        return $this->pidFile;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDaemonLog()
    {
        return $this->daemonLog;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isRunning()
    {
        return self::isProcessRunning($this->getPID());
    }

    /**
     * @param $pid
     * @return bool
     * @author 陈妙威
     */
    public static function isProcessRunning($pid)
    {
        if (!$pid) {
            return false;
        }

        if (function_exists('posix_kill')) {
            // This may fail if we can't signal the process because we are running as
            // a different user (for example, we are 'apache' and the process is some
            // other user's, or we are a normal user and the process is root's), but
            // we can check the error code to figure out if the process exists.
            $is_running = posix_kill($pid, 0);
            if (posix_get_last_error() == 1) {
                // "Operation Not Permitted", indicates that the PID exists. If it
                // doesn't, we'll get an error 3 ("No such process") instead.
                $is_running = true;
            }
        } else {
            // If we don't have the posix extension, just exec.
            list($err) = exec_manual('ps %s', $pid);
            $is_running = ($err == 0);
        }

        return $is_running;
    }

    /**
     * @param $seconds
     * @return bool
     * @author 陈妙威
     */
    public function waitForExit($seconds)
    {
        $start = time();
        while (time() < $start + $seconds) {
            usleep(100000);
            if (!$this->isRunning()) {
                return true;
            }
        }
        return !$this->isRunning();
    }

}
