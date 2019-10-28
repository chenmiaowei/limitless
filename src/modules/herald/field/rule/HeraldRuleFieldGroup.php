<?php

namespace orangins\modules\herald\field\rule;

use orangins\modules\herald\field\HeraldFieldGroup;

/**
 * Class HeraldRuleFieldGroup
 * @package orangins\modules\herald\field\rule
 * @author 陈妙威
 */
final class HeraldRuleFieldGroup
    extends HeraldFieldGroup
{

    /**
     *
     */
    const FIELDGROUPKEY = 'herald.rule';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getGroupLabel()
    {
        return pht('Rule Fields');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getGroupOrder()
    {
        return 500;
    }

}
