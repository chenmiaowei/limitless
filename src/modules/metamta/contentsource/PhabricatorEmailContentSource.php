<?php

namespace orangins\modules\metamta\contentsource;

use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;

/**
 * Class PhabricatorEmailContentSource
 * @package orangins\modules\metamta\contentsource
 * @author 陈妙威
 */
final class PhabricatorEmailContentSource
    extends PhabricatorContentSource
{

    /**
     *
     */
    const SOURCECONST = 'email';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSourceName()
    {
        return pht('Email');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSourceDescription()
    {
        return pht('Content sent by electronic mail, also known as e-mail.');
    }

}
