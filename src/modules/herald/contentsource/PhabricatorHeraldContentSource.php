<?php

namespace orangins\modules\herald\contentsource;

use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;

/**
 * Class PhabricatorHeraldContentSource
 * @package orangins\modules\herald\contentsource
 * @author 陈妙威
 */
final class PhabricatorHeraldContentSource
    extends PhabricatorContentSource
{

    /**
     *
     */
    const SOURCECONST = 'herald';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSourceName()
    {
        return pht('Herald');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSourceDescription()
    {
        return pht('Changes triggered by Herald rules.');
    }

}
