<?php

namespace orangins\lib\infrastructure\daemon\garbagecollector\management;

use PhutilArgumentParser;

/**
 * Class PhabricatorGarbageCollectorManagementCollectWorkflow
 * @package orangins\lib\infrastructure\daemon\garbagecollector\management
 * @author 陈妙威
 */
final class PhabricatorGarbageCollectorManagementCollectWorkflow
    extends PhabricatorGarbageCollectorManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('collect')
            ->setExamples('**collect** --collector __collector__')
            ->setSynopsis(
                \Yii::t("app", 'Run a garbage collector in the foreground.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'collector',
                        'param' => 'const',
                        'help' => \Yii::t("app",
                            'Constant identifying the garbage collector to run.'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilArgumentUsageException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $collector = $this->getCollector($args->getArg('collector'));

        echo tsprintf(
            "%s\n",
            \Yii::t("app", 'Collecting "{0}" garbage...', [
                $collector->getCollectorName()
            ]));

        $any = false;
        while (true) {
            $more = $collector->runCollector();
            if ($more) {
                $any = true;
            } else {
                break;
            }
        }

        if ($any) {
            $message = \Yii::t("app", 'Finished collecting all the garbage.');
        } else {
            $message = \Yii::t("app", 'Could not find any garbage to collect.');
        }
        echo tsprintf("\n%s\n", $message);

        return 0;
    }

}
