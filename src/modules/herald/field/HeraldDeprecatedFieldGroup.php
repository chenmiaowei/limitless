<?php

namespace orangins\modules\herald\field;

/**
 * Class HeraldDeprecatedFieldGroup
 * @package orangins\modules\herald\field
 * @author 陈妙威
 */
final class HeraldDeprecatedFieldGroup extends HeraldFieldGroup
{

    /**
     *
     */
    const FIELDGROUPKEY = 'deprecated';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getGroupLabel()
    {
        return pht('Deprecated');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getGroupOrder()
    {
        return 99999;
    }

}
