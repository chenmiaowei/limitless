<?php

namespace orangins\modules\search\ferret;

use orangins\modules\people\search\PhabricatorUserFerretEngine;

/**
 * 搜索接口， 继承该接口则表示当前数据库类可搜索
 * Interface PhabricatorFerretInterface
 * @package orangins\modules\search\engineextension
 */
interface PhabricatorFerretInterface
{

    /**
     * @return PhabricatorUserFerretEngine
     * @author 陈妙威
     */
    public function newFerretEngine();

}
