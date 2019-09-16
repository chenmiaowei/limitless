<?php

namespace orangins\modules\transactions\bulk;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\infrastructure\daemon\workers\bulk\PhabricatorWorkerBulkJobType;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJob;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkTask;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use PhutilNumber;

/**
 * Class PhabricatorEditEngineBulkJobType
 * @package orangins\modules\transactions\bulk
 * @author 陈妙威
 */
final class PhabricatorEditEngineBulkJobType
    extends PhabricatorWorkerBulkJobType
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBulkJobTypeKey()
    {
        return 'transaction.edit';
    }

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return string
     * @author 陈妙威
     */
    public function getJobName(PhabricatorWorkerBulkJob $job)
    {
        return \Yii::t("app", 'Bulk Edit');
    }

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return array
     * @author 陈妙威
     * @throws \Exception
     */
    public function getDescriptionForConfirm(PhabricatorWorkerBulkJob $job)
    {
        $parts = array();

        $parts[] = \Yii::t("app",
            'You are about to apply a bulk edit which will affect ' .
            '{0} object(s).',
            [new PhutilNumber($job->getSize())]);

        if ($job->getIsSilent()) {
            $parts[] = \Yii::t("app",
                'If you start work now, this edit will be applied silently: it will ' .
                'not send mail or publish notifications.');
        } else {
            $parts[] = \Yii::t("app",
                'If you start work now, this edit will send mail and publish ' .
                'notifications normally.');

            $parts[] = \Yii::t("app", 'To silence this edit, run this command:');

            $command = csprintf(
                'phabricator/ $ ./bin/bulk make-silent --id %R',
                $job->getID());
            $command = (string)$command;

            $parts[] = JavelinHtml::phutil_tag('tt', array(), $command);

            $parts[] = \Yii::t("app",
                'After running this command, reload this page to see the new setting.');
        }

        return $parts;
    }

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return int
     * @author 陈妙威
     */
    public function getJobSize(PhabricatorWorkerBulkJob $job)
    {
        return count($job->getParameter('objectPHIDs', array()));
    }

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return mixed
     * @author 陈妙威
     */
    public function getDoneURI(PhabricatorWorkerBulkJob $job)
    {
        return $job->getParameter('doneURI');
    }

    /**
     * @param PhabricatorWorkerBulkJob $job
     * @return array
     * @author 陈妙威
     */
    public function createTasks(PhabricatorWorkerBulkJob $job)
    {
        $tasks = array();

        foreach ($job->getParameter('objectPHIDs', array()) as $phid) {
            $tasks[] = PhabricatorWorkerBulkTask::initializeNewTask($job, $phid);
        }

        return $tasks;
    }

    /**
     * @param PhabricatorUser $actor
     * @param PhabricatorWorkerBulkJob $job
     * @param PhabricatorWorkerBulkTask $task
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function runTask(
        PhabricatorUser $actor,
        PhabricatorWorkerBulkJob $job,
        PhabricatorWorkerBulkTask $task)
    {

        $object = (new PhabricatorObjectQuery())
            ->setViewer($actor)
            ->withPHIDs(array($task->getObjectPHID()))
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$object) {
            return;
        }

        $raw_xactions = $job->getParameter('xactions');
        $xactions = $this->buildTransactions($object, $raw_xactions);
        $is_silent = $job->getIsSilent();

        $object->getApplicationTransactionEditor()
            ->setActor($actor)
            ->setContentSource($job->newContentSource())
            ->setContinueOnNoEffect(true)
            ->setContinueOnMissingFields(true)
            ->setIsSilent($is_silent)
            ->applyTransactions($object, $xactions);
    }

    /**
     * @param PhabricatorApplicationTransactionInterface $object
     * @param array $raw_xactions
     * @return array
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildTransactions($object, array $raw_xactions)
    {
        $xactions = array();

        foreach ($raw_xactions as $raw_xaction) {
            $xaction = $object->getApplicationTransactionTemplate()
                ->setTransactionType($raw_xaction['type']);

            if (isset($raw_xaction['new'])) {
                $xaction->setNewValue($raw_xaction['new']);
            }

            if (isset($raw_xaction['comment'])) {
                $comment = $xaction
                    ->getApplicationTransactionCommentObject()
                    ->setContent($raw_xaction['comment']);
                $xaction->attachComment($comment);
            }

            if (isset($raw_xaction['metadata'])) {
                foreach ($raw_xaction['metadata'] as $meta_key => $meta_value) {
                    $xaction->setMetadataValue($meta_key, $meta_value);
                }
            }

            if (array_key_exists('old', $raw_xaction)) {
                $xaction->setOldValue($raw_xaction['old']);
            }

            $xactions[] = $xaction;
        }

        return $xactions;
    }

}
