<?php

namespace orangins\modules\rbac\models;

use AphrontAccessDeniedQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\rbac\application\PhabricatorRBACApplication;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use yii\base\Exception;
use yii\db\ActiveRecord;

/**
 * This is the ActiveQuery class for [[Sxbzxr]].
 *
 * @see PhabricatorXgbzxr
 */
class PhabricatorRBACRoleQuery extends PhabricatorCursorPagedPolicyAwareQuery
{
    /**
     * @var
     */
    public $descriptionPrefixes;
    /**
     * @var array
     */
    private $ids;
    /**
     * @var array
     */
    private $phids;

    /**
     * @var
     */
    private $name;

    /**
     * @var
     */
    private $rangeMin;

    /**
     * @var
     */
    private $rangeMax;


    /**
     * @var
     */
    private $description;
    /**
     * @var
     */
    private $dateCreatedAfter;
    /**
     * @var
     */
    private $dateCreatedBefore;

    /**
     * @return RbacRole
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new RbacRole();
    }

    /**
     * @param $name
     * @author 陈妙威
     * @return PhabricatorRBACRoleQuery
     */
    public function withName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param array $prefixes
     * @return $this
     * @author 陈妙威
     */
    public function withDescriptionPrefixes(array $prefixes)
    {
        $this->descriptionPrefixes = $prefixes;
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
     * @param $name
     * @author 陈妙威
     * @return PhabricatorRBACRoleQuery
     */
    public function withDescription($name)
    {
        $this->description = $name;
        return $this;
    }


    /**
     * @param array $ids
     * @return $this
     * @author 陈妙威
     */
    public function withIDs(array $ids)
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withPHIDs(array $phids)
    {
        $this->phids = $phids;
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
     * @return array|null|ActiveRecord[]
     * @throws AphrontAccessDeniedQueryException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $activeRecords = $this->loadStandardPage();
        return $activeRecords;
    }


    /**
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorEmptyQueryException
     * @throws PhabricatorInvalidQueryCursorException
     * @throws Exception
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

        if ($this->rangeMin !== null) {
            $this->andWhere(['>=', 'created_at', $this->rangeMin]);
        }

        if ($this->rangeMax !== null) {
            $this->andWhere(['<=', 'created_at', $this->rangeMax]);
        }

        if ($this->name !== null) {
            $this->andWhere([
                'LIKE',
                'name',
                "{$this->name}",
                false
            ]);
        }
        if ($this->description !== null) {
            $this->andWhere([
                'LIKE',
                'description',
                "{$this->description}%",
                false
            ]);
        }
        if ($this->descriptionPrefixes) {
            $parts = array();
            foreach ($this->descriptionPrefixes as $name_prefix) {
                $parts[] = ['LIKE', 'description', "{$name_prefix}%", false];
            }
            if (count($parts) === 1) {
                $head = head($parts);
                $this->andWhere($head);
            } else {
                $this->andWhere(['AND', $parts]);
            }
        }

        if ($this->dateCreatedAfter) {
            $this->andWhere(['>=', 'created_at', $this->dateCreatedAfter]);
        }

        if ($this->dateCreatedBefore) {
            $this->andWhere(['<=', 'created_at', $this->dateCreatedBefore]);
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
        return PhabricatorRBACApplication::className();
    }
}
