<?php

namespace orangins\modules\file\conduit;

use orangins\modules\file\query\PhabricatorFileSearchEngine;
use orangins\modules\search\engine\PhabricatorSearchEngineAPIMethod;

/**
 * Class PhabricatorFileSearchConduitAPIMethod
 * @package orangins\modules\file\conduit
 * @author 陈妙威
 */
final class PhabricatorFileSearchConduitAPIMethod
    extends PhabricatorSearchEngineAPIMethod
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getAPIMethodName()
    {
        return 'file.search';
    }

    /**
     * @return PhabricatorFileSearchEngine
     * @author 陈妙威
     */
    public function newSearchEngine()
    {
        return new PhabricatorFileSearchEngine();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodSummary()
    {
        return pht('Read information about files.');
    }
}
