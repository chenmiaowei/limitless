<?php

namespace orangins\lib\infrastructure\cluster\exception;

/**
 * Class PhabricatorClusterImpossibleWriteException
 * @package orangins\lib\infrastructure\cluster\exception
 * @author 陈妙威
 */
final class PhabricatorClusterImpossibleWriteException
    extends PhabricatorClusterException
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getExceptionTitle()
    {
        return pht('Impossible Cluster Write');
    }

}
