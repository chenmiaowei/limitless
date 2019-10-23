<?php

namespace orangins\modules\search\interfaces;

use orangins\modules\search\ngrams\PhabricatorSearchNgrams;

/**
 * Interface PhabricatorNgramsInterface
 * @package orangins\modules\search\interfaces
 */
interface PhabricatorNgramsInterface
    extends PhabricatorIndexableInterface
{

    /**
     * @return PhabricatorSearchNgrams[]
     * @author 陈妙威
     */
    public function newNgrams();

}
