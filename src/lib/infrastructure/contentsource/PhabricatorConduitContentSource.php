<?php

namespace orangins\lib\infrastructure\contentsource;

/**
 * Class PhabricatorConduitContentSource
 * @package orangins\lib\infrastructure\contentsource
 * @author 陈妙威
 */
final class PhabricatorConduitContentSource
    extends PhabricatorContentSource
{

    /**
     *
     */
    const SOURCECONST = 'conduit';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSourceName()
    {
        return pht('Conduit');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSourceDescription()
    {
        return pht('Content from the Conduit API.');
    }
}
