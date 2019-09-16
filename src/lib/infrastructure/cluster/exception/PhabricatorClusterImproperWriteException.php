<?php

namespace orangins\lib\infrastructure\cluster\exception;

/**
 * Class PhabricatorClusterImproperWriteException
 * @package orangins\lib\infrastructure\cluster\exception
 * @author 陈妙威
 */
final class PhabricatorClusterImproperWriteException
    extends PhabricatorClusterException
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getExceptionTitle()
    {
        return pht('Improper Cluster Write');
    }

}
