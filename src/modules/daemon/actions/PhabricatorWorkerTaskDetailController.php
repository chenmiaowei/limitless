<?php

namespace orangins\modules\daemon\actions;

use Exception;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerActiveTask;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerArchiveTask;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTask;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTaskData;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITwoColumnView;
use PhutilNumber;

/**
 * Class PhabricatorWorkerTaskDetailController
 * @package orangins\modules\daemon\actions
 * @author 陈妙威
 */
final class PhabricatorWorkerTaskDetailController
    extends PhabricatorDaemonController
{

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException
     * @throws \yii\base\Exception
     * @throws Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');

        $task = PhabricatorWorkerActiveTask::findOne($id);
        if (!$task) {
            $tasks = PhabricatorWorkerArchiveTask::find()
                ->withIDs(array($id))
                ->execute();
            $task = reset($tasks);
        }

        $header = new PHUIHeaderView();

        if (!$task) {
            $title = \Yii::t("app",'Task Does Not Exist');

            $header->setHeader(\Yii::t("app",'Task {0} Missing', [$id]));

            $error_view = new PHUIInfoView();
            $error_view->setTitle(\Yii::t("app",'No Such Task'));
            $error_view->appendChild(phutil_tag(
                'p',
                array(),
                \Yii::t("app",'This task may have recently been garbage collected.')));
            $error_view->setSeverity(PHUIInfoView::SEVERITY_NODATA);

            $content = $error_view;
        } else {
            $title = \Yii::t("app",'Task {0}',[ $task->getID()]);

            $header->setHeader(
                \Yii::t("app",
                    'Task {0}: {1}',
                    [
                        $task->getID(),
                        $task->getTaskClass()
                    ]));

            $properties = $this->buildPropertyListView($task);

            $object_box = (new PHUIObjectBoxView())
                ->setHeaderText($title)
                ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
                ->addPropertyList($properties);

            $retry_head = (new PHUIHeaderView())
                ->setHeader(\Yii::t("app",'Retries'));

            $retry_info = $this->buildRetryListView($task);

            $retry_box = (new PHUIObjectBoxView())
                ->setHeader($retry_head)
                ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
                ->addPropertyList($retry_info);

            $content = array(
                $object_box,
                $retry_box,
            );
        }

        $header->setHeaderIcon('fa-sort');

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($title);
        $crumbs->setBorder(true);

        $view = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setFooter($content);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

    /**
     * @param PhabricatorWorkerTask $task
     * @return PHUIPropertyListView
     * @throws \orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException
     * @throws Exception
     * @author 陈妙威
     */
    private function buildPropertyListView(PhabricatorWorkerTask $task)
    {
        $viewer = $this->getViewer();

        $view = new PHUIPropertyListView();
        $object_phid = $task->getObjectPHID();
        if ($object_phid) {
            $handles = $viewer->loadHandles(array($object_phid));
            $handle = $handles[$object_phid];
            if ($handle->isComplete()) {
                $view->addProperty(\Yii::t("app",'Object'), $handle->renderLink());
            }
        }

        if ($task->isArchived()) {
            switch ($task->getResult()) {
                case PhabricatorWorkerArchiveTask::RESULT_SUCCESS:
                    $status = \Yii::t("app",'Complete');
                    break;
                case PhabricatorWorkerArchiveTask::RESULT_FAILURE:
                    $status = \Yii::t("app",'Failed');
                    break;
                case PhabricatorWorkerArchiveTask::RESULT_CANCELLED:
                    $status = \Yii::t("app",'Cancelled');
                    break;
                default:
                    throw new Exception(\Yii::t("app",'Unknown task status!'));
            }
        } else {
            $status = \Yii::t("app",'Queued');
        }

        $view->addProperty(
            \Yii::t("app",'Task Status'),
            $status);

        $view->addProperty(
            \Yii::t("app",'Task Class'),
            $task->getTaskClass());

        if ($task->getLeaseExpires()) {
            if ($task->getLeaseExpires() > time()) {
                $lease_status = \Yii::t("app",'Leased');
            } else {
                $lease_status = \Yii::t("app",'Lease Expired');
            }
        } else {
            $lease_status = phutil_tag('em', array(), \Yii::t("app",'Not Leased'));
        }

        $view->addProperty(
            \Yii::t("app",'Lease Status'),
            $lease_status);

        $view->addProperty(
            \Yii::t("app",'Lease Owner'),
            $task->getLeaseOwner()
                ? $task->getLeaseOwner()
                : phutil_tag('em', array(), \Yii::t("app",'None')));

        if ($task->getLeaseExpires() && $task->getLeaseOwner()) {
            $expires = ($task->getLeaseExpires() - time());
            $expires = phutil_format_relative_time_detailed($expires);
        } else {
            $expires = phutil_tag('em', array(), \Yii::t("app",'None'));
        }

        $view->addProperty(
            \Yii::t("app",'Lease Expires'),
            $expires);

        if ($task->isArchived()) {
            $duration = \Yii::t("app",'%s us', new PhutilNumber($task->getDuration()));
        } else {
            $duration = phutil_tag('em', array(), \Yii::t("app",'Not Completed'));
        }

        $view->addProperty(
            \Yii::t("app",'Duration'),
            $duration);
        $data = PhabricatorWorkerTaskData::findOne($task->getDataID());
        $task->setData(phutil_json_encode($data->getData()));
        $worker = $task->getWorkerInstance();
        $data = $worker->renderForDisplay($viewer);

        if ($data !== null) {
            $view->addProperty(\Yii::t("app",'Data'), $data);
        }

        return $view;
    }

    /**
     * @param PhabricatorWorkerTask $task
     * @return PHUIPropertyListView
     * @throws \orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException
     * @throws Exception
     * @author 陈妙威
     */
    private function buildRetryListView(PhabricatorWorkerTask $task)
    {
        $view = new PHUIPropertyListView();
        $data = PhabricatorWorkerTaskData::findOne($task->getDataID());
        $task->setData(phutil_json_encode($data->getData()));
        $worker = $task->getWorkerInstance();

        $view->addProperty(
            \Yii::t("app",'Failure Count'),
            $task->getFailureCount());

        $retry_count = $worker->getMaximumRetryCount();
        if ($retry_count === null) {
            $max_retries = phutil_tag('em', array(), \Yii::t("app",'Retries Forever'));
            $retry_count = INF;
        } else {
            $max_retries = $retry_count;
        }

        $view->addProperty(
            \Yii::t("app",'Maximum Retries'),
            $max_retries);

        $projection = clone $task;
        $projection->makeEphemeral();

        $next = array();
        for ($ii = $task->getFailureCount(); $ii < $retry_count; $ii++) {
            $projection->setFailureCount($ii);
            $next[] = $worker->getWaitBeforeRetry($projection);
            if (count($next) > 10) {
                break;
            }
        }

        if ($next) {
            $cumulative = 0;
            foreach ($next as $key => $duration) {
                if ($duration === null) {
                    $duration = 60;
                }
                $cumulative += $duration;
                $next[$key] = phutil_format_relative_time($cumulative);
            }
            if ($ii != $retry_count) {
                $next[] = '...';
            }
            $retries_in = implode(', ', $next);
        } else {
            $retries_in = \Yii::t("app",'No More Retries');
        }

        $view->addProperty(
            \Yii::t("app",'Retries After'),
            $retries_in);

        return $view;
    }

}
