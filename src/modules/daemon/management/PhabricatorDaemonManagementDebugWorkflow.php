<?php

namespace orangins\modules\daemon\management;

use PhutilArgumentParser;
use PhutilArgumentUsageException;

/**
 * Class PhabricatorDaemonManagementDebugWorkflow
 * @package orangins\modules\daemon\management
 * @author 陈妙威
 */
final class PhabricatorDaemonManagementDebugWorkflow
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
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('debug')
            ->setExamples('**debug** __daemon__')
            ->setSynopsis(
                \Yii::t("app",
                    'Start __daemon__ in the foreground and print large volumes of ' .
                    'diagnostic information to the console.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'argv',
                        'wildcard' => true,
                    ),
                    array(
                        'name' => 'pool',
                        'param' => 'count',
                        'help' => \Yii::t("app",'Maximum pool size.'),
                        'default' => 1,
                    ),
                    array(
                        'name' => 'as-current-user',
                        'help' => \Yii::t("app",
                            'Run the daemon as the current user ' .
                            'instead of the configured %s',
                            'phd.user'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return mixed
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
        $run_as_current_user = $args->getArg('as-current-user');

        if (!$argv) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",'You must specify which daemon to debug.'));
        }

        $config = array(
            'class' => array_shift($argv),
            'label' => 'debug',
            'pool' => (int)$args->getArg('pool'),
            'argv' => $argv,
        );

        return $this->launchDaemons(
            array(
                $config,
            ),
            $is_debug = true,
            $run_as_current_user);
    }

}
