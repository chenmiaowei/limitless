<?php

namespace orangins\modules\conduit\actions;

use orangins\modules\conduit\query\PhabricatorConduitLogSearchEngine;

/**
 * Class PhabricatorConduitLogController
 * @package orangins\modules\conduit\actions
 * @author 陈妙威
 */
final class PhabricatorConduitLogController
    extends PhabricatorConduitController
{

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        $request = $this->getRequest();
        return (new PhabricatorConduitLogSearchEngine())
            ->setAction($this)
            ->buildResponse();
    }

}
