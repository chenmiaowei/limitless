<?php

namespace orangins\lib\infrastructure\edges\query;

use AphrontAccessDeniedQueryException;
use orangins\lib\db\ActiveRecord;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\infrastructure\edges\conduit\PhabricatorEdgeObject;
use orangins\lib\infrastructure\edges\constants\PhabricatorEdgeConfig;
use orangins\lib\infrastructure\query\policy\PhabricatorQueryCursor;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorPHIDConstants;
use Exception;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use wild;
use Yii;

/**
 * This is a more formal version of @{class:PhabricatorEdgeQuery} that is used
 * to expose edges to Conduit.
 */
final class PhabricatorEdgeObjectQuery extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $sourcePHIDs;
    /**
     * @var
     */
    private $sourcePHIDType;
    /**
     * @var
     */
    private $edgeTypes;
    /**
     * @var
     */
    private $destinationPHIDs;


    /**
     * @param array $source_phids
     * @return $this
     * @author 陈妙威
     */
    public function withSourcePHIDs(array $source_phids)
    {
        $this->sourcePHIDs = $source_phids;
        return $this;
    }

    /**
     * @param array $types
     * @return $this
     * @author 陈妙威
     */
    public function withEdgeTypes(array $types)
    {
        $this->edgeTypes = $types;
        return $this;
    }

    /**
     * @param array $destination_phids
     * @return $this
     * @author 陈妙威
     */
    public function withDestinationPHIDs(array $destination_phids)
    {
        $this->destinationPHIDs = $destination_phids;
        return $this;
    }

    /**
     * @throws Exception
     * @author 陈妙威
     */
    protected function willExecute()
    {
        $source_phids = $this->sourcePHIDs;

        if (!$source_phids) {
            throw new Exception(
                Yii::t("app",
                    'Edge object query must be executed with a nonempty list of ' .
                    'source PHIDs.'));
        }

        $phid_item = null;
        $phid_type = null;
        foreach ($source_phids as $phid) {
            $this_type = PhabricatorPHID::phid_get_type($phid);
            if ($this_type == PhabricatorPHIDConstants::PHID_TYPE_UNKNOWN) {
                throw new Exception(
                    Yii::t("app",
                        'Source PHID "{0}" in edge object query has unknown PHID type.', [
                            $phid
                        ]));
            }

            if ($phid_type === null) {
                $phid_type = $this_type;
                $phid_item = $phid;
                continue;
            }

            if ($phid_type !== $this_type) {
                throw new Exception(
                    Yii::t("app",
                        'Two source PHIDs ("{0}" and "{1}") have different PHID types ' .
                        '("{2}" and "{3}"). All PHIDs must be of the same type to execute ' .
                        'an edge object query.',
                        [
                            $phid_item,
                            $phid,
                            $phid_type,
                            $this_type
                        ]));
            }
        }

        $this->sourcePHIDType = $phid_type;
    }

    /**
     * @return array
     * @throws AphrontAccessDeniedQueryException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $type = $this->sourcePHIDType;
        $conn = PhabricatorEdgeConfig::establishConnection($type, 'r');
        $table = PhabricatorEdgeConfig::TABLE_NAME_EDGE;
        $rows = $this->loadStandardPageRowsWithConnection($conn, $table);

        $result = array();
        foreach ($rows as $row) {
            $result[] = PhabricatorEdgeObject::newFromRow($row);
        }

        return $result;
    }

    /**
     * @return array|void
     * @throws Exception
     * @author 陈妙威
     */
    protected function buildSelectClauseParts()
    {
        $parts = parent::buildSelectClauseParts($conn);

        // TODO: This is hacky, because we don't have real IDs on this table.
        $parts[] = qsprintf(
            $conn,
            'CONCAT(dateCreated, %s, seq) AS id',
            '_');

        return $parts;
    }

    /**
     * @return array|void
     * @throws PhabricatorInvalidQueryCursorException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws PhabricatorEmptyQueryException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function buildWhereClause()
    {
        $parts = parent::buildWhereClause();

        $parts[] = qsprintf(
            $conn,
            'src IN (%Ls)',
            $this->sourcePHIDs);

        $parts[] = qsprintf(
            $conn,
            'type IN (%Ls)',
            $this->edgeTypes);

        if ($this->destinationPHIDs !== null) {
            $parts[] = qsprintf(
                $conn,
                'dst IN (%Ls)',
                $this->destinationPHIDs);
        }

        return $parts;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return null;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getPrimaryTableAlias()
    {
        return 'edge';
    }


    /**
     * @return array|wild
     * @author 陈妙威
     */
    public function getOrderableColumns() {
        return array(
            'dateCreated' => array(
                'table' => 'edge',
                'column' => 'dateCreated',
                'type' => 'int',
            ),
            'sequence' => array(
                'table' => 'edge',
                'column' => 'seq',
                'type' => 'int',

                // TODO: This is not actually unique, but we're just doing our best
                // here.
                'unique' => true,
            ),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getDefaultOrderVector() {
        return array('created_at', 'sequence');
    }

    /**
     * @param $cursor
     * @return mixed
     * @author 陈妙威
     * @throws PhabricatorInvalidQueryCursorException
     */
    protected function newInternalCursorFromExternalCursor($cursor) {
        list($epoch, $sequence) = $this->parseCursor($cursor);

        // Instead of actually loading an edge, we're just making a fake edge
        // with the properties the cursor describes.

        $edge_object = PhabricatorEdgeObject::newFromRow(
            array(
                'dateCreated' => $epoch,
                'seq' => $sequence,
            ));

        return (new PhabricatorQueryCursor())
            ->setObject($edge_object);
    }

    /**
     * @param ActiveRecord $object
     * @return array
     * @author 陈妙威
     */
    protected function newPagingMapFromPartialObject($object) {
        return array(
            'dateCreated' => $object->created_at,
            'sequence' => $object->getSequence(),
        );
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function newExternalCursorStringForResult($object) {
        return sprintf(
            '%d_%d',
            $object->created_at,
            $object->getSequence());
    }

    /**
     * @param $cursor
     * @return array
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    private function parseCursor($cursor) {
        if (!preg_match('/^\d+_\d+\z/', $cursor)) {
            $this->throwCursorException(
                pht(
                    'Expected edge cursor in the form "0123_6789", got "%s".',
                    $cursor));
        }

        return explode('_', $cursor);
    }

}
