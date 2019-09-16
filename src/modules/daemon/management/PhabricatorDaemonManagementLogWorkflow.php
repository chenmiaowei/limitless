<?php

namespace orangins\modules\daemon\management;

use orangins\modules\daemon\models\PhabricatorDaemonLog;
use orangins\modules\daemon\models\PhabricatorDaemonLogEvent;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilConsole;

/**
 * Class PhabricatorDaemonManagementLogWorkflow
 * @package orangins\modules\daemon\management
 * @author 陈妙威
 */
final class PhabricatorDaemonManagementLogWorkflow
    extends PhabricatorDaemonManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('log')
            ->setExamples('**log** [__options__]')
            ->setSynopsis(
                \Yii::t("app",
                    'Print the logs for all daemons, or some daemon(s) identified by ' .
                    'ID. You can get the ID for a daemon from the Daemon Console in ' .
                    'the web interface.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'id',
                        'param' => 'id',
                        'help' => \Yii::t("app",'Show logs for daemon(s) with given ID(s).'),
                        'repeat' => true,
                    ),
                    array(
                        'name' => 'limit',
                        'param' => 'N',
                        'default' => 100,
                        'help' => \Yii::t("app",
                            'Show a specific number of log messages (default 100).'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws PhutilArgumentUsageException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {

        $query = PhabricatorDaemonLog::find()
            ->setViewer($this->getViewer())
            ->setAllowStatusWrites(true);
        $ids = $args->getArg('id');
        if ($ids) {
            $query->withIDs($ids);
        }
        $daemons = $query->execute();
        $daemons = mpull($daemons, null, 'getID');

        if ($ids) {
            foreach ($ids as $id) {
                if (!isset($daemons[$id])) {
                    throw new PhutilArgumentUsageException(
                        \Yii::t("app",
                            'No log record exists for a daemon with ID "%s".',
                            $id));
                }
            }
        } else if (!$daemons) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",'No log records exist for any daemons.'));
        }

        $console = PhutilConsole::getConsole();

        $limit = $args->getArg('limit');

        $logs = (new PhabricatorDaemonLogEvent())->loadAllWhere(
            'logID IN (%Ld) ORDER BY id DESC LIMIT %d',
            mpull($daemons, 'getID'),
            $limit);
        $logs = array_reverse($logs);

        $lines = array();
        foreach ($logs as $log) {
            $text_lines = phutil_split_lines($log->getMessage(), $retain = false);
            foreach ($text_lines as $line) {
                $lines[] = array(
                    'id' => $log->getLogID(),
                    'type' => $log->getLogType(),
                    'date' => $log->getEpoch(),
                    'data' => $line,
                );
            }
        }

        // Each log message may be several lines. Limit the number of lines we
        // output so that `--limit 123` means "show 123 lines", which is the most
        // easily understandable behavior.
        $lines = array_slice($lines, -$limit);

        foreach ($lines as $line) {
            $id = $line['id'];
            $type = $line['type'];
            $data = $line['data'];
            $date = date('r', $line['date']);

            $console->writeOut(
                "%s\n",
                \Yii::t("app",
                    'Daemon %d %s [%s] %s',
                    $id,
                    $type,
                    $date,
                    $data));
        }

        return 0;
    }


}
