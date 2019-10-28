<?php

namespace orangins\modules\herald\management;

use orangins\lib\markup\rule\PhabricatorObjectRemarkupRule;

/**
 * Class HeraldRemarkupRule
 * @package orangins\modules\herald\management
 * @author 陈妙威
 */
final class HeraldRemarkupRule extends PhabricatorObjectRemarkupRule
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getObjectNamePrefix()
    {
        return 'H';
    }

    /**
     * @param array $ids
     * @return mixed
     * @author 陈妙威
     */
    protected function loadObjects(array $ids)
    {
        $viewer = $this->getEngine()->getConfig('viewer');
        return HeraldRule::find()
            ->setViewer($viewer)
            ->withIDs($ids)
            ->execute();
    }

}
