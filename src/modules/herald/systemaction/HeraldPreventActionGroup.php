<?php

namespace orangins\modules\herald\systemaction;

/**
 * Class HeraldPreventActionGroup
 * @package orangins\modules\herald\systemaction
 * @author 陈妙威
 */
final class HeraldPreventActionGroup extends HeraldActionGroup
{

    /**
     *
     */
    const ACTIONGROUPKEY = 'prevent';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getGroupLabel()
    {
        return pht('Prevent');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getGroupOrder()
    {
        return 3000;
    }

}
