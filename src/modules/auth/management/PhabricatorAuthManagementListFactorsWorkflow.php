<?php

namespace orangins\modules\auth\management;

use PhutilArgumentParser;
use PhutilConsole;

/**
 * Class PhabricatorAuthManagementListFactorsWorkflow
 * @package orangins\modules\auth\management
 * @author 陈妙威
 */
final class PhabricatorAuthManagementListFactorsWorkflow
    extends PhabricatorAuthManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('list-factors')
            ->setExamples('**list-factors**')
            ->setSynopsis(\Yii::t("app", 'List available multi-factor authentication factors.'))
            ->setArguments(array());
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $factors = PhabricatorAuthFactor::getAllFactors();

        $console = PhutilConsole::getConsole();
        foreach ($factors as $factor) {
            $console->writeOut(
                "%s\t%s\n",
                $factor->getFactorKey(),
                $factor->getFactorName());
        }

        return 0;
    }

}
