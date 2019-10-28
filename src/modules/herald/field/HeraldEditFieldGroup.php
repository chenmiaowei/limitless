<?php

namespace orangins\modules\herald\field;

/**
 * Class HeraldEditFieldGroup
 * @package orangins\modules\herald\field
 * @author 陈妙威
 */
final class HeraldEditFieldGroup extends HeraldFieldGroup
{

    /**
     *
     */
    const FIELDGROUPKEY = 'edit';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getGroupLabel()
    {
        return pht('Edit Attributes');
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
