<?php

namespace orangins\modules\userservice\models;

use AphrontAccessDeniedQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\PhabricatorApplication;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\userservice\application\PhabricatorUserServiceApplication;
use orangins\modules\userservice\capability\UserServiceBrowseDirectoryCapability;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use yii\db\ActiveRecord;

/**
 * This is the ActiveQuery class for [[Userservice]].
 *
 * @see PhabricatorUserService
 */
class PhabricatorUserServiceQuery extends PhabricatorCursorPagedPolicyAwareQuery
{
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
    private $dateCreatedAfter;
    /**
     * @var
     */
    private $dateCreatedBefore;

    /**
     * @var
     */
    private $userPHIDs;
    /**
     * @var
     */
    private $rangeMin;

    /**
     * @var
     */
    private $rangeMax;

    /**
     * @param array $user_phids
     * @return $this
     * @author 陈妙威
     */
    public function withUserPHIDs(array $user_phids)
    {
        $this->userPHIDs = $user_phids;
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
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $application = PhabricatorApplication::getAllApplications()[PhabricatorUserServiceApplication::className()];
        $hasCapability = PhabricatorPolicyFilter::hasCapability($this->getViewer(), $application, UserServiceBrowseDirectoryCapability::CAPABILITY);
        if (!$hasCapability && empty($this->cardnum)) {
            return [];
        }
        return $this->loadStandardPage();
    }


    /**
     * @throws PhabricatorInvalidQueryCursorException
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorEmptyQueryException
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

        if ($this->userPHIDs !== null) {
            $this->andWhere(['IN', 'user_phid', $this->userPHIDs]);
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
        return PhabricatorUserServiceApplication::className();
    }
}
