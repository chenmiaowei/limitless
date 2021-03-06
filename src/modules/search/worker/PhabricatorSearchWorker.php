<?php

namespace orangins\modules\search\worker;

use AphrontQueryException;
use Exception;
use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException;
use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerYieldException;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\lib\infrastructure\util\PhabricatorGlobalLock;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\search\index\PhabricatorIndexEngine;
use PhutilLockException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use yii\db\IntegrityException;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorSearchWorker
 * @package orangins\modules\search\worker
 * @author 陈妙威
 */
final class PhabricatorSearchWorker extends PhabricatorWorker
{

    /**
     * @param $phid
     * @param null $parameters
     * @param bool $is_strict
     * @throws AphrontQueryException
     * @throws IntegrityException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws \Throwable
     * @author 陈妙威
     */
    public static function queueDocumentForIndexing(
        $phid,
        $parameters = null,
        $is_strict = false)
    {

        if ($parameters === null) {
            $parameters = array();
        }

        parent::scheduleTask(
            'PhabricatorSearchWorker',
            array(
                'documentPHID' => $phid,
                'parameters' => $parameters,
                'strict' => $is_strict,
            ),
            array(
                'priority' => parent::PRIORITY_INDEX,
                'objectPHID' => $phid,
            ));
    }

    /**
     * @return mixed|void
     * @throws \Exception
     * @author 陈妙威
     */
    protected function doWork()
    {
        $data = $this->getTaskData();
        $object_phid = ArrayHelper::getValue($data, 'documentPHID');

        // See T12425. By the time we run an indexing task, the object it indexes
        // may have been deleted. This is unusual, but not concerning, and failing
        // to index these objects is correct.

        // To avoid showing these non-actionable errors to users, don't report
        // indexing exceptions unless we're in "strict" mode. This mode is set by
        // the "bin/search index" tool.

        $is_strict = ArrayHelper::getValue($data, 'strict', false);

        try {
            $object = $this->loadObjectForIndexing($object_phid);
        } catch (PhabricatorWorkerPermanentFailureException $ex) {
            if ($is_strict) {
                throw $ex;
            } else {
                return;
            }
        }

        $engine = (new PhabricatorIndexEngine())
            ->setObject($object);

        $parameters = ArrayHelper::getValue($data, 'parameters', array());
        $engine->setParameters($parameters);

        if (!$engine->shouldIndexObject()) {
            return;
        }

        $lock = PhabricatorGlobalLock::newLock(
            'index',
            array(
                'objectPHID' => $object_phid,
            ));

        try {
            $lock->lock(1);
        } catch (PhutilLockException $ex) {
            // If we fail to acquire the lock, just yield. It's expected that we may
            // contend on this lock occasionally if a large object receives many
            // updates in a short period of time, and it's appropriate to just retry
            // rebuilding the index later.
            throw new PhabricatorWorkerYieldException(15);
        }

        $caught = null;
        try {
            // Reload the object now that we have a lock, to make sure we have the
            // most current version.
            $object = $this->loadObjectForIndexing($object->getPHID());

            $engine->setObject($object);
            $engine->indexObject();
        } catch (Exception $ex) {
            $caught = $ex;
        }

        // Release the lock before we deal with the exception.
        $lock->unlock();

        if ($caught) {
            if (!($caught instanceof PhabricatorWorkerPermanentFailureException)) {
                $caught = new PhabricatorWorkerPermanentFailureException(
                    pht(
                        'Failed to update search index for document "%s": %s',
                        $object_phid,
                        $caught->getMessage()));
            }

            if ($is_strict) {
                throw $caught;
            }
        }
    }

    /**
     * @param $phid
     * @return mixed
     * @throws PhabricatorWorkerPermanentFailureException
     * @throws Exception
     * @author 陈妙威
     */
    private function loadObjectForIndexing($phid)
    {
        $viewer = PhabricatorUser::getOmnipotentUser();

        $object = (new PhabricatorObjectQuery())
            ->setViewer($viewer)
            ->withPHIDs(array($phid))
            ->executeOne();

        if (!$object) {
            throw new PhabricatorWorkerPermanentFailureException(
                pht(
                    'Unable to load object "%s" to rebuild indexes.',
                    $phid));
        }

        return $object;
    }

}
