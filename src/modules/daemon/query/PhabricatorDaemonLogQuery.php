<?php

namespace orangins\modules\daemon\query;

use AphrontWriteGuard;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\daemon\application\PhabricatorDaemonsApplication;
use orangins\modules\daemon\models\PhabricatorDaemonLog;
use PhutilDaemonHandle;
use Exception;

/**
 * Class PhabricatorDaemonLogQuery
 * @package orangins\modules\daemon\models
 * @author 陈妙威
 */
final class PhabricatorDaemonLogQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     *
     */
    const STATUS_ALL = 'status-all';
    /**
     *
     */
    const STATUS_ALIVE = 'status-alive';
    /**
     *
     */
    const STATUS_RUNNING = 'status-running';

    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $notIDs;
    /**
     * @var string
     */
    private $status = self::STATUS_ALL;
    /**
     * @var
     */
    private $daemonClasses;
    /**
     * @var
     */
    private $allowStatusWrites;
    /**
     * @var
     */
    private $daemonIDs;

    /**
     * @return float|int
     * @author 陈妙威
     */
    public static function getTimeUntilUnknown()
    {
        return 3 * PhutilDaemonHandle::getHeartbeatEventFrequency();
    }

    /**
     * @return float|int
     * @author 陈妙威
     */
    public static function getTimeUntilDead()
    {
        return 30 * PhutilDaemonHandle::getHeartbeatEventFrequency();
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
     * @param array $ids
     * @return $this
     * @author 陈妙威
     */
    public function withoutIDs(array $ids)
    {
        $this->notIDs = $ids;
        return $this;
    }

    /**
     * @param $status
     * @return $this
     * @author 陈妙威
     */
    public function withStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param array $classes
     * @return $this
     * @author 陈妙威
     */
    public function withDaemonClasses(array $classes)
    {
        $this->daemonClasses = $classes;
        return $this;
    }

    /**
     * @param $allow
     * @return $this
     * @author 陈妙威
     */
    public function setAllowStatusWrites($allow)
    {
        $this->allowStatusWrites = $allow;
        return $this;
    }

    /**
     * @param array $daemon_ids
     * @return $this
     * @author 陈妙威
     */
    public function withDaemonIDs(array $daemon_ids)
    {
        $this->daemonIDs = $daemon_ids;
        return $this;
    }

    /**
     * @return null
     * @author 陈妙威
     * @throws Exception
     */
    protected function loadPage()
    {
        return $this->loadStandardPage();
    }

    /**
     * @param array $daemons
     * @return array
     * @author 陈妙威
     * @throws Exception
     */
    protected function willFilterPage(array $daemons)
    {
        $unknown_delay = self::getTimeUntilUnknown();
        $dead_delay = self::getTimeUntilDead();

        $status_running = PhabricatorDaemonLog::STATUS_RUNNING;
        $status_unknown = PhabricatorDaemonLog::STATUS_UNKNOWN;
        $status_wait = PhabricatorDaemonLog::STATUS_WAIT;
        $status_exiting = PhabricatorDaemonLog::STATUS_EXITING;
        $status_exited = PhabricatorDaemonLog::STATUS_EXITED;
        $status_dead = PhabricatorDaemonLog::STATUS_DEAD;

        $filter = array_fuse($this->getStatusConstants());

        foreach ($daemons as $key => $daemon) {
            $status = $daemon->getStatus();
            $seen = $daemon->updated_at;

            $is_running = ($status == $status_running) ||
                ($status == $status_wait) ||
                ($status == $status_exiting);

            // If we haven't seen the daemon recently, downgrade its status to
            // unknown.
            $unknown_time = ($seen + $unknown_delay);
            if ($is_running && ($unknown_time < time())) {
                $status = $status_unknown;
            }

            // If the daemon hasn't been seen in quite a while, assume it is dead.
            $dead_time = ($seen + $dead_delay);
            if (($status == $status_unknown) && ($dead_time < time())) {
                $status = $status_dead;
            }

            // If we changed the daemon's status, adjust it.
            if ($status != $daemon->getStatus()) {
                $daemon->setStatus($status);

                // ...and write it, if we're in a context where that's reasonable.
                if ($this->allowStatusWrites) {
                    $guard = AphrontWriteGuard::beginScopedUnguardedWrites();
                    $daemon->save();
                    unset($guard);
                }
            }

            // If the daemon no longer matches the filter, get rid of it.
            if ($filter) {
                if (empty($filter[$daemon->getStatus()])) {
                    unset($daemons[$key]);
                }
            }
        }

        return $daemons;
    }

    /**
     * @author 陈妙威
     * @throws Exception
     */
    protected function buildWhereClause()
    {
        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->notIDs !== null) {
            $this->andWhere(['NOT IN', 'id', $this->ids]);
        }

        if ($this->getStatusConstants()) {
            $this->andWhere(['NOT IN', 'status', $this->getStatusConstants()]);
        }

        if ($this->daemonClasses !== null) {
            $this->andWhere(['NOT IN', 'daemon', $this->daemonClasses]);
        }

        if ($this->daemonIDs !== null) {
            $this->andWhere(['NOT IN', 'daemon_id', $this->daemonIDs]);
        }
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     */
    private function getStatusConstants()
    {
        $status = $this->status;
        switch ($status) {
            case self::STATUS_ALL:
                return array();
            case self::STATUS_RUNNING:
                return array(
                    PhabricatorDaemonLog::STATUS_RUNNING,
                );
            case self::STATUS_ALIVE:
                return array(
                    PhabricatorDaemonLog::STATUS_UNKNOWN,
                    PhabricatorDaemonLog::STATUS_RUNNING,
                    PhabricatorDaemonLog::STATUS_WAIT,
                    PhabricatorDaemonLog::STATUS_EXITING,
                );
            default:
                throw new Exception(\Yii::t("app",'Unknown status "%s"!', $status));
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
