<?php

namespace orangins\lib\infrastructure\edges\conduit;

use orangins\lib\OranginsObject;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;

/**
 * Class PhabricatorEdgeObject
 * @package orangins\lib\infrastructure\edges\conduit
 * @author 陈妙威
 */
final class PhabricatorEdgeObject
    extends OranginsObject
    implements PhabricatorPolicyInterface
{

    /**
     * @var
     */
    private $id;
    /**
     * @var
     */
    private $src;
    /**
     * @var
     */
    private $dst;
    /**
     * @var
     */
    private $type;

    /**
     * @param array $row
     * @return PhabricatorEdgeObject
     * @author 陈妙威
     */
    public static function newFromRow(array $row)
    {
        $edge = new self();

        $edge->id = $row['id'];
        $edge->src = $row['src'];
        $edge->dst = $row['dst'];
        $edge->type = $row['type'];

        return $edge;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSourcePHID()
    {
        return $this->src;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEdgeType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDestinationPHID()
    {
        return $this->dst;
    }


    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */


    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getCapabilities()
    {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
        );
    }

    /**
     * @param $capability
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function getPolicy($capability)
    {
        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                return PhabricatorPolicies::getMostOpenPolicy();
        }
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool|mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return false;
    }

}
