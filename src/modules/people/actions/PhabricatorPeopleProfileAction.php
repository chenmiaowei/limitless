<?php

namespace orangins\modules\people\actions;

use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITagView;
use orangins\modules\people\engine\PhabricatorPeopleProfileMenuEngine;
use orangins\modules\people\iconset\PhabricatorPeopleIconSet;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use yii\helpers\Url;

/**
 * Class PhabricatorPeopleProfileAction
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
abstract class PhabricatorPeopleProfileAction
    extends PhabricatorPeopleAction
{

    /**
     * @var PhabricatorUser
     */
    private $user;
    /**
     * @var
     */
    private $profileMenu;

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireAdmin()
    {
        return false;
    }

    /**
     * @param PhabricatorUser $user
     * @return $this
     * @author 陈妙威
     */
    public function setUser(PhabricatorUser $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param PhabricatorUser $user
     * @param $item_identifier
     * @return \orangins\lib\view\layout\AphrontSideNavFilterView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \Exception
     * @author 陈妙威
     */
    final protected function newNavigation(
        PhabricatorUser $user,
        $item_identifier) {

        $viewer = $this->getViewer();

        $engine = (new PhabricatorPeopleProfileMenuEngine())
            ->setViewer($viewer)
            ->setAction($this)
            ->setProfileObject($user);

        $view_list = $engine->newProfileMenuItemViewList();

        $view_list->setSelectedViewWithItemIdentifier($item_identifier);

        $navigation = $view_list->newNavigationView();

        return $navigation;
    }


    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();

        $user = $this->getUser();
        if ($user) {
            $crumbs->addTextCrumb(
                $user->getUsername(),
                Url::to(["/people/index/view", "id" => $user->getID()]));
        }

        return $crumbs;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildProfileHeader()
    {
        $user = $this->user;
        $viewer = $this->getViewer();

        $profile = $user->loadUserProfile();
        $picture = $user->getProfileImageURI();

        $profile_icon = PhabricatorPeopleIconSet::getIconIcon($profile->getIcon());
        $profile_title = $profile->getDisplayTitle();

        $roles = array();
        if ($user->getIsAdmin()) {
            $roles[] = \Yii::t("app",'Administrator');
        }
        if ($user->getIsDisabled()) {
            $roles[] = \Yii::t("app",'Disabled');
        }
        if (!$user->getIsApproved()) {
            $roles[] = \Yii::t("app",'Not Approved');
        }
        if ($user->getIsSystemAgent()) {
            $roles[] = \Yii::t("app",'Bot');
        }
        if ($user->getIsMailingList()) {
            $roles[] = \Yii::t("app",'Mailing List');
        }
        if (!$user->getIsEmailVerified()) {
            $roles[] = \Yii::t("app",'Email Not Verified');
        }

        $tag = null;
        if ($roles) {
            $tag = (new PHUITagView())
                ->setName(implode(', ', $roles))
                ->addClass('ml-2 project-view-header-tag')
                ->setType(PHUITagView::TYPE_SHADE);
        }

        $header = (new PHUIPageHeaderView())
            ->setHeader(array($user->getFullName(), $tag))
            ->setImage($picture)
            ->setProfileHeader(true)
            ->addClass('people-profile-header');

        if ($user->getIsDisabled()) {
            $header->setStatus('fa-ban', 'red', \Yii::t("app",'Disabled'));
        } else {
            $header->setStatus($profile_icon, 'bluegrey', $profile_title);
        }

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $user,
            PhabricatorPolicyCapability::CAN_EDIT);

        if ($can_edit) {
            $id = $user->getID();
            $header->setImageEditURL($this->getApplicationURI("picture/{$id}/"));
        }

        return $header;
    }

}
