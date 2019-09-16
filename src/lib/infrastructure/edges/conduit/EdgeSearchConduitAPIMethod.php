<?php

namespace orangins\lib\infrastructure\edges\conduit;

use orangins\lib\infrastructure\edges\query\PhabricatorEdgeObjectQuery;
use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;
use orangins\lib\markup\view\PHUIRemarkupView;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\modules\conduit\method\ConduitAPIMethod;
use orangins\modules\conduit\protocol\ConduitAPIRequest;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use Exception;

/**
 * Class EdgeSearchConduitAPIMethod
 * @package orangins\lib\infrastructure\edges\conduit
 * @author 陈妙威
 */
final class EdgeSearchConduitAPIMethod
    extends ConduitAPIMethod
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getAPIMethodName()
    {
        return 'edge.search';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodDescription()
    {
        return \Yii::t("app",'Read edge relationships between objects.');
    }

    /**
     * @return mixed
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getMethodDocumentation()
    {
        $viewer = $this->getViewer();

        $rows = array();
        foreach ($this->getConduitEdgeTypeMap() as $key => $type) {
            $inverse_constant = $type->getInverseEdgeConstant();
            if ($inverse_constant) {
                $inverse_type = PhabricatorEdgeType::getByConstant($inverse_constant);
                $inverse = $inverse_type->getConduitKey();
            } else {
                $inverse = null;
            }

            $rows[] = array(
                $key,
                $type->getConduitName(),
                $inverse,
                new PHUIRemarkupView($viewer, $type->getConduitDescription()),
            );
        }

        $types_table = (new AphrontTableView($rows))
            ->setHeaders(
                array(
                    \Yii::t("app",'Constant'),
                    \Yii::t("app",'Name'),
                    \Yii::t("app",'Inverse'),
                    \Yii::t("app",'Description'),
                ))
            ->setColumnClasses(
                array(
                    'mono',
                    'pri',
                    'mono',
                    'wide',
                ));

        return (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app",'Edge Types'))
            ->setTable($types_table);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMethodStatus()
    {
        return self::METHOD_STATUS_UNSTABLE;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodStatusDescription()
    {
        return \Yii::t("app",'This method is new and experimental.');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function defineParamTypes()
    {
        return array(
                'sourcePHIDs' => 'list<phid>',
                'types' => 'list<const>',
                'destinationPHIDs' => 'optional list<phid>',
            ) + $this->getPagerParamTypes();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function defineReturnType()
    {
        return 'list<dict>';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function defineErrorTypes()
    {
        return array();
    }

    /**
     * @param ConduitAPIRequest $request
     * @return mixed
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function execute(ConduitAPIRequest $request)
    {
        $viewer = $request->getViewer();
        $pager = $this->newPager($request);

        $source_phids = $request->getValue('sourcePHIDs', array());
        $edge_types = $request->getValue('types', array());
        $destination_phids = $request->getValue('destinationPHIDs', array());

        $object_query = (new PhabricatorObjectQuery())
            ->setViewer($viewer)
            ->withNames($source_phids);

        $object_query->execute();
        $objects = $object_query->getNamedResults();
        foreach ($source_phids as $phid) {
            if (empty($objects[$phid])) {
                throw new Exception(
                    \Yii::t("app",
                        'Source PHID "{0}" does not identify a valid object, or you do ' .
                        'not have permission to view it.', [
                            $phid
                        ]));
            }
        }
        $source_phids = mpull($objects, 'getPHID');

        if (!$edge_types) {
            throw new Exception(
                \Yii::t("app",
                    'Edge search must specify a nonempty list of edge types.'));
        }

        $edge_map = $this->getConduitEdgeTypeMap();

        $constant_map = array();
        $edge_constants = array();
        foreach ($edge_types as $edge_type) {
            if (!isset($edge_map[$edge_type])) {
                throw new Exception(
                    \Yii::t("app",
                        'Edge type "{0}" is not a recognized edge type.', [
                            $edge_type
                        ]));
            }

            $constant = $edge_map[$edge_type]->getEdgeConstant();

            $edge_constants[] = $constant;
            $constant_map[$constant] = $edge_type;
        }

        $edge_query = (new PhabricatorEdgeObjectQuery())
            ->setViewer($viewer)
            ->withSourcePHIDs($source_phids)
            ->withEdgeTypes($edge_constants);

        if ($destination_phids) {
            $edge_query->withDestinationPHIDs($destination_phids);
        }

        $edge_objects = $edge_query->executeWithCursorPager($pager);

        $edges = array();
        foreach ($edge_objects as $edge_object) {
            $edges[] = array(
                'sourcePHID' => $edge_object->getSourcePHID(),
                'edgeType' => $constant_map[$edge_object->getEdgeType()],
                'destinationPHID' => $edge_object->getDestinationPHID(),
            );
        }

        $results = array(
            'data' => $edges,
        );

        return $this->addPagerResults($results, $pager);
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function getConduitEdgeTypeMap()
    {
        $types = PhabricatorEdgeType::getAllTypes();

        $map = array();
        foreach ($types as $type) {
            $key = $type->getConduitKey();
            if ($key === null) {
                continue;
            }

            $map[$key] = $type;
        }

        ksort($map);

        return $map;
    }
}
