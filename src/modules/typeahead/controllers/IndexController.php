<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/23
 * Time: 1:30 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\typeahead\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\typeahead\actions\TypeaheadIndexAction;

/**
 * Class IndexController
 * @package orangins\modules\typeahead\controllers
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
          'index' => TypeaheadIndexAction::class,
        ];
    }
}