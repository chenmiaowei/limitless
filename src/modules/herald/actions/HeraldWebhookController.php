<?php

namespace orangins\modules\herald\actions;

/**
 * Class HeraldWebhookController
 * @package orangins\modules\herald\actions
 * @author 陈妙威
 */
abstract class HeraldWebhookController extends HeraldController
{

    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();

        $crumbs->addTextCrumb(
            pht('Webhooks'),
            $this->getApplicationURI('webhook/'));

        return $crumbs;
    }

}
