<?php

namespace orangins\modules\file\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\modules\file\query\PhabricatorFileSearchEngine;

/**
 * Class PhabricatorFileController
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
abstract class PhabricatorFileAction extends PhabricatorAction
{
    /**
     * @return null
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        return $this->controller->newApplicationMenu()
            ->setSearchEngine(new PhabricatorFileSearchEngine());
    }
}
