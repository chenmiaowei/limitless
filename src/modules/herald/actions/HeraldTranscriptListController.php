<?php

namespace orangins\modules\herald\actions;

use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\modules\herald\query\HeraldTranscriptSearchEngine;
use PhutilURI;

/**
 * Class HeraldTranscriptListController
 * @package orangins\modules\herald\actions
 * @author 陈妙威
 */
final class HeraldTranscriptListController extends HeraldController
{

    /**
     * @return \orangins\lib\view\layout\AphrontSideNavFilterView|AphrontSideNavFilterView
     * @throws \PhutilMethodNotImplementedException
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSideNavView()
    {
        $user = $this->getRequest()->getViewer();

        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        (new HeraldTranscriptSearchEngine())
            ->setViewer($user)
            ->addNavigationItems($nav->getMenu());

        $nav->selectFilter(null);

        return $nav;
    }

    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();

        $crumbs->addTextCrumb(
            pht('Transcripts'),
            $this->getApplicationURI('transcript/'));
        return $crumbs;
    }

    /**
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        return (new HeraldTranscriptSearchEngine())
            ->setAction($this)
            ->buildResponse();
    }

}
