<?php

namespace orangins\modules\daemon\management;

use PhutilArgumentParser;

/**
 * Class PhabricatorDaemonManagementStopWorkflow
 * @package orangins\modules\daemon\management
 * @author 陈妙威
 */
final class PhabricatorDaemonManagementStopWorkflow
    extends PhabricatorDaemonManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('stop')
            ->setSynopsis(
                \Yii::t("app",
                    'Stop all running daemons, or specific daemons identified by PIDs. ' .
                    'Use **%s** to find PIDs.',
                    'phd status'))
            ->setArguments(
                array(
                    array(
                        'name' => 'graceful',
                        'param' => 'seconds',
                        'help' => \Yii::t("app",
                            'Grace period for daemons to attempt a clean shutdown, in ' .
                            'seconds. Defaults to __15__ seconds.'),
                        'default' => 15,
                    ),
                    array(
                        'name' => 'force',
                        'help' => \Yii::t("app",
                            'Also stop running processes that look like daemons but do ' .
                            'not have corresponding PID files.'),
                    ),
                    array(
                        'name' => 'gently',
                        'help' => \Yii::t("app",
                            'Ignore running processes that look like daemons but do not ' .
                            'have corresponding PID files.'),
                    ),
                    array(
                        'name' => 'pids',
                        'wildcard' => true,
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int
     * @throws \FilesystemException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilArgumentUsageException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        return $this->executeStopCommand(
            $args->getArg('pids'),
            array(
                'graceful' => $args->getArg('graceful'),
                'force' => $args->getArg('force'),
                'gently' => $args->getArg('gently'),
            ));
    }

}
