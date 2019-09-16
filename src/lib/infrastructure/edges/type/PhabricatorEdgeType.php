<?php

namespace orangins\lib\infrastructure\edges\type;

use orangins\lib\OranginsObject;
use PhutilClassMapQuery;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Defines an edge type.
 *
 * Edges are typed, directed connections between two objects. They are used to
 * represent most simple relationships, like when a user is subscribed to an
 * object or an object is a member of a project.
 *
 * @task load   Loading Types
 */
abstract class PhabricatorEdgeType extends OranginsObject
{

    /**
     * @return string
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public function getEdgeConstant()
    {
        $const = $this->getPhobjectClassConstant('EDGECONST');

        if (!is_int($const) || ($const <= 0)) {
            throw new Exception(
                \Yii::t("app",
                    '{0} class "{1}" has an invalid {2} property. ' .
                    'Edge constants must be positive integers.', [
                        __CLASS__,
                        get_class($this),
                        'EDGECONST'
                    ]));
        }

        return $const;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getConduitKey()
    {
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getConduitName()
    {
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getConduitDescription()
    {
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getInverseEdgeConstant()
    {
        return null;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldPreventCycles()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldWriteInverseTransactions()
    {
        return false;
    }

    /**
     * @param $actor
     * @return string
     * @author 陈妙威
     */
    public function getTransactionPreviewString($actor)
    {
        return \Yii::t("app",
            '{0} edited edge metadata.',
            [
                $actor
            ]);
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

        return \Yii::t("app",
            '{0} added {1} edge(s): {2}.', [
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

        return \Yii::t("app",
            '{0} removed {1} edge(s): {2}.', [
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

        return \Yii::t("app",
            '{0} edited {1} edge(s), added {2}: {3}; removed {4}: {5}.', [
                $actor,
                $total_count,
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

        return \Yii::t("app",
            '{0} added {1} edge(s) to {2}: {3}.', [
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

        return \Yii::t("app",
            '{0} removed {1} edge(s) from {2}: {3}.', [
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

        return \Yii::t("app",
            '{0} edited {1} edge(s) for {2}, added {3}: {4}; removed {5}: {6}.', [
                $actor,
                $total_count,
                $object,
                $add_count,
                $add_edges,
                $rem_count,
                $rem_edges
            ]);
    }


    /* -(  Loading Types  )------------------------------------------------------ */


    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public static function getAllTypes()
    {
        static $type_map;

        if ($type_map === null) {
            /** @var PhabricatorEdgeType[] $types */
            $types = (new PhutilClassMapQuery())
                ->setAncestorClass(__CLASS__)
                ->setUniqueMethod('getEdgeConstant')
                ->execute();

            // Check that all the inverse edge definitions actually make sense. If
            // edge type A says B is its inverse, B must exist and say that A is its
            // inverse.

            foreach ($types as $const => $type) {
                $inverse = $type->getInverseEdgeConstant();
                if ($inverse === null) {
                    continue;
                }

                if (empty($types[$inverse])) {
                    throw new Exception(
                        \Yii::t("app",
                            'Edge type "{0}" ("{1}") defines an inverse type ("{2}") which ' .
                            'does not exist.', [
                                get_class($type),
                                $const,
                                $inverse
                            ]));
                }

                $inverse_inverse = $types[$inverse]->getInverseEdgeConstant();
                if ($inverse_inverse !== $const) {
                    throw new Exception(
                        \Yii::t("app",
                            'Edge type "{0}" ("{1}") defines an inverse type ("{2}"), but that ' .
                            'inverse type defines a different type ("{3}") as its ' .
                            'inverse.', [
                                get_class($type),
                                $const,
                                $inverse,
                                $inverse_inverse
                            ]));
                }
            }

            $type_map = $types;
        }

        return $type_map;
    }


    /**
     * @task load
     * @param $const
     * @return PhabricatorEdgeType
     * @throws Exception
     */
    public static function getByConstant($const)
    {
        $type = ArrayHelper::getValue(self::getAllTypes(), $const);

        if (!$type) {
            throw new Exception(
                \Yii::t("app",'Unknown edge constant "{0}"!', [$const]));
        }

        return $type;
    }

}
