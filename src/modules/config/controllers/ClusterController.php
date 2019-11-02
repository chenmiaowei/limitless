<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/3/28
 * Time: 10:26 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\config\controllers;


use orangins\lib\controllers\PhabricatorController;
use orangins\modules\config\actions\PhabricatorConfigClusterDatabasesAction;
use orangins\modules\config\actions\PhabricatorConfigClusterNotificationsAction;
use orangins\modules\config\actions\PhabricatorConfigClusterSearchAction;

/**
 * Class ClusterController
 * @package orangins\modules\config\controllers
 * @author 陈妙威
 */
class ClusterController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'databases' => PhabricatorConfigClusterDatabasesAction::class,
            'notifications' => PhabricatorConfigClusterNotificationsAction::class,
            'search' => PhabricatorConfigClusterSearchAction::class
        ];
    }
}