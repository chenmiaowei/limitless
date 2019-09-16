<?php

namespace orangins\lib\infrastructure\daemon\workers\query;

use orangins\lib\infrastructure\daemon\workers\bulk\PhabricatorWorkerBulkJobType;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJob;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\daemon\application\PhabricatorDaemonsApplication;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorWorkerBulkJobQuery
 * @package orangins\lib\infrastructure\daemon\workers\query
 * @author 陈妙威
 */
final class PhabricatorWorkerBulkJobQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $phids;
    /**
     * @var
     */
    private $authorPHIDs;
    /**
     * @var
     */
    private $bulkJobTypes;
    /**
     * @var
     */
    private $statuses;

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
     * @param array $author_phids
     * @return $this
     * @author 陈妙威
     */
    public function withAuthorPHIDs(array $author_phids)
    {
        $this->authorPHIDs = $author_phids;
        return $this;
    }

    /**
     * @param array $job_types
     * @return $this
     * @author 陈妙威
     */
    public function withBulkJobTypes(array $job_types)
    {
        $this->bulkJobTypes = $job_types;
        return $this;
    }

    /**
     * @param array $statuses
     * @return $this
     * @author 陈妙威
     */
    public function withStatuses(array $statuses)
    {
        $this->statuses = $statuses;
        return $this;
    }

    /**
     * @return null|PhabricatorWorkerBulkJob
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorWorkerBulkJob();
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
        return $this->loadStandardPage();
    }

    /**
     * @param array $page
     * @return array
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function willFilterPage(array $page)
    {
        $map = PhabricatorWorkerBulkJobType::getAllJobTypes();

        foreach ($page as $key => $job) {
            $implementation = ArrayHelper::getValue($map, $job->getJobTypeKey());
            if (!$implementation) {
                $this->didRejectResult($job);
                unset($page[$key]);
                continue;
            }
            $job->attachJobImplementation($implementation);
        }

        return $page;
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

        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }

        if ($this->authorPHIDs !== null) {
            $this->andWhere(['IN', 'author_phid', $this->authorPHIDs]);
        }

        if ($this->bulkJobTypes !== null) {
            $this->andWhere(['IN', 'bulk_job_type', $this->bulkJobTypes]);
        }

        if ($this->statuses !== null) {
            $this->andWhere(['IN', 'status', $this->statuses]);
        }
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorDaemonsApplication::className();
    }

}
