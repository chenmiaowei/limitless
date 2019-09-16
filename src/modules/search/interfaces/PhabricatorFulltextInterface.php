<?php

namespace orangins\modules\search\interfaces;

use orangins\modules\search\index\PhabricatorFulltextEngine;

/**
 * Interface PhabricatorFulltextInterface
 * @package orangins\modules\search\interfaces
 */
interface PhabricatorFulltextInterface
    extends PhabricatorIndexableInterface
{

    /**
     * @return PhabricatorFulltextEngine
     * @author 陈妙威
     */
    public function newFulltextEngine();

}
