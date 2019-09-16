<?php

namespace orangins\modules\rbac\query;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\modules\rbac\models\RbacRole;
use yii\helpers\Url;

/**
 * Class PhabricatorDaemonTasksTableView
 * @package orangins\modules\daemon\view
 * @author 陈妙威
 */
final class PhabricatorRBACRoleTableView extends AphrontView
{

    /**
     * @var RbacRole[]
     */
    private $tasks;
    /**
     * @var
     */
    private $noDataString;

    /**
     * @param RbacRole[] $tasks
     * @return $this
     * @author 陈妙威
     */
    public function setTasks(array $tasks)
    {
        $this->tasks = $tasks;
        return $this;
    }

    /**
     * @return RbacRole[]
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
                $task->id,
                $task->name,
                $task->description,
                JavelinHtml::phutil_implode_html("\n", [
                    (new PHUIButtonView())
                        ->setTag("a")
                        ->setText("查看详情")
                        ->setWorkflow(false)
                        ->setSize("btn-xs")
                        ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                        ->setHref(Url::to(['/rbac/role/view'
                            , 'id' => $task->getID()
                            , 'redirect_uri' => \Yii::$app->request->url
                        ])),
                ])
            );
        }

        $table = new AphrontTableView($rows);
        $table->setHeaders(
            array(
                \Yii::t("app",'ID'),
                \Yii::t("app",'名称'),
                \Yii::t("app",'描述'),
                \Yii::t("app",'操作'),
            ));
        $table->setColumnClasses(
            array(
                '',
                '',
                '',
                '',
            ));

        if (strlen($this->getNoDataString())) {
            $table->setNoDataString($this->getNoDataString());
        }

        return $table;
    }

}