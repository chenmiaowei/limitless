<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/3
 * Time: 6:19 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\settings\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\lib\view\OranginsPanelView;
use orangins\lib\view\OranginsTableBoxView;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\query\UserEmailSearchEngine;
use orangins\modules\settings\actions\PhabricatorSettingsAdjustAction;
use orangins\modules\settings\actions\PhabricatorSettingsIssueAction;
use orangins\modules\settings\actions\PhabricatorSettingsListAction;
use orangins\modules\settings\actions\PhabricatorSettingsMainAction;
use orangins\modules\settings\actions\PhabricatorSettingsTimezoneAction;
use Yii;

/**
 * Class IndexController
 * @package orangins\modules\settings\controllers
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
        return array(
            'issue' => PhabricatorSettingsIssueAction::class,
            'timezone' => PhabricatorSettingsTimezoneAction::class,
            'adjust' => PhabricatorSettingsAdjustAction::class,
            'panel' => PhabricatorSettingsMainAction::class,
            'builtin' => PhabricatorSettingsMainAction::class,
            'user' => PhabricatorSettingsMainAction::class,
            'index' => PhabricatorSettingsListAction::class,
        );
    }
}