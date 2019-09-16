<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/3
 * Time: 6:52 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\tag\edge;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;

/**
 * Class PhabricatorObjectHasTagEdgeType
 * @package orangins\modules\tag\edge
 * @author 陈妙威
 */
class PhabricatorObjectHasTagEdgeType extends PhabricatorEdgeType {

    /**
     *
     */
    const EDGECONST = 101;

    /**
     * @return int|null
     * @author 陈妙威
     */
    public function getInverseEdgeConstant() {
        return PhabricatorTagToObjectEdgeType::EDGECONST;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldWriteInverseTransactions() {
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
        $add_edges) {

        return \Yii::t("app",
            '{0} added {0} subscriber(s): {0}.',
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
        $rem_edges) {

        return \Yii::t("app",
            '{0} removed {0} subscriber(s): {0}.',
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
        $rem_edges) {

        return \Yii::t("app",
            '{0} edited subscriber(s), added {0}: {0}; removed {0}: {0}.',
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
        $add_edges) {

        return \Yii::t("app",
            '{0} added {0} subscriber(s) for {0}: {0}.',
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
        $rem_edges) {

        return \Yii::t("app",
            '{0} removed {0} subscriber(s) for {0}: {0}.',
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
        $rem_edges) {

        return \Yii::t("app",
            '{0} edited subscriber(s) for {0}, added {0}: {0}; removed {0}: {0}.',
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