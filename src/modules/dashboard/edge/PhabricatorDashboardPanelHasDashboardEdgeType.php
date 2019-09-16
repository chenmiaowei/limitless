<?php

namespace orangins\modules\dashboard\edge;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;

/**
 * Class PhabricatorDashboardPanelHasDashboardEdgeType
 * @package orangins\modules\dashboard\edge
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelHasDashboardEdgeType
    extends PhabricatorEdgeType
{

    /**
     *
     */
    const EDGECONST = 46;

    /**
     * @return int|null
     * @author 陈妙威
     */
    public function getInverseEdgeConstant()
    {
        return PhabricatorDashboardDashboardHasPanelEdgeType::EDGECONST;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldWriteInverseTransactions()
    {
        return true;
    }

    /**
     * @param $actor
     * @param $add_count
     * @param $add_edges
     * @return mixed|string
     * @author 陈妙威
     */
    public function getTransactionAddString(
        $actor,
        $add_count,
        $add_edges)
    {

        return \Yii::t("app",
            '%s added %s dashboard(s): %s.',
            $actor,
            $add_count,
            $add_edges);
    }

    /**
     * @param $actor
     * @param $rem_count
     * @param $rem_edges
     * @return mixed|string
     * @author 陈妙威
     */
    public function getTransactionRemoveString(
        $actor,
        $rem_count,
        $rem_edges)
    {

        return \Yii::t("app",
            '%s removed %s dashboard(s): %s.',
            $actor,
            $rem_count,
            $rem_edges);
    }

    /**
     * @param $actor
     * @param $total_count
     * @param $add_count
     * @param $add_edges
     * @param $rem_count
     * @param $rem_edges
     * @return mixed|string
     * @author 陈妙威
     */
    public function getTransactionEditString(
        $actor,
        $total_count,
        $add_count,
        $add_edges,
        $rem_count,
        $rem_edges)
    {

        return \Yii::t("app",
            '%s edited dashboard(s), added %s: %s; removed %s: %s.',
            $actor,
            $add_count,
            $add_edges,
            $rem_count,
            $rem_edges);
    }

    /**
     * @param $actor
     * @param $object
     * @param $add_count
     * @param $add_edges
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFeedAddString(
        $actor,
        $object,
        $add_count,
        $add_edges)
    {

        return \Yii::t("app",
            '%s added %s dashboard(s) for %s: %s.',
            $actor,
            $add_count,
            $object,
            $add_edges);
    }

    /**
     * @param $actor
     * @param $object
     * @param $rem_count
     * @param $rem_edges
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFeedRemoveString(
        $actor,
        $object,
        $rem_count,
        $rem_edges)
    {

        return \Yii::t("app",
            '%s removed %s dashboard(s) for %s: %s.',
            $actor,
            $rem_count,
            $object,
            $rem_edges);
    }

    /**
     * @param $actor
     * @param $object
     * @param $total_count
     * @param $add_count
     * @param $add_edges
     * @param $rem_count
     * @param $rem_edges
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFeedEditString(
        $actor,
        $object,
        $total_count,
        $add_count,
        $add_edges,
        $rem_count,
        $rem_edges)
    {

        return \Yii::t("app",
            '%s edited dashboard(s) for %s, added %s: %s; removed %s: %s.',
            $actor,
            $object,
            $add_count,
            $add_edges,
            $rem_count,
            $rem_edges);
    }
}
