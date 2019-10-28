<?php

namespace orangins\modules\herald\field;

/**
 * Class HeraldBasicFieldGroup
 * @package orangins\modules\herald\field
 * @author 陈妙威
 */
final class HeraldBasicFieldGroup extends HeraldFieldGroup
{

    /**
     *
     */
    const FIELDGROUPKEY = 'herald';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getGroupLabel()
    {
        return pht('Herald');
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
