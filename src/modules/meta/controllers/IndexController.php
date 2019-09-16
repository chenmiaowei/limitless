<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/2/28
 * Time: 6:06 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\meta\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\meta\actions\ErrorAction;
use orangins\modules\meta\actions\PhabricatorApplicationDetailViewAction;
use orangins\modules\meta\actions\PhabricatorApplicationEditAction;
use orangins\modules\meta\actions\PhabricatorApplicationEmailCommandsAction;
use orangins\modules\meta\actions\PhabricatorApplicationPanelAction;
use orangins\modules\meta\actions\PhabricatorApplicationsListAction;

/**
 * Class IndexController
 * @package orangins\modules\meta\controllers
 * @author 陈妙威
 */
class IndexController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'query' => PhabricatorApplicationsListAction::class,
            'view' => PhabricatorApplicationDetailViewAction::class,
            'edit' => PhabricatorApplicationEditAction::class,
            'mailcommands' => PhabricatorApplicationEmailCommandsAction::class,
            'panel' => PhabricatorApplicationPanelAction::class,
            'error' => ErrorAction::class,
        ];
    }
}