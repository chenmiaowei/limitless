<?php

namespace orangins\modules\daemon\actions;

use orangins\lib\infrastructure\daemon\workers\query\PhabricatorWorkerBulkJobSearchEngine;

/**
 * Class PhabricatorDaemonBulkJobListController
 * @package orangins\modules\daemon\actions
 * @author 陈妙威
 */
final class PhabricatorDaemonBulkJobListController
    extends PhabricatorDaemonBulkJobController
{

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        $request = $this->getRequest();
        return (new PhabricatorWorkerBulkJobSearchEngine())
            ->setAction($this)
            ->buildResponse();
    }

}
