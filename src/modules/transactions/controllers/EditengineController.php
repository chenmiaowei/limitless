<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/4
 * Time: 2:54 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\transactions\controllers;


use orangins\lib\controllers\PhabricatorController;
use orangins\modules\transactions\actions\PhabricatorEditEngineConfigurationDefaultCreateController;
use orangins\modules\transactions\actions\PhabricatorEditEngineConfigurationDefaultsController;
use orangins\modules\transactions\actions\PhabricatorEditEngineConfigurationDisableController;
use orangins\modules\transactions\actions\PhabricatorEditEngineConfigurationEditController;
use orangins\modules\transactions\actions\PhabricatorEditEngineConfigurationIsEditController;
use orangins\modules\transactions\actions\PhabricatorEditEngineConfigurationLockController;
use orangins\modules\transactions\actions\PhabricatorEditEngineConfigurationReorderController;
use orangins\modules\transactions\actions\PhabricatorEditEngineConfigurationSaveController;
use orangins\modules\transactions\actions\PhabricatorEditEngineConfigurationSortController;
use orangins\modules\transactions\actions\PhabricatorEditEngineConfigurationSubtypeController;
use orangins\modules\transactions\actions\PhabricatorEditEngineConfigurationViewController;
use orangins\modules\transactions\actions\PhabricatorEditEngineListController;

/**
 * Class EditengineController
 * @package orangins\modules\transactions\controllers
 * @author 陈妙威
 */
class EditengineController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'query' => PhabricatorEditEngineListController::class,
            'edit' => PhabricatorEditEngineConfigurationEditController::class,
            'sort' => PhabricatorEditEngineConfigurationSortController::class,
            'view' => PhabricatorEditEngineConfigurationViewController::class,
            'save' => PhabricatorEditEngineConfigurationSaveController::class,
            'reorder' => PhabricatorEditEngineConfigurationReorderController::class,
            'defaults' => PhabricatorEditEngineConfigurationDefaultsController::class,
            'lock' => PhabricatorEditEngineConfigurationLockController::class,
            'subtype' => PhabricatorEditEngineConfigurationSubtypeController::class,
            'defaultcreate' => PhabricatorEditEngineConfigurationDefaultCreateController::class,
            'defaultedit' => PhabricatorEditEngineConfigurationIsEditController::class,
            'disable' => PhabricatorEditEngineConfigurationDisableController::class,
        ];
    }
}