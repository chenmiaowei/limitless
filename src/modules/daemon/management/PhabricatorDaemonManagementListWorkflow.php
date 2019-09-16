<?php

namespace orangins\modules\daemon\management;

use PhutilArgumentParser;
use PhutilConsole;

/**
 * Class PhabricatorDaemonManagementListWorkflow
 * @package orangins\modules\daemon\management
 * @author 陈妙威
 */
final class PhabricatorDaemonManagementListWorkflow
    extends PhabricatorDaemonManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('list')
            ->setSynopsis(\Yii::t("app",'Show a list of available daemons.'))
            ->setArguments(array());
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();

        $symbols = $this->loadAvailableDaemonClasses();
        $symbols = igroup($symbols, 'library');

        foreach ($symbols as $library => $symbol_list) {
            $console->writeOut(\Yii::t("app",'Daemons in library __%s__:', $library) . "\n");
            foreach ($symbol_list as $symbol) {
                $console->writeOut("    %s\n", $symbol['name']);
            }
            $console->writeOut("\n");
        }

        return 0;
    }


}
