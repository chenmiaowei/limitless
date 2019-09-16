<?php

namespace orangins\modules\config\management;

use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use PhutilArgumentParser;
use PhutilConsole;

/**
 * Class PhabricatorConfigManagementListWorkflow
 * @package orangins\modules\config\management
 * @author 陈妙威
 */
final class PhabricatorConfigManagementListWorkflow
    extends PhabricatorConfigManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('list')
            ->setExamples('**list**')
            ->setSynopsis(\Yii::t("app",'List all configuration keys.'));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int
     * @throws \Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $options = PhabricatorApplicationConfigOptions::loadAllOptions();
        ksort($options);

        $console = PhutilConsole::getConsole();
        foreach ($options as $option) {
            $console->writeOut($option->getKey() . "\n");
        }

        return 0;
    }
}
