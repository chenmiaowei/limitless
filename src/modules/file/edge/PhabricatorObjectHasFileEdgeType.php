<?php

namespace orangins\modules\file\edge;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;
use Yii;

/**
 * Class PhabricatorObjectHasFileEdgeType
 * @package orangins\modules\file\edge
 * @author 陈妙威
 */
final class PhabricatorObjectHasFileEdgeType extends PhabricatorEdgeType
{

    /**
     *
     */
    const EDGECONST = 25;

    /**
     * @return int|null
     * @author 陈妙威
     */
    public function getInverseEdgeConstant()
    {
        return PhabricatorFileHasObjectEdgeType::EDGECONST;
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
     * @return string
     * @author 陈妙威
     */
    public function getTransactionAddString(
        $actor,
        $add_count,
        $add_edges)
    {

        return Yii::t("app",
            '{0} added {1} file(s): {2}.',
           [
               $actor,
               $add_count,
               $add_edges
           ]);
    }

    /**
     * @param $actor
     * @param $rem_count
     * @param $rem_edges
     * @return string
     * @author 陈妙威
     */
    public function getTransactionRemoveString(
        $actor,
        $rem_count,
        $rem_edges)
    {

        return Yii::t("app",
            '{0} removed {1} file(s): {2}.',
            [
                $actor,
                $rem_count,
                $rem_edges
            ]);
    }

    /**
     * @param $actor
     * @param $total_count
     * @param $add_count
     * @param $add_edges
     * @param $rem_count
     * @param $rem_edges
     * @return string
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

        return Yii::t("app",
            '{0} edited file(s), added {0}: {0}; removed {0}: {0}.',
            [
                $actor,
                $add_count,
                $add_edges,
                $rem_count,
                $rem_edges
            ]);
    }

    /**
     * @param $actor
     * @param $object
     * @param $add_count
     * @param $add_edges
     * @return string
     * @author 陈妙威
     */
    public function getFeedAddString(
        $actor,
        $object,
        $add_count,
        $add_edges)
    {

        return Yii::t("app",
            '{0} added {1} file(s) for {2}: {3}.',
            [
                $actor,
                $add_count,
                $object,
                $add_edges
            ]);
    }

    /**
     * @param $actor
     * @param $object
     * @param $rem_count
     * @param $rem_edges
     * @return string
     * @author 陈妙威
     */
    public function getFeedRemoveString(
        $actor,
        $object,
        $rem_count,
        $rem_edges)
    {

        return Yii::t("app",
            '{0} removed {1} file(s) for {2}: {3}.',
            [
                $actor,
                $rem_count,
                $object,
                $rem_edges
            ]);
    }

    /**
     * @param $actor
     * @param $object
     * @param $total_count
     * @param $add_count
     * @param $add_edges
     * @param $rem_count
     * @param $rem_edges
     * @return string
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

        return Yii::t("app",
            '{0} edited file(s) for {1}, added {2}: {3}; removed {4}: {5}.',
            [
                $actor,
                $object,
                $add_count,
                $add_edges,
                $rem_count,
                $rem_edges
            ]);
    }

}
