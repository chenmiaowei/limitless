<?php

namespace orangins\modules\daemon\garbagecollector;

use orangins\lib\infrastructure\daemon\garbagecollector\PhabricatorGarbageCollector;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerArchiveTask;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTaskData;

/**
 * Class PhabricatorDaemonTaskGarbageCollector
 * @package orangins\lib\infrastructure\garbagecollector
 * @author 陈妙威
 */
final class PhabricatorDaemonTaskGarbageCollector
    extends PhabricatorGarbageCollector
{

    /**
     *
     */
    const COLLECTORCONST = 'worker.tasks';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCollectorName()
    {
        return \Yii::t("app",'Archived Tasks');
    }

    /**
     * @return int|void
     * @author 陈妙威
     */
    public function getDefaultRetentionPolicy()
    {
        return phutil_units('14 days in seconds');
    }

    /**
     * @return bool
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    protected function collectGarbage()
    {
        $table = new PhabricatorWorkerArchiveTask();
        $tasks = PhabricatorWorkerArchiveTask::find()
            ->withDateCreatedBefore($this->getGarbageEpoch())
            ->setLimit(100)
            ->execute();
        if (!$tasks) {
            return false;
        }

        $data_ids = array_filter(mpull($tasks, 'getDataID'));
        $task_ids = mpull($tasks, 'getID');

        $table->openTransaction();
        if ($data_ids) {
            PhabricatorWorkerTaskData::deleteAll([
                'IN', 'id', $data_ids
            ]);
        }
        PhabricatorWorkerArchiveTask::deleteAll([
            'IN', 'id', $task_ids
        ]);
        $table->saveTransaction();
        return (count($task_ids) == 100);
    }
}
