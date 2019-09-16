<?php

namespace orangins\modules\daemon\management;

use PhutilArgumentParser;

/**
 * Class PhabricatorDaemonManagementRestartWorkflow
 * @package orangins\modules\daemon\management
 * @author 陈妙威
 */
final class PhabricatorDaemonManagementRestartWorkflow
    extends PhabricatorDaemonManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('restart')
            ->setSynopsis(\Yii::t("app",'Stop, then start the standard daemon loadout.'))
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
                        'name' => 'gently',
                        'help' => \Yii::t("app",
                            'Ignore running processes that look like daemons but do not ' .
                            'have corresponding PID files.'),
                    ),
                    array(
                        'name' => 'force',
                        'help' => \Yii::t("app",
                            'Also stop running processes that look like daemons but do ' .
                            'not have corresponding PID files.'),
                    ),
                    $this->getAutoscaleReserveArgument(),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int
     * @throws \CommandException
     * @throws \FilesystemException
     * @throws \PhutilArgumentUsageException
     * @throws \PhutilProxyException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\base\Exception
     * @throws \PhutilArgumentSpecificationException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $err = $this->executeStopCommand(
            array(),
            array(
                'graceful' => $args->getArg('graceful'),
                'force' => $args->getArg('force'),
                'gently' => $args->getArg('gently'),
            ));
        if ($err) {
            return $err;
        }

        return $this->executeStartCommand(
            array(
                'reserve' => (float)$args->getArg('autoscale-reserve'),
            ));
    }

}
