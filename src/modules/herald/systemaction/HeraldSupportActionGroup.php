<?php

namespace orangins\modules\herald\systemaction;

/**
 * Class HeraldSupportActionGroup
 * @package orangins\modules\herald\systemaction
 * @author 陈妙威
 */
final class HeraldSupportActionGroup extends HeraldActionGroup
{

    /**
     *
     */
    const ACTIONGROUPKEY = 'support';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getGroupLabel()
    {
        return pht('Supporting Applications');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getGroupOrder()
    {
        return 4000;
    }

}
