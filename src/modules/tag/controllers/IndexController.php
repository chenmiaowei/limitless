<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 4:48 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\tag\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\tag\actions\PhabricatorTagEditAction;
use orangins\modules\tag\actions\PhabricatorTagListAction;
use orangins\modules\tag\actions\PhabricatorTagViewAction;

/**
 * Class IndexController
 * @package orangins\modules\tag\controllers
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
            'query' => PhabricatorTagListAction::className(),
            'create' => PhabricatorTagEditAction::className(),
            'edit' => PhabricatorTagEditAction::className(),
            'view' => PhabricatorTagViewAction::className(),
        ];
    }
}