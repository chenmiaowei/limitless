<?php

namespace orangins\modules\herald\systemaction;

/**
 * Class HeraldNotifyActionGroup
 * @package orangins\modules\herald\systemaction
 * @author 陈妙威
 */
final class HeraldNotifyActionGroup extends HeraldActionGroup
{

    /**
     *
     */
    const ACTIONGROUPKEY = 'notify';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getGroupLabel()
    {
        return pht('Notify');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getGroupOrder()
    {
        return 2000;
    }

}
