<?php

namespace orangins\modules\herald\systemaction;

/**
 * Class HeraldUtilityActionGroup
 * @package orangins\modules\herald\systemaction
 * @author 陈妙威
 */
final class HeraldUtilityActionGroup extends HeraldActionGroup
{

    /**
     *
     */
    const ACTIONGROUPKEY = 'utility';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getGroupLabel()
    {
        return pht('Utility');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getGroupOrder()
    {
        return 10000;
    }

}
