<?php

namespace orangins\lib\infrastructure\edges\exception;

use yii\base\UserException;

/**
 * Class PhabricatorEdgeCycleException
 * @package orangins\lib\infrastructure\edges\exception
 * @author 陈妙威
 */
final class PhabricatorEdgeCycleException extends UserException
{

    /**
     * @var
     */
    private $cycleEdgeType;
    /**
     * @var array
     */
    private $cycle;

    /**
     * PhabricatorEdgeCycleException constructor.
     * @param $cycle_edge_type
     * @param array $cycle
     */
    public function __construct($cycle_edge_type, array $cycle)
    {
        $this->cycleEdgeType = $cycle_edge_type;
        $this->cycle = $cycle;

        $cycle_list = implode(', ', $cycle);

        parent::__construct(
            \Yii::t("app",
                'Graph cycle detected (type={0}, cycle={1}).',
                [
                    $cycle_edge_type,
                    $cycle_list
                ]));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getCycle()
    {
        return $this->cycle;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCycleEdgeType()
    {
        return $this->cycleEdgeType;
    }

}
