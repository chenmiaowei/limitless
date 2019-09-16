<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/26
 * Time: 5:07 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\search\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\search\actions\PhabricatorSearchAction;
use orangins\modules\search\actions\PhabricatorSearchDefaultAction;
use orangins\modules\search\actions\PhabricatorSearchDeleteAction;
use orangins\modules\search\actions\PhabricatorSearchEditAction;
use orangins\modules\search\actions\PhabricatorSearchOrderAction;
use orangins\modules\search\actions\SearchHovercardAction;

/**
 * Class IndexController
 * @package orangins\modules\search\controllers
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
            'query' => PhabricatorSearchAction::class,
            'hovercard' => SearchHovercardAction::class,
            'default' => PhabricatorSearchDefaultAction::class,
            'edit' => PhabricatorSearchEditAction::class,
            'order' => PhabricatorSearchOrderAction::class,
            'delete' => PhabricatorSearchDeleteAction::class,
        ];
    }
}