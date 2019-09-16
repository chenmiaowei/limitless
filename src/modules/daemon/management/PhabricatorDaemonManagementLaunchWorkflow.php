<?php

namespace orangins\modules\daemon\management;

use PhutilArgumentParser;
use PhutilArgumentUsageException;

/**
 * Class PhabricatorDaemonManagementLaunchWorkflow
 * @package orangins\modules\daemon\management
 * @author 陈妙威
 */
final class PhabricatorDaemonManagementLaunchWorkflow
    extends PhabricatorDaemonManagementWorkflow
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldParsePartial()
    {
        return true;
    }

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('launch')
            ->setExamples('**launch** [n] __daemon__ [options]')
            ->setSynopsis(\Yii::t("app",
                'Start a specific __daemon__, or __n__ copies of a specific ' .
                '__daemon__.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'argv',
                        'wildcard' => true,
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int
     * @throws PhutilArgumentUsageException
     * @throws \CommandException
     * @throws \FilesystemException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilProxyException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $argv = $args->getArg('argv');

        $daemon_count = 1;
        if ($argv) {
            if (is_numeric(head($argv))) {
                $daemon_count = array_shift($argv);
            }

            if ($daemon_count < 1) {
                throw new PhutilArgumentUsageException(
                    \Yii::t("app",'You must launch at least one daemon.'));
            }
        }

        if (!$argv) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",'You must specify which daemon to launch.'));
        }

        $daemon = array();
        $daemon['class'] = array_shift($argv);
        $daemon['label'] = $daemon['class'];
        $daemon['argv'] = $argv;

        $daemons = array_fill(0, $daemon_count, $daemon);

        $this->launchDaemons($daemons, $is_debug = false);

        return 0;
    }
}
