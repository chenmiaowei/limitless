<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/23
 * Time: 8:08 PM
 */

namespace orangins\modules\people\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\people\actions\PhabricatorPeopleCreateAction;
use orangins\modules\people\actions\PhabricatorPeopleDeleteAction;
use orangins\modules\people\actions\PhabricatorPeopleDisableAction;
use orangins\modules\people\actions\PhabricatorPeopleEmpowerAction;
use orangins\modules\people\actions\PhabricatorPeopleEmpowerManagerAction;
use orangins\modules\people\actions\PhabricatorPeopleInviteAction;
use orangins\modules\people\actions\PhabricatorPeopleListAction;
use orangins\modules\people\actions\PhabricatorPeopleLogsAction;
use orangins\modules\people\actions\PhabricatorPeopleNewAction;
use orangins\modules\people\actions\PhabricatorPeopleProfileBadgesController;
use orangins\modules\people\actions\PhabricatorPeopleProfileCommitsController;
use orangins\modules\people\actions\PhabricatorPeopleProfileEditController;
use orangins\modules\people\actions\PhabricatorPeopleProfileManageController;
use orangins\modules\people\actions\PhabricatorPeopleProfilePictureController;
use orangins\modules\people\actions\PhabricatorPeopleProfileRevisionsController;
use orangins\modules\people\actions\PhabricatorPeopleProfileViewAction;
use orangins\modules\people\actions\PhabricatorPeopleRenameAction;
use orangins\modules\people\actions\PhabricatorPeopleSidebarToggleAction;
use orangins\modules\people\actions\PhabricatorPeopleWelcomeAction;

/**
 * Class IndexController
 * @package orangins\modules\people\controllers
 * @author 陈妙威
 */
class IndexController extends PhabricatorController
{
    public function actions()
    {
        return [
            'query' => PhabricatorPeopleListAction::class,
            'logs' => PhabricatorPeopleLogsAction::class,
            'invite' => PhabricatorPeopleInviteAction::class,
            'view' => PhabricatorPeopleProfileViewAction::class,
            'editprofile' => PhabricatorPeopleProfileEditController::class,
            'empower' => PhabricatorPeopleEmpowerAction::class,
            'empower-manager' => PhabricatorPeopleEmpowerManagerAction::class,
            'delete' => PhabricatorPeopleDeleteAction::class,
            'rename' => PhabricatorPeopleRenameAction::class,
            'welcome' => PhabricatorPeopleWelcomeAction::class,
            'create' => PhabricatorPeopleCreateAction::class,
            'new' => PhabricatorPeopleNewAction::class,
            'commits' => PhabricatorPeopleProfileCommitsController::class,
            'disable' => PhabricatorPeopleDisableAction::class,
            'revisions' => PhabricatorPeopleProfileRevisionsController::class,
            'picture' => PhabricatorPeopleProfilePictureController::class,
            'manage' => PhabricatorPeopleProfileManageController::class,
            'sidebar-toggle' => PhabricatorPeopleSidebarToggleAction::class,
        ];
    }
}