<?php

namespace orangins\modules\tag\models;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\tag\application\PhabricatorTagsApplication;

/**
 * @see PhabricatorTag
 */
class PhabricatorTagQuery extends PhabricatorCursorPagedPolicyAwareQuery
{


    /**
     * @var array
     */
    public $phids;


    /**
     * @var
     */
    public $ids;
    /**
     * @var
     */
    public $authorPHIDs;
    /**
     * @var
     */
    public $dateCreatedAfter;
    /**
     * @var
     */
    public $dateCreatedBefore;
    /**
     * @var
     */
    private $namePrefixes;
    /**
     * @var
     */
    private $rangeMin;

    /**
     * @var
     */
    private $rangeMax;

    /**
     * @param array $prefixes
     * @return $this
     * @author 陈妙威
     */
    public function withNamePrefixes(array $prefixes)
    {
        $this->namePrefixes = $prefixes;
        return $this;
    }


    /**
     * @param array $phids
     * @author 陈妙威
     * @return PhabricatorTagQuery
     */
    public function withPHIDs(array $phids)
    {
        $this->phids = $phids;
        return $this;
    }


    /**
     * @param array $phids
     * @author 陈妙威
     * @return PhabricatorTagQuery
     */
    public function withIDs(array $phids)
    {
        $this->ids = $phids;
        return $this;
    }

    /**
     * @param array $authors
     * @return $this
     * @author 陈妙威
     */
    public function withAuthorPHIDs(array $authors)
    {
        $this->authorPHIDs = $authors;
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
     * @param $date_created_before
     * @return $this
     * @author 陈妙威
     */
    public function withDateCreatedBefore($date_created_before)
    {
        $this->dateCreatedBefore = $date_created_before;
        return $this;
    }

    /**
     * @param $date_created_after
     * @return $this
     * @author 陈妙威
     */
    public function withDateCreatedAfter($date_created_after)
    {
        $this->dateCreatedAfter = $date_created_after;
        return $this;
    }

    /**
     * @return array|null|\yii\db\ActiveRecord[]
     * @throws \AphrontAccessDeniedQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $activeRecords = $this->loadStandardPage();
        return $activeRecords;
    }

    /**
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
        parent::buildWhereClauseParts();


        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }


        if ($this->phids !== null) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }

        if ($this->namePrefixes) {
            $parts = array();
            foreach ($this->namePrefixes as $name_prefix) {
                $parts[] = ['LIKE', 'name', "{$name_prefix}%", false];
            }
            if (count($parts) === 1) {
                $head = head($parts);
                $this->andWhere($head);
            } else {
                $this->andWhere(['AND', $parts]);
            }
        }

        if ($this->authorPHIDs) {
            $this->andWhere(['IN', 'author_phid', $this->authorPHIDs]);
        }

        if ($this->dateCreatedAfter) {
            $this->andWhere(['>=', 'created_at', $this->dateCreatedAfter]);
        }

        if ($this->dateCreatedBefore) {
            $this->andWhere(['<=', 'created_at', $this->dateCreatedBefore]);
        }
        if ($this->rangeMin !== null) {
            $this->andWhere(['>=', 'created_at', $this->rangeMin]);
        }

        if ($this->rangeMax !== null) {
            $this->andWhere(['<=', 'created_at', $this->rangeMax]);
        }

    }


    /**
     * If this query belongs to an application, return the application class name
     * here. This will prevent the query from returning results if the viewer can
     * not access the application.
     *
     * If this query does not belong to an application, return `null`.
     *
     * @return string|null Application class name.
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorTagsApplication::className();
    }
}
