<?php

namespace orangins\modules\daemon\management;

use PhutilArgumentParser;

/**
 * Class PhabricatorDaemonManagementStartWorkflow
 * @package orangins\modules\daemon\management
 * @author 陈妙威
 */
final class PhabricatorDaemonManagementStartWorkflow
    extends PhabricatorDaemonManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('start')
            ->setSynopsis(
                \Yii::t("app",
                    'Start the standard configured collection of Phabricator daemons. ' .
                    'This is appropriate for most installs. Use **%s** to ' .
                    'customize which daemons are launched.',
                    'phd launch'))
            ->setArguments(
                array(
                    array(
                        'name' => 'keep-leases',
                        'help' => \Yii::t("app",
                            'By default, **{0}** will free all task leases held by ' .
                            'the daemons. With this flag, this step will be skipped.', [
                                'phd start'
                            ]),
                    ),
                    array(
                        'name' => 'force',
                        'help' => \Yii::t("app",'Start daemons even if daemons are already running.'),
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
        return $this->executeStartCommand(
            array(
                'keep-leases' => $args->getArg('keep-leases'),
                'force' => $args->getArg('force'),
                'reserve' => (float)$args->getArg('autoscale-reserve'),
            ));
    }

}
