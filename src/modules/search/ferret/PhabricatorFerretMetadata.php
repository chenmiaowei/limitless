<?php

namespace orangins\modules\search\ferret;

use orangins\lib\OranginsObject;
use PhutilSortVector;

/**
 * Class PhabricatorFerretMetadata
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
final class PhabricatorFerretMetadata extends OranginsObject
{

    /**
     * @var
     */
    private $phid;
    /**
     * @var
     */
    private $engine;
    /**
     * @var
     */
    private $relevance;

    /**
     * @param $engine
     * @return $this
     * @author 陈妙威
     */
    public function setEngine($engine)
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @param $phid
     * @return $this
     * @author 陈妙威
     */
    public function setPHID($phid)
    {
        $this->phid = $phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPHID()
    {
        return $this->phid;
    }

    /**
     * @param $relevance
     * @return $this
     * @author 陈妙威
     */
    public function setRelevance($relevance)
    {
        $this->relevance = $relevance;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRelevance()
    {
        return $this->relevance;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRelevanceSortVector()
    {
        $engine = $this->getEngine();

        return (new PhutilSortVector())
            ->addInt($engine->getObjectTypeRelevance())
            ->addInt(-$this->getRelevance());
    }

}
