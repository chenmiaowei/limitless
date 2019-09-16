<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/19
 * Time: 3:54 PM
 */

namespace orangins\modules\file\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\file\actions\PhabricatorFileDataAction;

/**
 * Class DataController
 * @package orangins\modules\file\controllers
 */
class DataController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'view' => PhabricatorFileDataAction::class,
            'data' => PhabricatorFileDataAction::class,
            'download' => PhabricatorFileDataAction::class,
        ];
    }
}