<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/9/20
 * Time: 11:24 AM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\metamta\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\metamta\actions\PhabricatorMetaMTAMailListAction;

/**
 * Class IndexController
 * @package orangins\modules\metamta\controllers
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
            'query' => PhabricatorMetaMTAMailListAction::className()
        ];
    }
}