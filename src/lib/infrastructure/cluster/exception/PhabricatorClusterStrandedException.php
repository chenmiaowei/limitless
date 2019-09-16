<?php

namespace orangins\lib\infrastructure\cluster\exception;

/**
 * Class PhabricatorClusterStrandedException
 * @package orangins\lib\infrastructure\cluster\exception
 * @author 陈妙威
 */
final class PhabricatorClusterStrandedException
    extends PhabricatorClusterException
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getExceptionTitle()
    {
        return pht('Unable to Reach Any Database');
    }

}
