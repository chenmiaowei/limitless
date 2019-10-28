<?php

namespace orangins\modules\herald\group;

use Phobject;

/**
 * Class HeraldGroup
 * @package orangins\modules\herald\group
 * @author 陈妙威
 */
abstract class HeraldGroup extends Phobject
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getGroupLabel();

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getGroupOrder()
    {
        return 1000;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSortKey()
    {
        return sprintf('A%08d%s', $this->getGroupOrder(), $this->getGroupLabel());
    }
}
