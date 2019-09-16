<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/5
 * Time: 10:21 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\notification\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\notification\actions\PhabricatorNotificationClearAction;
use orangins\modules\notification\actions\PhabricatorNotificationIndividualAction;
use orangins\modules\notification\actions\PhabricatorNotificationListAction;
use orangins\modules\notification\actions\PhabricatorNotificationPanelAction;

/**
 * Class IndexController
 * @package orangins\modules\notification\controllers
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
            'index' => PhabricatorNotificationListAction::class,
            'panel' => PhabricatorNotificationPanelAction::class,
            'individual' => PhabricatorNotificationIndividualAction::class,
            'clear' => PhabricatorNotificationClearAction::class,
        ];
    }
}