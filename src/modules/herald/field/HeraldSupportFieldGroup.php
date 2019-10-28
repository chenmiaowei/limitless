<?php

namespace orangins\modules\herald\field;

/**
 * Class HeraldSupportFieldGroup
 * @package orangins\modules\herald\field
 * @author 陈妙威
 */
final class HeraldSupportFieldGroup extends HeraldFieldGroup
{

    /**
     *
     */
    const FIELDGROUPKEY = 'support';

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
        return 3000;
    }

}
