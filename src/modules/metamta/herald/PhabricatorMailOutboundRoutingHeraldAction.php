<?php

namespace orangins\modules\metamta\herald;

use Exception;
use orangins\modules\herald\models\HeraldRule;
use orangins\modules\herald\systemaction\HeraldAction;
use orangins\modules\herald\systemaction\HeraldApplicationActionGroup;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;

/**
 * Class PhabricatorMailOutboundRoutingHeraldAction
 * @package orangins\modules\metamta\herald
 * @author 陈妙威
 */
abstract class PhabricatorMailOutboundRoutingHeraldAction
    extends HeraldAction
{

    /**
     *
     */
    const DO_ROUTE = 'do.route';

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        return ($object instanceof PhabricatorMetaMTAMail);
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getActionGroupKey()
    {
        return HeraldApplicationActionGroup::ACTIONGROUPKEY;
    }

    /**
     * @param HeraldRule $rule
     * @param $route
     * @param $phids
     * @throws Exception
     * @author 陈妙威
     */
    protected function applyRouting(HeraldRule $rule, $route, $phids)
    {
        $adapter = $this->getAdapter();

        /** @var PhabricatorMetaMTAMail $mail */
        $mail = $adapter->getObject();
        $mail->addRoutingRule($route, $phids, $rule->getPHID());

        $this->logEffect(
            self::DO_ROUTE,
            array(
                'route' => $route,
                'phids' => $phids,
            ));
    }

    /**
     * @return array|void
     * @author 陈妙威
     */
    protected function getActionEffectMap()
    {
        return array(
            self::DO_ROUTE => array(
                'icon' => 'fa-arrow-right',
                'color' => 'green',
                'name' => pht('Routed Message'),
            ),
        );
    }

    /**
     * @param $type
     * @param $data
     * @return string|null
     * @author 陈妙威
     */
    protected function renderActionEffectDescription($type, $data)
    {
        switch ($type) {
            case self::DO_ROUTE:
                return pht('Routed mail.');
        }
    }

}
