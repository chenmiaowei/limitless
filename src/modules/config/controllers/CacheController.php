<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/3/28
 * Time: 10:24 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\config\controllers;


use orangins\lib\controllers\PhabricatorController;
use orangins\modules\config\actions\PhabricatorConfigCacheAction;
use orangins\modules\config\actions\PhabricatorConfigPurgeCacheAction;

/**
 * Class CacheController
 * @package orangins\modules\config\controllers
 * @author 陈妙威
 */
class CacheController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return [
            'index' => PhabricatorConfigCacheAction::class,
            'purge' => PhabricatorConfigPurgeCacheAction::class,
        ];
    }
}