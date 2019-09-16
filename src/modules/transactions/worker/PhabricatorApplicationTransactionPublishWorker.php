<?php

namespace orangins\modules\transactions\worker;

use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;
use yii\helpers\ArrayHelper;

/**
 * Performs backgroundable work after applying transactions.
 *
 * This class handles email, notifications, feed stories, search indexes, and
 * other similar backgroundable work.
 */
final class PhabricatorApplicationTransactionPublishWorker
    extends PhabricatorWorker
{


    /**
     * Publish information about a set of transactions.
     * @throws PhabricatorWorkerPermanentFailureException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     */
    protected function doWork()
    {
        $object = $this->loadObject();
        $editor = $this->buildEditor($object);
        $xactions = $this->loadTransactions($object);

        $editor->publishTransactions($object, $xactions);
    }


    /**
     * Load the object the transactions affect.
     * @return mixed|null
     * @throws PhabricatorWorkerPermanentFailureException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    private function loadObject()
    {
        $viewer = PhabricatorUser::getOmnipotentUser();

        $data = $this->getTaskData();
        if (!is_array($data)) {
            throw new PhabricatorWorkerPermanentFailureException(
                \Yii::t("app", 'Task has invalid task data.'));
        }

        $phid = ArrayHelper::getValue($data, 'objectPHID');
        if (!$phid) {
            throw new PhabricatorWorkerPermanentFailureException(
                \Yii::t("app", 'Task has no object PHID!'));
        }

        $object = (new PhabricatorObjectQuery())
            ->setViewer($viewer)
            ->withPHIDs(array($phid))
            ->executeOne();
        if (!$object) {
            throw new PhabricatorWorkerPermanentFailureException(
                \Yii::t("app",
                    'Unable to load object with PHID "{0}"!', [
                        $phid
                    ]));
        }

        return $object;
    }


    /**
     * Build and configure an Editor to publish these transactions.
     * @param PhabricatorApplicationTransactionInterface $object
     * @return \orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function buildEditor(
        PhabricatorApplicationTransactionInterface $object)
    {
        $data = $this->getTaskData();

        $daemon_source = $this->newContentSource();

        $viewer = PhabricatorUser::getOmnipotentUser();
        $acting_as_phid = ArrayHelper::getValue($data, 'actorPHID');
        $editor = $object->getApplicationTransactionEditor()
            ->setActor($viewer)
            ->setContentSource($daemon_source)
            ->setActingAsPHID($acting_as_phid)
            ->loadWorkerState(ArrayHelper::getValue($data, 'state', array()));

        return $editor;
    }


    /**
     * Load the transactions to be published.
     * @param PhabricatorApplicationTransactionInterface $object
     * @return array
     * @throws PhabricatorWorkerPermanentFailureException
     */
    private function loadTransactions(
        PhabricatorApplicationTransactionInterface $object)
    {
        $data = $this->getTaskData();

        $xaction_phids = ArrayHelper::getValue($data, 'xactionPHIDs');
        if (!$xaction_phids) {
            // It's okay if we don't have any transactions. This can happen when
            // creating objects or performing no-op updates. We will still apply
            // meaningful side effects like updating search engine indexes.
            return array();
        }

        $viewer = PhabricatorUser::getOmnipotentUser();

        $query = PhabricatorApplicationTransactionQuery::newQueryForObject($object);
        if (!$query) {
            throw new PhabricatorWorkerPermanentFailureException(
                \Yii::t("app",
                    'Unable to load query for transaction object "{0}"!', [
                        $object->getPHID()
                    ]));
        }

        $xactions = $query
            ->setViewer($viewer)
            ->withPHIDs($xaction_phids)
            ->needComments(true)
            ->execute();
        $xactions = mpull($xactions, null, 'getPHID');

        $missing = array_diff($xaction_phids, array_keys($xactions));
        if ($missing) {
            throw new PhabricatorWorkerPermanentFailureException(
                \Yii::t("app",
                    'Unable to load transactions: {0}.', [
                        implode(', ', $missing)
                    ]));
        }

        return array_select_keys($xactions, $xaction_phids);
    }
}
