<?php

namespace orangins\modules\people\actions;

use orangins\lib\PhabricatorApplication;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\people\engine\PhabricatorPeopleProfileMenuEngine;

/**
 * Class PhabricatorPeopleProfileRevisionsController
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleProfileRevisionsController
    extends PhabricatorPeopleProfileAction
{

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView|Aphront404Response
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $id = $request->getURIData('id');

        $user = PhabricatorUser::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->needProfile(true)
            ->needProfileImage(true)
            ->needAvailability(true)
            ->executeOne();
        if (!$user) {
            return new Aphront404Response();
        }

        $class = 'PhabricatorDifferentialApplication';
        if (!PhabricatorApplication::isClassInstalledForViewer($class, $viewer)) {
            return new Aphront404Response();
        }

        $this->setUser($user);
        $title = array(\Yii::t("app", 'Recent Revisions'), $user->getUsername());
        $header = $this->buildProfileHeader();
        $commits = $this->buildRevisionsView($user);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'Recent Revisions'));
        $crumbs->setBorder(true);

        $nav = $this->buildNavigation(
            $user,
            PhabricatorPeopleProfileMenuEngine::ITEM_REVISIONS);

        $view = (new PHUITwoColumnView())
            ->setHeader($header)
            ->addClass('project-view-home')
            ->addClass('project-view-people-home')
            ->setFooter(array(
                $commits,
            ));

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->setNavigation($nav)
            ->appendChild($view);
    }

    /**
     * @param PhabricatorUser $user
     * @return mixed
     * @author 陈妙威
     */
    private function buildRevisionsView(PhabricatorUser $user)
    {
        $viewer = $this->getViewer();

        $revisions = (new DifferentialRevisionQuery())
            ->setViewer($viewer)
            ->withAuthors(array($user->getPHID()))
            ->needFlags(true)
            ->needDrafts(true)
            ->needReviewers(true)
            ->setLimit(100)
            ->execute();

        $list = (new DifferentialRevisionListView())
            ->setViewer($viewer)
            ->setNoBox(true)
            ->setRevisions($revisions)
            ->setNoDataString(\Yii::t("app", 'No recent revisions.'));

        $view = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Recent Revisions'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->appendChild($list);

        return $view;
    }
}
