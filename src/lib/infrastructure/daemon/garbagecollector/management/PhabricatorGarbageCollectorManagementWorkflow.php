<?php

namespace orangins\lib\infrastructure\daemon\garbagecollector\management;

use orangins\lib\infrastructure\daemon\garbagecollector\PhabricatorGarbageCollector;
use orangins\lib\infrastructure\management\PhabricatorManagementWorkflow;
use PhutilArgumentUsageException;

/**
 * Class PhabricatorGarbageCollectorManagementWorkflow
 * @package orangins\lib\infrastructure\daemon\garbagecollector\management
 * @author 陈妙威
 */
abstract class PhabricatorGarbageCollectorManagementWorkflow
    extends PhabricatorManagementWorkflow
{

    /**
     * @param $const
     * @return PhabricatorGarbageCollector
     * @throws PhutilArgumentUsageException
     * @author 陈妙威
     */
    protected function getCollector($const)
    {
        $collectors = PhabricatorGarbageCollector::getAllCollectors();

        $collector_list = array_keys($collectors);
        sort($collector_list);
        $collector_list = implode(', ', $collector_list);

        if (!$const) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'Specify a collector with "{0}". Valid collectors are: {1}.',
                    [
                        '--collector',
                        $collector_list
                    ]));
        }

        if (empty($collectors[$const])) {
            throw new PhutilArgumentUsageException(
                \Yii::t("app",
                    'No such collector "{0}". Choose a valid collector: {1}.',
                    [
                        $const,
                        $collector_list
                    ]));
        }

        return $collectors[$const];
    }

}
