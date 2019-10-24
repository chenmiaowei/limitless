<?php

namespace orangins\modules\daemon\actions;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\infrastructure\daemon\workers\editor\PhabricatorWorkerBulkJobEditor;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJob;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJobTransaction;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkTask;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\daemon\assets\JavelinBulkJobReloadBehaviorAsset;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorDaemonBulkJobMonitorController
 * @package orangins\modules\daemon\actions
 * @author 陈妙威
 */
final class PhabricatorDaemonBulkJobMonitorController
    extends PhabricatorDaemonBulkJobController
{

    /**
     * @return Aphront404Response|AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        /** @var PhabricatorWorkerBulkJob $job */
        $job = PhabricatorWorkerBulkJob::find()
            ->setViewer($viewer)
            ->withIDs(array($request->getURIData('id')))
            ->executeOne();
        if (!$job) {
            return new Aphront404Response();
        }

        // If the user clicks "Continue" on a completed job, take them back to
        // whatever application sent them here.
        if ($request->getStr('done')) {
            if ($request->isFormPost()) {
                $done_uri = $job->getDoneURI();
                return (new AphrontRedirectResponse())->setURI($done_uri);
            }
        }

        $title = Yii::t("app", 'Bulk Job {0}', [$job->getID()]);

        if ($job->getStatus() == PhabricatorWorkerBulkJob::STATUS_CONFIRM) {
            $can_edit = PhabricatorPolicyFilter::hasCapability(
                $viewer,
                $job,
                PhabricatorPolicyCapability::CAN_EDIT);

            if ($can_edit) {
                if ($request->isFormPost()) {
                    $type_status = PhabricatorWorkerBulkJobTransaction::TYPE_STATUS;

                    $xactions = array();
                    $xactions[] = (new PhabricatorWorkerBulkJobTransaction())
                        ->setTransactionType($type_status)
                        ->setNewValue(PhabricatorWorkerBulkJob::STATUS_WAITING);

                    $editor = (new PhabricatorWorkerBulkJobEditor())
                        ->setActor($viewer)
                        ->setContentSourceFromRequest($request)
                        ->setContinueOnMissingFields(true)
                        ->applyTransactions($job, $xactions);

                    return (new AphrontRedirectResponse())
                        ->setURI($job->getMonitorURI());
                } else {
                    $dialog = $this->newDialog()
                        ->setTitle(Yii::t("app", 'Confirm Bulk Job'));

                    $confirm = $job->getDescriptionForConfirm();
                    $confirm = (array)$confirm;
                    foreach ($confirm as $paragraph) {
                        $dialog->appendParagraph($paragraph);
                    }

                    $dialog
                        ->appendParagraph(
                            Yii::t("app", 'Start work on this bulk job?'))
                        ->addCancelButton($job->getManageURI(), Yii::t("app", 'Details'))
                        ->addSubmitButton(Yii::t("app", 'Start Work'));

                    return $dialog;
                }
            } else {
                return $this->newDialog()
                    ->setTitle(Yii::t("app", 'Waiting For Confirmation'))
                    ->appendParagraph(
                        Yii::t("app",
                            'This job is waiting for confirmation before work begins.'))
                    ->addCancelButton($job->getManageURI(), Yii::t("app", 'Details'));
            }
        }


        $dialog = $this->newDialog()
            ->setTitle(Yii::t("app", '{0}: {1}', [$title, $job->getStatusName()]))
            ->addCancelButton($job->getManageURI(), Yii::t("app", 'Details'));

        switch ($job->getStatus()) {
            case PhabricatorWorkerBulkJob::STATUS_WAITING:
                $dialog->appendParagraph(
                    Yii::t("app", 'This job is waiting for tasks to be queued.'));
                break;
            case PhabricatorWorkerBulkJob::STATUS_RUNNING:
                $dialog->appendParagraph(
                    Yii::t("app", 'This job is running.'));
                break;
            case PhabricatorWorkerBulkJob::STATUS_COMPLETE:
                $dialog->appendParagraph(
                    Yii::t("app", 'This job is complete.'));
                break;
        }

        $counts = $job->loadTaskStatusCounts();
        if ($counts) {
            $dialog->appendParagraph($this->renderProgress($counts));
        }

        switch ($job->getStatus()) {
            case PhabricatorWorkerBulkJob::STATUS_COMPLETE:
                $dialog->addHiddenInput('done', true);
                $dialog->addSubmitButton(Yii::t("app", 'Continue'));
                break;
            default:
                JavelinHtml::initBehavior(new JavelinBulkJobReloadBehaviorAsset());
                break;
        }

        return $dialog;
    }

    /**
     * @param array $counts
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderProgress(array $counts)
    {
//        $this->requireResource('bulk-job-css');

        $states = array(
            PhabricatorWorkerBulkTask::STATUS_DONE => array(
                'class' => 'bg-success',
            ),
            PhabricatorWorkerBulkTask::STATUS_RUNNING => array(
                'class' => 'bg-primary',
            ),
            PhabricatorWorkerBulkTask::STATUS_WAITING => array(
                'class' => 'bg-warning',
            ),
            PhabricatorWorkerBulkTask::STATUS_FAIL => array(
                'class' => 'bg-danger',
            ),
        );

        $total = array_sum($counts);
        $offset = 0;
        $bars = array();
        foreach ($states as $state => $spec) {
            $size = ArrayHelper::getValue($counts, $state, 0);
            if (!$size) {
                continue;
            }

            $classes = array();
            $classes[] = 'progress-bar progress-bar-striped';
            $classes[] = $spec['class'];

            $width = ($size / $total);
            $bars[] = phutil_tag(
                'div',
                array(
                    'class' => implode(' ', $classes),
                    'style' =>
                        'width: ' . sprintf('%.2f%%', 100 * $width) . ';',
                ),
                '');

            $offset += $width;
        }

        return phutil_tag(
            'div',
            array(
                'class' => 'progress my-3',
                'style' => 'height: 1.375rem;'
            ),
            $bars);
    }

}
