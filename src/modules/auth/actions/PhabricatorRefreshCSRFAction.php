<?php

namespace orangins\modules\auth\actions;

use orangins\lib\response\AphrontAjaxResponse;

/**
 * Class PhabricatorRefreshCSRFAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorRefreshCSRFAction extends PhabricatorAuthAction
{

    /**
     * @return AphrontAjaxResponse
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        return (new AphrontAjaxResponse())
            ->setContent(
                array(
                    'token' => $viewer->getCSRFToken(),
                ));
    }

}
