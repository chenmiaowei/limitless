<?php

namespace orangins\lib\infrastructure\daemon\workers\query;

use orangins\lib\infrastructure\query\PhabricatorBaseQuery;
use orangins\lib\infrastructure\query\PhabricatorQuery;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerActiveTask;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTaskData;
use PhutilUTF8StringTruncator;
use Exception;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * Select and lease tasks from the worker task queue.
 */
final class PhabricatorWorkerLeaseQuery extends PhabricatorBaseQuery
{

    /**
     *
     */
    const PHASE_LEASED = 'leased';
    /**
     *
     */
    const PHASE_UNLEASED = 'unleased';
    /**
     *
     */
    const PHASE_EXPIRED = 'expired';

    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $objectPHIDs;

    /**
     * @var
     */
    private $skipLease;
    /**
     * @var bool
     */
    private $leased = false;

    /**
     * @return int
     * @author 陈妙威
     */
    public static function getDefaultWaitBeforeRetry()
    {
        return phutil_units('5 minutes in seconds');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public static function getDefaultLeaseDuration()
    {
        return phutil_units('2 hours in seconds');
    }

    /**
     * Set this flag to select tasks from the top of the queue without leasing
     * them.
     *
     * This can be used to show which tasks are coming up next without altering
     * the queue's behavior.
     *
     * @param bool True to skip the lease acquisition step.
     * @return PhabricatorWorkerLeaseQuery
     */
    public function setSkipLease($skip)
    {
        $this->skipLease = $skip;
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
    public function withObjectPHIDs(array $phids)
    {
        $this->objectPHIDs = $phids;
        return $this;
    }

    /**
     * Select only leased tasks, only unleased tasks, or both types of task.
     *
     * By default, queries select only unleased tasks (equivalent to passing
     * `false` to this method). You can pass `true` to select only leased tasks,
     * or `null` to ignore the lease status of tasks.
     *
     * If your result set potentially includes leased tasks, you must disable
     * leasing using @{method:setSkipLease}. These options are intended for use
     * when displaying task status information.
     *
     * @param mixed `true` to select only leased tasks, `false` to select only
     *              unleased tasks (default), or `null` to select both.
     * @return PhabricatorWorkerLeaseQuery
     */
    public function withLeasedTasks($leased)
    {
        $this->leased = $leased;
        return $this;
    }

    /**
     * @param $limit
     * @return $this
     * @author 陈妙威
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function execute()
    {
        if (!$this->limit) {
            throw new Exception(
                \Yii::t("app",'You must {0} when leasing tasks.', ['setLimit()']));
        }

        if ($this->leased !== false) {
            if (!$this->skipLease) {
                throw new Exception(
                    \Yii::t("app",
                        'If you potentially select leased tasks using {0}, ' .
                        'you MUST disable lease acquisition by calling {1}.',
                         [
                             'withLeasedTasks()',
                             'setSkipLease()'
                         ]));
            }
        }

        $task_table = new PhabricatorWorkerActiveTask();
        $taskdata_table = new PhabricatorWorkerTaskData();
        $lease_ownership_name = $this->getLeaseOwnershipName();


        // Try to satisfy the request from new, unleased tasks first. If we don't
        // find enough tasks, try tasks with expired leases (i.e., tasks which have
        // previously failed).

        // If we're selecting leased tasks, look for them first.

        $phases = array();
        if ($this->leased !== false) {
            $phases[] = self::PHASE_LEASED;
        }
        if ($this->leased !== true) {
            $phases[] = self::PHASE_UNLEASED;
            $phases[] = self::PHASE_EXPIRED;
        }
        $limit = $this->limit;

        $leased = 0;
        $task_ids = array();
        foreach ($phases as $phase) {
            // NOTE: If we issue `UPDATE ... WHERE ... ORDER BY id ASC`, the query
            // goes very, very slowly. The `ORDER BY` triggers this, although we get
            // the same apparent results without it. Without the ORDER BY, binary
            // read slaves complain that the query isn't repeatable. To avoid both
            // problems, do a SELECT and then an UPDATE.


            $phabricatorWorkerActiveTaskQuery = $task_table::find();
            $this->buildCustomWhereClause($phabricatorWorkerActiveTaskQuery, $phase);
            $this->buildOrderClause($phabricatorWorkerActiveTaskQuery, $phase);
            $this->buildLimitClause($phabricatorWorkerActiveTaskQuery, $limit - $leased);

            $rows = $phabricatorWorkerActiveTaskQuery->all();


            // NOTE: Sometimes, we'll race with another worker and they'll grab
            // this task before we do. We could reduce how often this happens by
            // selecting more tasks than we need, then shuffling them and trying
            // to lock only the number we're actually after. However, the amount
            // of time workers spend here should be very small relative to their
            // total runtime, so keep it simple for the moment.

            if ($rows) {
                if ($this->skipLease) {
                    $leased += count($rows);
                    $list = ipull($rows, 'id');
                    $task_ids += array_fuse($list);
                } else {
                    $phabricatorWorkerActiveTaskQuery1 = $task_table::find();
                    $this->buildUpdateWhereClause($phabricatorWorkerActiveTaskQuery1, $phase, $rows);

                    $getAffectedRows = $task_table::updateAll([
                        'lease_owner' => $lease_ownership_name,
                        'lease_expires' => new Expression(self::getDefaultLeaseDuration() . " + UNIX_TIMESTAMP()")
                    ], $phabricatorWorkerActiveTaskQuery1->where, $phabricatorWorkerActiveTaskQuery1->params);
                    $leased += $getAffectedRows;
                }

                if ($leased == $limit) {
                    break;
                }
            }
        }

        if (!$leased) {
            return array();
        }

        $phabricatorWorkerActiveTaskQuery2 = $task_table::find();
        if ($this->skipLease) {
            $phabricatorWorkerActiveTaskQuery2->andWhere(['IN', 'worker_activetask.id', $task_ids]);
        } else {
            $phabricatorWorkerActiveTaskQuery2->andWhere('worker_activetask.lease_owner = :lease_owner AND lease_expires > UNIX_TIMESTAMP()', [
                ':lease_owner' => $lease_ownership_name
            ]);
        }

        $this->buildOrderClause($phabricatorWorkerActiveTaskQuery2, $phase);
        $this->buildLimitClause($phabricatorWorkerActiveTaskQuery2, $limit);


        $tasks = $phabricatorWorkerActiveTaskQuery2
            ->select(['worker_activetask.*', 'taskdata.data as data', 'UNIX_TIMESTAMP() _serverTime'])
            ->innerJoin($taskdata_table::tableName() . " taskdata", "worker_activetask.data_id=taskdata.id")
            ->all();

//        $tasks = $task_table->loadAllFromArray($data);
        $tasks = mpull($tasks, null, 'getID');

//        foreach ($data as $row) {
//            $tasks[$row['id']]->setServerTime($row['_serverTime']);
//            if ($row['_taskData']) {
//                $task_data = json_decode($row['_taskData'], true);
//            } else {
//                $task_data = null;
//            }
//            $tasks[$row['id']]->setData($task_data);
//        }

        if ($this->skipLease) {
            // Reorder rows into the original phase order if this is a status query.
            $tasks = array_select_keys($tasks, $task_ids);
        }

        return $tasks;
    }

    /**
     * @param PhabricatorQuery $conn
     * @param $phase
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    protected function buildCustomWhereClause(
        PhabricatorQuery $conn,
        $phase)
    {

        $where = array();

        switch ($phase) {
            case self::PHASE_LEASED:
                $conn->andWhere('lease_owner IS NOT NULL');
                $conn->andWhere('lease_expires  >= UNIX_TIMESTAMP()');
                break;
            case self::PHASE_UNLEASED:
                $conn->andWhere('lease_owner IS NULL');
                break;
            case self::PHASE_EXPIRED:
                $conn->andWhere('lease_expires < UNIX_TIMESTAMP()');
                break;
            default:
                throw new Exception(\Yii::t("app","Unknown phase '{0}'!", [
                    $phase
                ]));
        }

        if ($this->ids !== null) {
            $conn->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->objectPHIDs !== null) {
            $conn->andWhere(['IN', 'object_phid', $this->objectPHIDs]);
        }
    }

    /**
     * @param PhabricatorQuery $conn
     * @param $phase
     * @param array $rows
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function buildUpdateWhereClause(
        PhabricatorQuery $conn,
        $phase,
        array $rows)
    {

        $where = array();

        // NOTE: This is basically working around the MySQL behavior that
        // `IN (NULL)` doesn't match NULL.

        switch ($phase) {
            case self::PHASE_LEASED:
                throw new Exception(
                    \Yii::t("app",
                        'Trying to lease tasks selected in the leased phase! This is ' .
                        'intended to be impossible.'));
            case self::PHASE_UNLEASED:
                $conn->andWhere("lease_owner IS NULL");
                $conn->andWhere(['IN', 'id', ipull($rows, 'id')]);
                break;
            case self::PHASE_EXPIRED:
                $in = array();
                foreach ($rows as $row) {
                    $in[] = [
                        'id' => $row['id'],
                        'lease_owner' => $row['leaseOwner']
                    ];
                }
                if ($in) {
                    if (count($in) >= 2) {
                        $conn->andWhere(ArrayHelper::merge(['OR'], $rows));
                    } else {
                        $conn->andWhere(head($in));
                    }
                }
                break;
            default:
                throw new Exception(\Yii::t("app",'Unknown phase "{0}"!', [
                    $phase
                ]));
        }
    }

    /**
     * @param PhabricatorQuery $conn_w
     * @param $phase
     * @throws Exception
     * @author 陈妙威
     */
    private function buildOrderClause(PhabricatorQuery $conn_w, $phase)
    {
        switch ($phase) {
            case self::PHASE_LEASED:
                // Ideally we'd probably order these by lease acquisition time, but
                // we don't have that handy and this is a good approximation.
                $conn_w->orderBy('priority ASC, id ASC');
                break;
            case self::PHASE_UNLEASED:
                // When selecting new tasks, we want to consume them in order of
                // increasing priority (and then FIFO).
                $conn_w->orderBy('priority ASC, id ASC');
                break;
            case self::PHASE_EXPIRED:
                // When selecting failed tasks, we want to consume them in roughly
                // FIFO order of their failures, which is not necessarily their original
                // queue order.

                // Particularly, this is important for tasks which use soft failures to
                // indicate that they are waiting on other tasks to complete: we need to
                // push them to the end of the queue after they fail, at least on
                // average, so we don't deadlock retrying the same blocked task over
                // and over again.
                $conn_w->orderBy('lease_expires ASC');
                break;
            default:
                throw new Exception(\Yii::t("app",'Unknown phase "{0}"!', [
                    $phase
                ]));
        }
    }

    /**
     * @param PhabricatorQuery $conn_w
     * @param $limit
     * @author 陈妙威
     */
    private function buildLimitClause(PhabricatorQuery $conn_w, $limit)
    {
        $conn_w->limit($limit);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getLeaseOwnershipName()
    {
        static $sequence = 0;

        // TODO: If the host name is very long, this can overflow the 64-character
        // column, so we pick just the first part of the host name. It might be
        // useful to just use a random hash as the identifier instead and put the
        // pid / time / host (which are somewhat useful diagnostically) elsewhere.
        // Likely, we could store a daemon ID instead and use that to identify
        // when and where code executed. See T6742.

        $host = php_uname('n');
        $host = (new PhutilUTF8StringTruncator())
            ->setMaximumBytes(32)
            ->setTerminator('...')
            ->truncateString($host);

        $parts = array(
            getmypid(),
            time(),
            $host,
            ++$sequence,
        );

        return implode(':', $parts);
    }

}
