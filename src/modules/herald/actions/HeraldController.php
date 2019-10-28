<?php

namespace orangins\modules\herald\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\modules\herald\query\HeraldRuleSearchEngine;
use PhutilURI;

/**
 * Class HeraldController
 * @package orangins\modules\herald\actions
 * @author 陈妙威
 */
abstract class HeraldController extends PhabricatorAction
{

    /**
     * @return \orangins\lib\view\phui\PHUIListView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        return $this->buildSideNavView()->getMenu();
    }

    /**
     * @return AphrontSideNavFilterView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSideNavView()
    {
        $viewer = $this->getViewer();

        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        (new HeraldRuleSearchEngine())
            ->setViewer($viewer)
            ->addNavigationItems($nav->getMenu());

        $nav->addLabel(pht('Utilities'))
            ->addFilter('test', pht('Test Console'), $this->getApplicationURI('index/test'))
            ->addFilter('transcript', pht('Transcripts'), $this->getApplicationURI('transcript/query'));

        $nav->addLabel(pht('Webhooks'))
            ->addFilter('webhook', pht('Webhooks'), $this->getApplicationURI('webhook/query'));

        $nav->selectFilter(null);

        return $nav;
    }

}
