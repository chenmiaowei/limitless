<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/3/26
 * Time: 11:32 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\policy\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\policy\actions\PhabricatorPolicyEditAction;
use orangins\modules\policy\actions\PhabricatorPolicyExplainAction;

/**
 * Class IndexController
 * @package orangins\modules\policy\controllers
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
            'edit' => PhabricatorPolicyEditAction::class,
            'explain' => PhabricatorPolicyExplainAction::class,
        ];
    }
}