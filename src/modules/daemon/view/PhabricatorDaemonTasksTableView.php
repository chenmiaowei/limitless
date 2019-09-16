<?php

namespace orangins\modules\daemon\view;

use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTask;
use orangins\lib\view\AphrontView;
use orangins\lib\view\control\AphrontTableView;
use yii\helpers\Url;

/**
 * Class PhabricatorDaemonTasksTableView
 * @package orangins\modules\daemon\view
 * @author 陈妙威
 */
final class PhabricatorDaemonTasksTableView extends AphrontView
{

    /**
     * @var PhabricatorWorkerTask[]
     */
    private $tasks;
    /**
     * @var
     */
    private $noDataString;

    /**
     * @param array $tasks
     * @return $this
     * @author 陈妙威
     */
    public function setTasks(array $tasks)
    {
        $this->tasks = $tasks;
        return $this;
    }

    /**
     * @return PhabricatorWorkerTask[]
     * @author 陈妙威
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * @param $no_data_string
     * @return $this
     * @author 陈妙威
     */
    public function setNoDataString($no_data_string)
    {
        $this->noDataString = $no_data_string;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getNoDataString()
    {
        return $this->noDataString;
    }

    /**
     * @return mixed|AphrontTableView
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        $tasks = $this->getTasks();
        $rows = array();
        foreach ($tasks as $task) {
            $rows[] = array(
                $task->getID(),
                $task->getTaskClass(),
                $task->getLeaseOwner(),
                $task->getLeaseExpires()
                    ? phutil_format_relative_time($task->getLeaseExpires() - time())
                    : '-',
                $task->getPriority(),
                $task->getFailureCount(),
                phutil_tag(
                    'a',
                    array(
                        'href' => Url::to([
                            '/daemon/index/view', 'id' => $task->getID()
                        ]),
                        'class' => 'button small button-grey',
                    ),
                    \Yii::t("app",'View Task')),
            );
        }

        $table = new AphrontTableView($rows);
        $table->setHeaders(
            array(
                \Yii::t("app",'ID'),
                \Yii::t("app",'Class'),
                \Yii::t("app",'Owner'),
                \Yii::t("app",'Expires'),
                \Yii::t("app",'Priority'),
                \Yii::t("app",'Failures'),
                '',
            ));
        $table->setColumnClasses(
            array(
                'n',
                'wide',
                '',
                '',
                'n',
                'n',
                'action',
            ));

        if (strlen($this->getNoDataString())) {
            $table->setNoDataString($this->getNoDataString());
        }

        return $table;
    }

}
