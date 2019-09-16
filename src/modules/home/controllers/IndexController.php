<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/31
 * Time: 8:19 PM
 */

namespace orangins\modules\home\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\home\actions\PhabricatorHomeMenuItemAction;

/**
 * Class IndexController
 * @package orangins\modules\home\application
 */
class IndexController extends PhabricatorController
{
    public function actions()
    {
        return [
          'index' => PhabricatorHomeMenuItemAction::class
        ];
    }
}