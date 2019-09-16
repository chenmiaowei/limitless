<?php
namespace orangins\modules\daemon\worker\test;

use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTask;

/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/6/20
 * Time: 4:05 PM
 * Email: chenmiaowei0914@gmail.com
 */

class PhabricatorDaemonTestWorker extends PhabricatorWorker
{

    /**
     * @return int|null
     * @author 陈妙威
     */
    public function getMaximumRetryCount()
    {
        return 5;
    }

    /**
     * @param PhabricatorWorkerTask $task
     * @return int|null
     * @author 陈妙威
     */
    public function getWaitBeforeRetry(PhabricatorWorkerTask $task)
    {
        return phutil_units('1 minute in seconds');
    }

    /**
     * @return mixed|void
     * @author 陈妙威
     */
    protected function doWork()
    {
        $wild = $this->getTaskDataValue("ids");
        phlog('1111');
        return;
    }
}