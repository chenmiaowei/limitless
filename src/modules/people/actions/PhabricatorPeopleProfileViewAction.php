<?php

namespace orangins\modules\people\actions;

use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\feed\builder\PhabricatorFeedBuilder;
use orangins\modules\feed\models\PhabricatorFeedStoryData;
use orangins\modules\people\engine\PhabricatorPeopleProfileMenuEngine;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorPeopleProfileViewAction
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleProfileViewAction
    extends PhabricatorPeopleProfileAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return Aphront404Response|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $username = $request->getURIData('username');
        $id = $request->getURIData('id');

        $user = null;
        if ($username) {
            $user = PhabricatorUser::find()
                ->setViewer($viewer)
                ->withUsernames(array($username))
                ->needProfileImage(true)
                ->needAvailability(true)
                ->executeOne();
        }

        if ($id) {
            $user = PhabricatorUser::find()
                ->setViewer($viewer)
                ->withIDs(array($id))
                ->needProfileImage(true)
                ->needAvailability(true)
                ->executeOne();
        }

        if (!$user) {
            return new Aphront404Response();
        }

        $this->setUser($user);
        $header = $this->buildProfileHeader();

        $properties = $this->buildPropertyView($user);
        $name = $user->getUsername();

        $feed = $this->buildPeopleFeed($user, $viewer);

//        $view_all = (new PHUIButtonView())
//            ->setTag('a')
//            ->setIcon(
//                (new PHUIIconView())
//                    ->setIcon('fa-list-ul'))
//            ->setText(\Yii::t("app",'View All'))
//            ->setHref('/feed/?userPHIDs=' . $user->getPHID());

        $feed_header = (new PHUIHeaderView())
            ->setHeader(\Yii::t("app", 'Recent Activity'));
//            ->addActionLink($view_all);

        $feed = (new PHUIObjectBoxView())
            ->setHeader($feed_header)
            ->addClass('project-view-feed')
            ->appendChild($feed);

//        $projects = $this->buildProjectsView($user);
//        $calendar = $this->buildCalendarDayView($user);

        $home = (new PHUITwoColumnView())
            ->addClass('project-view-home')
            ->addClass('project-view-people-home')
            ->setMainColumn(
                array(
                    $properties,
                    $feed,
                ))
            ->setSideColumn(
                array(
//                    $projects,
//                    $calendar,
                ));

        $nav = $this->newNavigation(
            $user,
            PhabricatorPeopleProfileMenuEngine::ITEM_PROFILE);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->setBorder(true);

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($user->getUsername())
            ->setNavigation($nav)
            ->setCrumbs($crumbs)
            ->appendChild(
                array(
                    $home,
                ));
    }

    /**
     * @param PhabricatorUser $user
     * @return null|PHUIObjectBoxView|PHUIPropertyListView
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildPropertyView(
        PhabricatorUser $user)
    {

        $viewer = $this->getRequest()->getViewer();
        $view = (new PHUIPropertyListView())
            ->setUser($viewer)
            ->setObject($user);

        $field_list = PhabricatorCustomField::getObjectFields(
            $user,
            PhabricatorCustomField::ROLE_VIEW);
        $field_list->appendFieldsToPropertyList($user, $viewer, $view);

        if (!$view->hasAnyProperties()) {
            return null;
        }

        $header = (new PHUIHeaderView())
            ->setHeader(\Yii::t("app", 'User Details'));

        $view = (new PHUIObjectBoxView())
            ->appendChild($view)
            ->setHeader($header)
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->addClass('project-view-properties');

        return $view;
    }


    /**
     * @param PhabricatorUser $user
     * @param $viewer
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function buildPeopleFeed(
        PhabricatorUser $user,
        $viewer)
    {

        $query = PhabricatorFeedStoryData::find();
        $query->withFilterPHIDs(
            array(
                $user->getPHID(),
            ));
        $query->setLimit(100);
        $query->setViewer($viewer);
        $stories = $query->execute();

        $builder = new PhabricatorFeedBuilder($stories);
        $builder->setUser($viewer);
        $builder->setShowHovercards(true);
        $builder->setNoDataString(\Yii::t("app", 'To begin on such a grand journey, ' .
            'requires but just a single step.'));
        $view = $builder->buildView();

        return $view;
    }
}
