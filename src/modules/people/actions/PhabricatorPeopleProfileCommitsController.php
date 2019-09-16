<?php

namespace orangins\modules\people\actions;

use orangins\lib\PhabricatorApplication;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\people\engine\PhabricatorPeopleProfileMenuEngine;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorPeopleProfileCommitsController
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleProfileCommitsController
    extends PhabricatorPeopleProfileAction
{

    /**
     * @return Aphront404Response|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
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

        $class = 'PhabricatorDiffusionApplication';
        if (!PhabricatorApplication::isClassInstalledForViewer($class, $viewer)) {
            return new Aphront404Response();
        }

        $this->setUser($user);
        $title = array(\Yii::t("app", 'Recent Commits'), $user->getUsername());
        $header = $this->buildProfileHeader();
        $commits = $this->buildCommitsView($user);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'Recent Commits'));
        $crumbs->setBorder(true);

        $nav = $this->newNavigation(
            $user,
            PhabricatorPeopleProfileMenuEngine::ITEM_COMMITS);

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
    private function buildCommitsView(PhabricatorUser $user)
    {
        $viewer = $this->getViewer();

        $commits = (new DiffusionCommitQuery())
            ->setViewer($viewer)
            ->withAuthorPHIDs(array($user->getPHID()))
            ->needCommitData(true)
            ->setLimit(100)
            ->execute();

        $list = (new DiffusionCommitListView())
            ->setViewer($viewer)
            ->setCommits($commits)
            ->setNoDataString(\Yii::t("app", 'No recent commits.'));

        return $list;
    }
}
