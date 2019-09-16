<?php

namespace orangins\modules\feed\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\modules\feed\query\PhabricatorFeedSearchEngine;
use PhutilURI;

/**
 * Class PhabricatorFeedController
 * @package orangins\modules\feed\actions
 * @author 陈妙威
 */
abstract class PhabricatorFeedController extends PhabricatorAction
{

    /**
     * @return AphrontSideNavFilterView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    protected function buildSideNavView()
    {
        $user = $this->getRequest()->getViewer();

        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        (new PhabricatorFeedSearchEngine())
            ->setViewer($user)
            ->addNavigationItems($nav->getMenu());

        $nav->selectFilter(null);

        return $nav;
    }

    /**
     * @return null|\orangins\lib\view\phui\PHUIListView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        return $this->buildSideNavView()->getMenu();
    }

}
