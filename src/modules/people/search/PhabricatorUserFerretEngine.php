<?php

namespace orangins\modules\people\search;

use orangins\modules\people\query\PhabricatorPeopleSearchEngine;
use orangins\modules\search\ferret\PhabricatorFerretEngine;

/**
 * Class PhabricatorUserFerretEngine
 * @package orangins\modules\people\search
 * @author 陈妙威
 */
final class PhabricatorUserFerretEngine
    extends PhabricatorFerretEngine
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'user';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getScopeName()
    {
        return 'user';
    }

    /**
     * @return PhabricatorPeopleSearchEngine|\orangins\modules\search\engine\PhabricatorApplicationSearchEngine
     * @author 陈妙威
     */
    public function newSearchEngine()
    {
        return new PhabricatorPeopleSearchEngine();
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getObjectTypeRelevance()
    {
        // Always sort users above other documents, regardless of relevance
        // metrics. A user profile is very likely to be the best hit for a query
        // which matches a user.
        return 500;
    }

}
