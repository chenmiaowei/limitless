<?php

namespace orangins\modules\daemon\management;

use PhutilArgumentParser;

/**
 * Class PhabricatorDaemonManagementReloadWorkflow
 * @package orangins\modules\daemon\management
 * @author 陈妙威
 */
final class PhabricatorDaemonManagementReloadWorkflow
    extends PhabricatorDaemonManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('reload')
            ->setSynopsis(
                \Yii::t("app",
                    'Gracefully restart daemon processes in-place to pick up changes ' .
                    'to source. This will not disrupt running jobs. This is an ' .
                    'advanced workflow; most installs should use __%s__.',
                    'phd restart'))
            ->setArguments(
                array(
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
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        return $this->executeReloadCommand($args->getArg('pids'));
    }
}
