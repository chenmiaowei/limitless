<?php

namespace orangins\modules\herald\field;

/**
 * Class HeraldRelatedFieldGroup
 * @package orangins\modules\herald\field
 * @author 陈妙威
 */
final class HeraldRelatedFieldGroup extends HeraldFieldGroup
{

    /**
     *
     */
    const FIELDGROUPKEY = 'related';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getGroupLabel()
    {
        return pht('Related Fields');
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
