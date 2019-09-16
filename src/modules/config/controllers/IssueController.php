<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/3/28
 * Time: 10:23 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\config\controllers;


use orangins\lib\controllers\PhabricatorController;
use orangins\modules\config\actions\PhabricatorConfigIssueListAction;
use orangins\modules\config\actions\PhabricatorConfigIssuePanelAction;

/**
 * Class IssueController
 * @package orangins\modules\config\controllers
 * @author 陈妙威
 */
class IssueController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'index' => PhabricatorConfigIssueListAction::class,
            'panel' => PhabricatorConfigIssuePanelAction::class,
        ];
    }
}