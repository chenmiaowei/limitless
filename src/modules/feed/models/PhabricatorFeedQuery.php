<?php

namespace orangins\modules\feed\models;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\feed\application\PhabricatorFeedApplication;
use orangins\modules\feed\story\PhabricatorFeedStory;

/**
 * This is the ActiveQuery class for [[PhabricatorFeedStoryData]].
 *
 * @see PhabricatorFeedStoryData
 */
final class PhabricatorFeedQuery extends PhabricatorCursorPagedPolicyAwareQuery
{
    /**
     * @var
     */
    private $filterPHIDs;
    /**
     * @var
     */
    private $chronological_keys;
    /**
     * @var
     */
    private $rangeMin;
    /**
     * @var
     */
    private $rangeMax;

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withFilterPHIDs(array $phids)
    {
        $this->filterPHIDs = $phids;
        return $this;
    }

    /**
     * @param array $keys
     * @return $this
     * @author 陈妙威
     */
    public function withChronologicalKeys(array $keys)
    {
        $this->chronological_keys = $keys;
        return $this;
    }

    /**
     * @param $range_min
     * @param $range_max
     * @return $this
     * @author 陈妙威
     */
    public function withEpochInRange($range_min, $range_max)
    {
        $this->rangeMin = $range_min;
        $this->rangeMax = $range_max;
        return $this;
    }

    /**
     * @return PhabricatorFeedStoryData|null
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorFeedStoryData();
    }

    /**
     * @return array|null|\yii\db\ActiveRecord[]
     * @throws \Exception
     * @author 陈妙威
     */
    protected function loadPage()
    {
        // NOTE: We return raw rows from this method, which is a little unusual.
        $activeRecords = $this->loadStandardPageRows();
        return $activeRecords;
    }

    /**
     * @param PhabricatorFeedStory[] $data
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Throwable
     * @author 陈妙威
     */
    protected function willFilterPage(array $data)
    {
        /** @var PhabricatorFeedStory[] $stories */
        $stories = PhabricatorFeedStory::loadAllFromRows($data, $this->getViewer());

        foreach ($stories as $key => $story) {
            if (!$story->isVisibleInFeed()) {
                unset($stories[$key]);
            }
        }

        return $stories;
    }

    /**
     * @author 陈妙威
     * @throws \Exception
     */
    protected function buildJoinClauseParts()
    {
        parent::buildJoinClauseParts();
        // NOTE: We perform this join unconditionally (even if we have no filter
        // PHIDs) to omit rows which have no story references. These story data
        // rows are notifications or realtime alerts.
        $refTableName = PhabricatorFeedStoryReference::tableName();
        $storyTableName = PhabricatorFeedStoryData::tableName();
        $this->innerJoin("{$refTableName} ref", "ref.chronological_key={$storyTableName}.chronological_key");
    }

    /**
     * @return array|void
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
        parent::buildWhereClauseParts();
        if ($this->filterPHIDs !== null) {
            $this->andWhere(['IN', 'ref.object_phid', $this->filterPHIDs]);
        }

        if ($this->chronological_keys !== null) {
            // NOTE: We can't use "%d" to format these large integers on 32-bit
            // systems. Historically, we formatted these into integers in an
            // awkward way because MySQL could sometimes (?) fail to use the proper
            // keys if the values were formatted as strings instead of integers.

            // After the "qsprintf()" update to use PhutilQueryString, we can no
            // longer do this in a sneaky way. However, the MySQL key issue also
            // no longer appears to reproduce across several systems. So: just use
            // strings until problems turn up?
            $this->andWhere(['IN', 'ref.chronological_key', $this->chronological_keys]);
        }

        // NOTE: We may not have 64-bit PHP, so do the shifts in MySQL instead.
        // From EXPLAIN, it appears like MySQL is smart enough to compute the
        // result and make use of keys to execute the query.

        if ($this->rangeMin !== null) {
            $this->andWhere('ref.chronological_key >= (:chronological_key << 32)', [
                ':chronological_key' => $this->rangeMin
            ]);
        }

        if ($this->rangeMax !== null) {
            $this->andWhere('ref.chronological_key < (:chronological_key << 32)', [
                ':chronological_key' => $this->rangeMax
            ]);
        }
    }

    /**
     * @author 陈妙威
     */
    protected function buildGroupClause()
    {
        $tableNameAndAlias = $this->getTableNameAndAlias();
        if ($this->filterPHIDs !== null) {
            $this->groupBy('ref.chronological_key');
        } else {
            $this->groupBy("{$tableNameAndAlias[1]}.chronological_key");
        }
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getDefaultOrderVector()
    {
        return array('key');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getBuiltinOrders()
    {
        return array(
            'newest' => array(
                'vector' => array('key'),
                'name' => \Yii::t('app', 'Creation (Newest First)'),
                'aliases' => array('created'),
            ),
            'oldest' => array(
                'vector' => array('-key'),
                'name' =>  \Yii::t('app', 'Creation (Oldest First)'),
            ),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getOrderableColumns()
    {
        $storyTableName = PhabricatorFeedStoryData::tableName();
        $table = ($this->filterPHIDs ? 'ref' : $storyTableName);
        return array(
            'key' => array(
                'table' => $table,
                'column' => 'chronological_key',
                'type' => 'string',
                'unique' => true,
            ),
        );
    }

    /**
     * @param PhabricatorCursorPagedPolicyAwareQuery|PhabricatorFeedQuery $subquery
     * @param $cursor
     * @author 陈妙威
     */
    protected function applyExternalCursorConstraintsToQuery(
        PhabricatorCursorPagedPolicyAwareQuery $subquery,
        $cursor)
    {
        $subquery->withChronologicalKeys(array($cursor));
    }

    /**
     * @param PhabricatorFeedStory $object
     * @return string
     * @author 陈妙威
     */
    protected function newExternalCursorStringForResult($object)
    {
        return $object->getChronologicalKey();
    }

    /**
     * @param \orangins\lib\db\ActiveRecord $object
     * @return array
     * @author 陈妙威
     */
    protected function newPagingMapFromPartialObject($object)
    {
        // This query is unusual, and the "object" is a raw result row.
        return array(
            'key' => $object['chronological_key'],
        );
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorFeedApplication::class;
    }
}
