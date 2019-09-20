<?php

namespace orangins\modules\people\actions;

use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\people\capability\PeopleDisableUsersCapability;
use orangins\modules\people\engine\PhabricatorPeopleProfileMenuEngine;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use yii\helpers\Url;

/**
 * Class PhabricatorPeopleProfileManageController
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleProfileManageController
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
     * @return \orangins\lib\view\page\PhabricatorStandardPageView|Aphront404Response
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilMethodNotImplementedException
     * @throws \Exception
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

        $this->setUser($user);
        $header = $this->buildProfileHeader();

        $curtain = $this->buildCurtain($user);
        $properties = $this->buildPropertyView($user);
        $name = $user->getUsername();

        $nav = $this->buildNavigation(
            $user,
            PhabricatorPeopleProfileMenuEngine::ITEM_MANAGE);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'Manage'));
        $crumbs->setBorder(true);

        $manage = (new PHUITwoColumnView())
            ->addClass('project-view-home')
            ->addClass('project-view-people-home')
            ->setCurtain($curtain)
            ->addPropertySection(\Yii::t("app", 'Details'), $properties);

        return $this->newPage()
            ->setHeader($header)
            ->setTitle(
                array(
                    \Yii::t("app", 'Manage User'),
                    $user->getUsername(),
                ))
            ->setNavigation($nav)
            ->setCrumbs($crumbs)
            ->appendChild(
                array(
                    $manage,
                ));
    }

    /**
     * @param PhabricatorUser $user
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildPropertyView(PhabricatorUser $user)
    {

        $viewer = $this->getRequest()->getViewer();
        $view = (new PHUIPropertyListView())
            ->setUser($viewer)
            ->setObject($user);

        $field_list = PhabricatorCustomField::getObjectFields(
            $user,
            PhabricatorCustomField::ROLE_VIEW);
        $field_list->appendFieldsToPropertyList($user, $viewer, $view);

        return $view;
    }

    /**
     * @param PhabricatorUser $user
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function buildCurtain(PhabricatorUser $user)
    {
        $viewer = $this->getViewer();

        $is_self = ($user->getPHID() === $viewer->getPHID());

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $user,
            PhabricatorPolicyCapability::CAN_EDIT);

        $is_admin = $viewer->getIsAdmin();
        $can_admin = ($is_admin && !$is_self);

        $has_disable = $this->hasApplicationCapability(
            PeopleDisableUsersCapability::CAPABILITY);
        $can_disable = ($has_disable && !$is_self);

        $can_welcome = ($is_admin && $user->canEstablishWebSessions());

        $curtain = $this->newCurtainView($user);

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setIcon('fa-pencil')
                ->setName(\Yii::t("app", 'Edit Profile'))
                ->setHref($this->getApplicationURI('index/editprofile', ['id' => $user->getID()]))
                ->setDisabled(!$can_edit)
                ->setWorkflow(!$can_edit));

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setIcon('fa-picture-o')
                ->setName(\Yii::t("app", 'Edit Profile Picture'))
                ->setHref($this->getApplicationURI('index/picture', ['id' => $user->getID()]))
                ->setDisabled(!$can_edit)
                ->setWorkflow(!$can_edit));

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setIcon('fa-wrench')
                ->setName(\Yii::t("app", 'Edit Settings'))
                ->setDisabled(!$can_edit)
                ->setWorkflow(!$can_edit)
                ->setHref(Url::to(['/settings/index/user', 'username' => $user->getUsername()])));


        if (!$user->getIsManager()) {
            if ($user->getIsAdmin()) {
                $empower_icon = 'fa-arrow-circle-o-down';
                $empower_name = \Yii::t("app", 'Remove Administrator');
            } else {
                $empower_icon = 'fa-arrow-circle-o-up';
                $empower_name = \Yii::t("app", 'Make Administrator');
            }

            $curtain->addAction(
                (new PhabricatorActionView())
                    ->setIcon($empower_icon)
                    ->setName($empower_name)
                    ->setDisabled(!$can_admin)
                    ->setWorkflow(true)
                    ->setHref($this->getApplicationURI('index/empower', ['id' => $user->getID()])));
        }

        if (!$user->getIsAdmin()) {
            if ($user->getIsManager()) {
                $empower_icon = 'fa-level-down';
                $empower_name = \Yii::t("app", 'Edit Manager');
            } else {
                $empower_icon = 'fa-level-up';
                $empower_name = \Yii::t("app", 'Make Manager');
            }
            $curtain->addAction(
                (new PhabricatorActionView())
                    ->setIcon($empower_icon)
                    ->setName($empower_name)
                    ->setDisabled(!$can_admin)
                    ->setWorkflow(true)
                    ->setHref($this->getApplicationURI('index/empower-manager', ['id' => $user->getID()])));
        }


        $curtain->addAction(
            (new PhabricatorActionView())
                ->setIcon('fa-tag')
                ->setName(\Yii::t("app", 'Change Username'))
                ->setDisabled(!$is_admin)
                ->setWorkflow(true)
                ->setHref($this->getApplicationURI('index/rename', ['id' => $user->getID()])));

        if ($user->getIsDisabled()) {
            $disable_icon = 'fa-check-circle-o';
            $disable_name = \Yii::t("app", 'Enable User');
        } else {
            $disable_icon = 'fa-ban';
            $disable_name = \Yii::t("app", 'Disable User');
        }

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setIcon($disable_icon)
                ->setName($disable_name)
                ->setDisabled(!$can_disable)
                ->setWorkflow(true)
                ->setHref($this->getApplicationURI('index/disable', ['id' => $user->getID()])));

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setIcon('fa-times')
                ->setName(\Yii::t("app", 'Delete User'))
                ->setDisabled(!$can_admin)
                ->setWorkflow(true)
                ->setHref($this->getApplicationURI('index/delete', ['id' => $user->getID()])));

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setIcon('fa-envelope')
                ->setName(\Yii::t("app", 'Send Welcome Email'))
                ->setWorkflow(true)
                ->setDisabled(!$can_welcome)
                ->setHref($this->getApplicationURI('index/welcome', ['id' => $user->getID()])));

        return $curtain;
    }


}
