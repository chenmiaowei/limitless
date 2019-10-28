<?php

namespace orangins\modules\metamta\herald;

use orangins\modules\herald\field\HeraldFieldGroup;

/**
 * Class PhabricatorMailEmailHeraldFieldGroup
 * @package orangins\modules\metamta\herald
 * @author 陈妙威
 */
final class PhabricatorMailEmailHeraldFieldGroup extends HeraldFieldGroup
{

    /**
     *
     */
    const FIELDGROUPKEY = 'mail.message';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getGroupLabel()
    {
        return pht('Message Fields');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getGroupOrder()
    {
        return 1000;
    }

}
