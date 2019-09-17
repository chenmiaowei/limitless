<?php

namespace orangins\modules\daemon\management;

use ExecFuture;
use Filesystem;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\daemon\control\PhabricatorDaemonReference;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerActiveTask;
use orangins\lib\infrastructure\management\PhabricatorManagementWorkflow;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\daemon\models\PhabricatorDaemonLog;
use orangins\modules\daemon\query\PhabricatorDaemonLogQuery;
use orangins\modules\people\models\PhabricatorUser;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilConsole;
use PhutilDaemonOverseer;
use PhutilSymbolLoader;
use PhutilTypeSpec;
use ReflectionExtension;
use TempFile;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorDaemonManagementWorkflow
 * @package orangins\modules\daemon\management
 * @author 陈妙威
 */
abstract class PhabricatorDaemonManagementWorkflow
    extends PhabricatorManagementWorkflow
{

    /**
     * @var null
     */
    private $runDaemonsAsUser = null;

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function loadAvailableDaemonClasses()
    {
        return (new PhutilSymbolLoader())
            ->setAncestorClass('PhutilDaemon')
            ->setConcreteOnly(true)
            ->selectSymbolsWithoutLoading();
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final protected function getPIDDirectory()
    {
        $path = PhabricatorEnv::getEnvConfig('phd.pid-directory');
        return $this->getControlDirectory($path);
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final protected function getLogDirectory()
    {
        $path = PhabricatorEnv::getEnvConfig('phd.log-directory');
        return $this->getControlDirectory($path);
    }

    /**
     * @param $path
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    private function getControlDirectory($path)
    {
        if (!Filesystem::pathExists($path)) {
            list($err) = exec_manual('mkdir -p %s', $path);
            if ($err) {
                throw new Exception(
                    \Yii::t("app",
                        "{0} requires the directory '{1}' to exist, but it does not exist " .
                        "and could not be created. Create this directory or update " .
                        "'{2}' / '{3}' in your configuration to point to an existing " .
                        "directory.", [
                            'phd',
                            $path,
                            'phd.pid-directory',
                            'phd.log-directory'
                        ]));
            }
        }
        return $path;
    }

    /**
     * @return array
     * @throws \FilesystemException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws Exception
     * @author 陈妙威
     */
    final protected function loadRunningDaemons()
    {
        $daemons = array();

        $pid_dir = $this->getPIDDirectory();
        $pid_files = Filesystem::listDirectory($pid_dir);

        foreach ($pid_files as $pid_file) {
            $path = $pid_dir . '/' . $pid_file;
            $daemons[] = PhabricatorDaemonReference::loadReferencesFromFile($path);
        }

        return array_mergev($daemons);
    }

    /**
     * @return array
     * @throws Exception
     * @throws \FilesystemException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final protected function loadAllRunningDaemons()
    {
        $local_daemons = $this->loadRunningDaemons();

        $local_ids = array();
        foreach ($local_daemons as $daemon) {
            $daemon_log = $daemon->getDaemonLog();

            if ($daemon_log) {
                $local_ids[] = $daemon_log->getID();
            }
        }

        $daemon_query = PhabricatorDaemonLog::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withStatus(PhabricatorDaemonLogQuery::STATUS_ALIVE);

        if ($local_ids) {
            $daemon_query->withoutIDs($local_ids);
        }

        $remote_daemons = $daemon_query->execute();

        return array_merge($local_daemons, $remote_daemons);
    }

    /**
     * @param $substring
     * @return object
     * @author 陈妙威
     * @throws PhutilArgumentUsageException
     */
    private function findDaemonClass($substring)
    {
        $symbols = $this->loadAvailableDaemonClasses();

        $symbols = ipull($symbols, 'name');
        $match = array();
        foreach ($symbols as $symbol) {
            if (stripos($symbol, $substring) !== false) {
                if (strtolower($symbol) == strtolower($substring)) {
                    $match = array($symbol);
                    break;
                } else {
                    $match[] = $symbol;
                }
            }
        }

        if (count($match) == 0) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    "No daemons match '{0}'! Use '{1}' for a list of available daemons.",[
                        $substring,
                        'phd list'
                    ]));
        } else if (count($match) > 1) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    "Specify a daemon unambiguously. Multiple daemons match '{0}': {1}.", [
                        $substring,
                        implode(', ', $match)
                    ]));
        }

        return head($match);
    }

    /**
     * @param array $daemons
     * @param $debug
     * @param bool $run_as_current_user
     * @throws PhutilArgumentUsageException
     * @throws \FilesystemException
     * @throws \yii\base\Exception
     * @throws Exception
     * @author 陈妙威
     */
    final protected function launchDaemons(
        array $daemons,
        $debug,
        $run_as_current_user = false)
    {

        // Convert any shorthand classnames like "taskmaster" into proper class
        // names.
        foreach ($daemons as $key => $daemon) {
            $class = $this->findDaemonClass($daemon['class']);
            $daemons[$key]['class'] = $class;
        }

        $console = PhutilConsole::getConsole();

        if (!$run_as_current_user) {
            // Check if the script is started as the correct user
            $phd_user = PhabricatorEnv::getEnvConfig('phd.user');
            $current_user = posix_getpwuid(posix_geteuid());
            $current_user = $current_user['name'];
            if ($phd_user && $phd_user != $current_user) {
                if ($debug) {
                    throw new PhutilArgumentUsageException(
                        \Yii::t("app",
                            "You are trying to run a daemon as a nonstandard user, " .
                            "and `{0}` was not able to `{1}` to the correct user. \n" .
                            'Phabricator is configured to run daemons as "{2}", ' .
                            'but the current user is "{3}". ' . "\n" .
                            'Use `{4}` to run as a different user, pass `{5}` to ignore this ' .
                            'warning, or edit `{6}` to change the configuration.', [
                                'phd',
                                'sudo',
                                $phd_user,
                                $current_user,
                                'sudo',
                                '--as-current-user',
                                'phd.user'
                            ]));
                } else {
                    $this->runDaemonsAsUser = $phd_user;
                    $console->writeOut(\Yii::t("app",'Starting daemons as {0}', [
                            $phd_user
                        ]) . "\n");
                }
            }
        }

        $this->printLaunchingDaemons($daemons, $debug);

        $trace = PhutilArgumentParser::isTraceModeEnabled();

        $flags = array();
        if ($trace || PhabricatorEnv::getEnvConfig('phd.trace')) {
            $flags[] = '--trace';
        }

        if ($debug || PhabricatorEnv::getEnvConfig('phd.verbose')) {
            $flags[] = '--verbose';
        }

        $instance = PhabricatorEnv::getEnvConfig('cluster.instance');
        if ($instance) {
            $flags[] = '-l';
            $flags[] = $instance;
        }

        $config = array();

        if (!$debug) {
            $config['daemonize'] = true;
        }

        if (!$debug) {
            $config['log'] = $this->getLogDirectory() . '/daemons.log';
        }

        $pid_dir = $this->getPIDDirectory();

        // TODO: This should be a much better user experience.
        Filesystem::assertExists($pid_dir);
        Filesystem::assertIsDirectory($pid_dir);
        Filesystem::assertWritable($pid_dir);

        $config['inidir'] =  \Yii::getAlias(\Yii::$app->scriptsPath) . "/__init_script__.php";
        $config['piddir'] = $pid_dir;
        $config['daemons'] = $daemons;

        $command = csprintf('./phd-daemon %Ls', $flags);

        $daemon_script_dir = \Yii::getAlias(\Yii::$app->scriptsPath) . '/daemon/';

        if ($debug) {
            // Don't terminate when the user sends ^C; it will be sent to the
            // subprocess which will terminate normally.
            pcntl_signal(
                SIGINT,
                array(__CLASS__, 'ignoreSignal'));

            $alias = \Yii::getAlias(\Yii::$app->scriptsPath);
            echo "\n    $alias/daemon/ \$ {$command}\n\n";

            $tempfile = new TempFile('daemon.config');
            Filesystem::writeFile($tempfile, json_encode($config));

            phutil_passthru(
                '(cd %s && exec %C < %s)',
                $daemon_script_dir,
                $command,
                $tempfile);
        } else {
            try {
                $this->executeDaemonLaunchCommand(
                    $command,
                    $daemon_script_dir,
                    $config,
                    $this->runDaemonsAsUser);
            } catch (Exception $ex) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app",
                        'Daemons are configured to run as user "{0}" in configuration ' .
                        'option `{1}`, but the current user is "{2}" and `phd` was unable ' .
                        'to switch to the correct user with `sudo`. Command output:' .
                        "\n\n" .
                        '{3}', [
                            $phd_user,
                            'phd.user',
                            $current_user,
                            $ex->getMessage()
                        ]));
            }
        }
    }

    /**
     * @param $command
     * @param $daemon_script_dir
     * @param array $config
     * @param null $run_as_user
     * @throws Exception
     * @throws \CommandException
     * @throws \PhutilProxyException
     * @author 陈妙威
     */
    private function executeDaemonLaunchCommand(
        $command,
        $daemon_script_dir,
        array $config,
        $run_as_user = null)
    {

        $is_sudo = false;
        if ($run_as_user) {
            // If anything else besides sudo should be
            // supported then insert it here (runuser, su, ...)
            $command = csprintf(
                'sudo -En -u %s -- %C',
                $run_as_user,
                $command);
            $is_sudo = true;
        }
        $future = new ExecFuture('exec %C', $command);
        // Play games to keep 'ps' looking reasonable.
        $future->setCWD($daemon_script_dir);
        $json_encode = json_encode($config);
        $future->write($json_encode);
        list($stdout, $stderr) = $future->resolvex();

        if ($is_sudo) {
            // On OSX, `sudo -n` exits 0 when the user does not have permission to
            // switch accounts without a password. This is not consistent with
            // sudo on Linux, and seems buggy/broken. Check for this by string
            // matching the output.
            if (preg_match('/sudo: a password is required/', $stderr)) {
                throw new Exception(
                    \Yii::t("app",
                        '%s exited with a zero exit code, but emitted output ' .
                        'consistent with failure under OSX.',
                        'sudo'));
            }
        }
    }

    /**
     * @param $signo
     * @author 陈妙威
     */
    public static function ignoreSignal($signo)
    {
        return;
    }

    /**
     * @author 陈妙威
     * @throws \ReflectionException
     */
    public static function requireExtensions()
    {
        self::mustHaveExtension('pcntl');
        self::mustHaveExtension('posix');
    }

    /**
     * @param $ext
     * @author 陈妙威
     * @throws \ReflectionException
     */
    private static function mustHaveExtension($ext)
    {
        if (!extension_loaded($ext)) {
            echo \Yii::t("app",
                "ERROR: The PHP extension '{0}' is not installed. You must " .
                "install it to run daemons on this machine.\n", [
                    $ext
                ]);
            exit(1);
        }

        $extension = new ReflectionExtension($ext);
        foreach ($extension->getFunctions() as $function) {
            $function = $function->name;
            if (!function_exists($function)) {
                echo \Yii::t("app",
                    "ERROR: The PHP function %s is disabled. You must " .
                    "enable it to run daemons on this machine.\n",
                    $function . '()');
                exit(1);
            }
        }
    }


    /* -(  Commands  )----------------------------------------------------------- */


    /**
     * @param array $options
     * @return int
     * @throws PhutilArgumentUsageException
     * @throws \FilesystemException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws Exception
     * @author 陈妙威
     */
    final protected function executeStartCommand(array $options)
    {
        PhutilTypeSpec::checkMap(
            $options,
            array(
                'keep-leases' => 'optional bool',
                'force' => 'optional bool',
                'reserve' => 'optional float',
            ));

        $console = PhutilConsole::getConsole();

        if (!ArrayHelper::getValue($options, 'force')) {
            $running = $this->loadRunningDaemons();

            // This may include daemons which were launched but which are no longer
            // running; check that we actually have active daemons before failing.
            foreach ($running as $daemon) {
                if ($daemon->isRunning()) {
                    $message = \Yii::t("app",
                        "phd start: Unable to start daemons because daemons are already " .
                        "running.\n\n" .
                        "You can view running daemons with '{0}'.\n" .
                        "You can stop running daemons with '{1}'.\n" .
                        "You can use '{2}' to stop all daemons before starting " .
                        "new daemons.\n" .
                        "You can force daemons to start anyway with {3}.", [

                            'phd status',
                            'phd stop',
                            'phd restart',
                            '--force'
                        ]);

                    $console->writeErr("%s\n", $message);
                    exit(1);
                }
            }
        }

        if (ArrayHelper::getValue($options, 'keep-leases')) {
            $console->writeErr("%s\n", \Yii::t("app",'Not touching active task queue leases.'));
        } else {
            $console->writeErr("%s\n", \Yii::t("app",'Freeing active task leases...'));
            $count = $this->freeActiveLeases();
            $console->writeErr(
                "%s\n",
                \Yii::t("app",'Freed {0} task lease(s).', [$count]));
        }

        $daemons = array(
            array(
                'class' => 'PhabricatorTriggerDaemon',
                'label' => 'trigger',
            ),
            array(
                'class' => 'PhabricatorTaskmasterDaemon',
                'label' => 'task',
                'pool' => PhabricatorEnv::getEnvConfig('phd.taskmasters'),
                'reserve' => ArrayHelper::getValue($options, 'reserve', 0),
            ),
        );

        $this->launchDaemons($daemons, $is_debug = false);

        $console->writeErr("%s\n", \Yii::t("app",'Done.'));
        return 0;
    }

    /**
     * @param array $pids
     * @param array $options
     * @return int
     * @throws Exception
     * @throws PhutilArgumentUsageException
     * @throws \FilesystemException
     * @author 陈妙威
     */
    final protected function executeStopCommand(
        array $pids,
        array $options)
    {

        $console = PhutilConsole::getConsole();

        $grace_period = ArrayHelper::getValue($options, 'graceful', 15);
        $force = ArrayHelper::getValue($options, 'force');
        $gently = ArrayHelper::getValue($options, 'gently');

        if ($gently && $force) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'You can not specify conflicting options %s and %s together.',
                    '--gently',
                    '--force'));
        }

        $daemons = $this->loadRunningDaemons();
        if (!$daemons) {
            $survivors = array();
            if (!$pids && !$gently) {
                $survivors = $this->processRogueDaemons(
                    $grace_period,
                    $warn = true,
                    $force);
            }
            if (!$survivors) {
                $console->writeErr(
                    "%s\n",
                    \Yii::t("app",'There are no running Phabricator daemons.'));
            }
            return 0;
        }

        $stop_pids = $this->selectDaemonPIDs($daemons, $pids);

        if (!$stop_pids) {
            $console->writeErr("%s\n", \Yii::t("app",'No daemons to kill.'));
            return 0;
        }

        $survivors = $this->sendStopSignals($stop_pids, $grace_period);

        // Try to clean up PID files for daemons we killed.
        $remove = array();
        foreach ($daemons as $daemon) {
            $pid = $daemon->getPID();
            if (empty($stop_pids[$pid])) {
                // We did not try to stop this overseer.
                continue;
            }

            if (isset($survivors[$pid])) {
                // We weren't able to stop this overseer.
                continue;
            }

            if (!$daemon->getPIDFile()) {
                // We don't know where the PID file is.
                continue;
            }

            $remove[] = $daemon->getPIDFile();
        }

        foreach (array_unique($remove) as $remove_file) {
            Filesystem::remove($remove_file);
        }

        if (!$gently) {
            $this->processRogueDaemons($grace_period, !$pids, $force);
        }

        return 0;
    }

    /**
     * @param array $pids
     * @return int
     * @throws \FilesystemException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final protected function executeReloadCommand(array $pids)
    {
        $console = PhutilConsole::getConsole();

        $daemons = $this->loadRunningDaemons();
        if (!$daemons) {
            $console->writeErr(
                "%s\n",
                \Yii::t("app",'There are no running daemons to reload.'));
            return 0;
        }

        $reload_pids = $this->selectDaemonPIDs($daemons, $pids);
        if (!$reload_pids) {
            $console->writeErr(
                "%s\n",
                \Yii::t("app",'No daemons to reload.'));
            return 0;
        }

        foreach ($reload_pids as $pid) {
            $console->writeOut(
                "%s\n",
                \Yii::t("app",'Reloading process %d...', $pid));
            posix_kill($pid, SIGHUP);
        }

        return 0;
    }

    /**
     * @param $grace_period
     * @param $warn
     * @param $force_stop
     * @return mixed
     * @author 陈妙威
     */
    private function processRogueDaemons($grace_period, $warn, $force_stop)
    {
        $console = PhutilConsole::getConsole();

        $rogue_daemons = PhutilDaemonOverseer::findRunningDaemons();
        if ($rogue_daemons) {
            if ($force_stop) {
                $rogue_pids = ipull($rogue_daemons, 'pid');
                $survivors = $this->sendStopSignals($rogue_pids, $grace_period);
                if ($survivors) {
                    $console->writeErr(
                        "%s\n",
                        \Yii::t("app",
                            'Unable to stop processes running without PID files. ' .
                            'Try running this command again with sudo.'));
                }
            } else if ($warn) {
                $console->writeErr("%s\n", $this->getForceStopHint($rogue_daemons));
            }
        }

        return $rogue_daemons;
    }

    /**
     * @param $rogue_daemons
     * @return string
     * @author 陈妙威
     */
    private function getForceStopHint($rogue_daemons)
    {
        $debug_output = '';
        foreach ($rogue_daemons as $rogue) {
            $debug_output .= $rogue['pid'] . ' ' . $rogue['command'] . "\n";
        }
        return \Yii::t("app",
            "There are processes running that look like Phabricator daemons but " .
            "have no corresponding PID files:\n\n{0}\n\n" .
            "Stop these processes by re-running this command with the {1} parameter.", [
                $debug_output,
                '--force'
            ]);
    }

    /**
     * @param $pids
     * @param $grace_period
     * @return array
     * @author 陈妙威
     */
    private function sendStopSignals($pids, $grace_period)
    {
        // If we're doing a graceful shutdown, try SIGINT first.
        if ($grace_period) {
            $pids = $this->sendSignal($pids, SIGINT, $grace_period);
        }

        // If we still have daemons, SIGTERM them.
        if ($pids) {
            $pids = $this->sendSignal($pids, SIGTERM, 15);
        }

        // If the overseer is still alive, SIGKILL it.
        if ($pids) {
            $pids = $this->sendSignal($pids, SIGKILL, 0);
        }

        return $pids;
    }

    /**
     * @param array $pids
     * @param $signo
     * @param $wait
     * @return array
     * @author 陈妙威
     */
    private function sendSignal(array $pids, $signo, $wait)
    {
        $console = PhutilConsole::getConsole();

        $pids = array_fuse($pids);

        foreach ($pids as $key => $pid) {
            if (!$pid) {
                // NOTE: We must have a PID to signal a daemon, since sending a signal
                // to PID 0 kills this process.
                unset($pids[$key]);
                continue;
            }

            switch ($signo) {
                case SIGINT:
                    $message = \Yii::t("app",'Interrupting process {0}...', [$pid]);
                    break;
                case SIGTERM:
                    $message = \Yii::t("app",'Terminating process {0}...', [$pid]);
                    break;
                case SIGKILL:
                    $message = \Yii::t("app",'Killing process {0}...', [$pid]);
                    break;
            }

            $console->writeOut("%s\n", $message);
            posix_kill($pid, $signo);
        }

        if ($wait) {
            $start = PhabricatorTime::getNow();
            do {
                foreach ($pids as $key => $pid) {
                    if (!PhabricatorDaemonReference::isProcessRunning($pid)) {
                        $console->writeOut(\Yii::t("app",'Process {0} exited.', [$pid]) . "\n");
                        unset($pids[$key]);
                    }
                }
                if (empty($pids)) {
                    break;
                }
                usleep(100000);
            } while (PhabricatorTime::getNow() < $start + $wait);
        }

        return $pids;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \yii\db\Exception
     */
    private function freeActiveLeases()
    {
        $task_table = (new PhabricatorWorkerActiveTask());
        $getAffectedRows = $task_table->getDb()
            ->createCommand("UPDATE " . $task_table::tableName() . " SET lease_expires = UNIX_TIMESTAMP() WHERE lease_expires > UNIX_TIMESTAMP()")
            ->execute();
        return $getAffectedRows;
    }


    /**
     * @param array $daemons
     * @param $debug
     * @throws Exception
     * @author 陈妙威
     */
    private function printLaunchingDaemons(array $daemons, $debug)
    {
        $console = PhutilConsole::getConsole();

        if ($debug) {
            $console->writeOut(\Yii::t("app",'Launching daemons (in debug mode):'));
        } else {
            $console->writeOut(\Yii::t("app",'Launching daemons:'));
        }

        $log_dir = $this->getLogDirectory() . '/daemons.log';
        $console->writeOut(
            "\n%s\n\n",
            \Yii::t("app",'(Logs will appear in "{0}".)', [$log_dir]));

        foreach ($daemons as $daemon) {
            $pool_size = \Yii::t("app",'(Pool: {0})', [ArrayHelper::getValue($daemon, 'pool', 1)]);

            $console->writeOut(
                "    %s %s\n",
                $pool_size,
                $daemon['class'],
                implode(' ', ArrayHelper::getValue($daemon, 'argv', array())));
        }
        $console->writeOut("\n");
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getAutoscaleReserveArgument()
    {
        return array(
            'name' => 'autoscale-reserve',
            'param' => 'ratio',
            'help' => \Yii::t("app",
                'Specify a proportion of machine memory which must be free ' .
                'before autoscale pools will grow. For example, a value of 0.25 ' .
                'means that pools will not grow unless the machine has at least ' .
                '25%%%% of its RAM free.'),
        );
    }

    /**
     * @param array $daemons
     * @param array $pids
     * @return array
     * @author 陈妙威
     */
    private function selectDaemonPIDs(array $daemons, array $pids)
    {
        $console = PhutilConsole::getConsole();

        $running_pids = array_fuse(mpull($daemons, 'getPID'));
        if (!$pids) {
            $select_pids = $running_pids;
        } else {
            // We were given a PID or set of PIDs to kill.
            $select_pids = array();
            foreach ($pids as $key => $pid) {
                if (!preg_match('/^\d+$/', $pid)) {
                    $console->writeErr(\Yii::t("app","PID '%s' is not a valid PID.", $pid) . "\n");
                    continue;
                } else if (empty($running_pids[$pid])) {
                    $console->writeErr(
                        "%s\n",
                        \Yii::t("app",
                            'PID "%d" is not a known Phabricator daemon PID.',
                            $pid));
                    continue;
                } else {
                    $select_pids[$pid] = $pid;
                }
            }
        }

        return $select_pids;
    }

}
