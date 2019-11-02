<?php

namespace orangins\aphront\handler;

use orangins\lib\request\AphrontRequest;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorRequestExceptionHandler
 * @package orangins\aphront\handler
 * @author 陈妙威
 */
abstract class PhabricatorRequestExceptionHandler
    extends AphrontRequestExceptionHandler
{

    /**
     * @param AphrontRequest $request
     * @return bool
     * @author 陈妙威
     */
    protected function isPhabricatorSite(AphrontRequest $request)
    {
        $site = $request->getSite();
        if (!$site) {
            return false;
        }

        return ($site instanceof PhabricatorSite);
    }

    /**
     * @param AphrontRequest $request
     * @return PhabricatorUser
     * @author 陈妙威
     */
    protected function getViewer(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        if ($viewer) {
            return $viewer;
        }

        // If we hit an exception very early, we won't have a user yet.
        return new PhabricatorUser();
    }

}
