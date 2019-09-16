<?php

namespace orangins\modules\userservice\bulk;

use orangins\modules\transactions\bulk\PhabricatorBulkEngine;
use orangins\modules\userservice\editors\PhabricatorUserServiceEditEngine;
use orangins\modules\userservice\query\PhabricatorUserServiceSearchEngine;

/**
 * Class ManiphestTaskBulkEngine
 * @package orangins\modules\userservice\bulk
 * @author 陈妙威
 */
final class ManiphestTaskBulkEngine
    extends PhabricatorBulkEngine
{
    /**
     * @return \orangins\modules\search\engine\PhabricatorApplicationSearchEngine|PhabricatorUserServiceSearchEngine
     * @author 陈妙威
     */
    public function newSearchEngine()
    {
        return new PhabricatorUserServiceSearchEngine();
    }

    /**
     * @return \orangins\modules\transactions\editengine\PhabricatorEditEngine|PhabricatorUserServiceEditEngine
     * @author 陈妙威
     */
    public function newEditEngine()
    {
        return new PhabricatorUserServiceEditEngine();
    }
}
