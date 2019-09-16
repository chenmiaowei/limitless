<?php

namespace orangins\modules\userservice\view;

use orangins\lib\components\Redis;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUITagView;
use orangins\modules\conduit\method\ConduitAPIMethod;
use orangins\modules\conduit\query\PhabricatorConduitMethodQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\userservice\conduitprice\UserServiceConduitPrice;
use orangins\modules\userservice\models\PhabricatorUserService;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorDaemonTasksTableView
 * @package orangins\modules\daemon\view
 * @author 陈妙威
 */
final class PhabricatorUserServiceTableView extends AphrontView
{

    /**
     * @var PhabricatorUserService[]
     */
    private $tasks;
    /**
     * @var
     */
    private $noDataString;

    /**
     * @param PhabricatorUserService[] $tasks
     * @return $this
     * @author 陈妙威
     */
    public function setTasks(array $tasks)
    {
        $this->tasks = $tasks;
        return $this;
    }

    /**
     * @return PhabricatorUserService[]
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

        $userPHIDs = ArrayHelper::getColumn($tasks, 'user_phid');

        /** @var PhabricatorUser[] $users */
        $users = PhabricatorUser::find()
            ->setViewer($this->getViewer())
            ->withPHIDs($userPHIDs)
            ->execute();
        $users = mpull($users, null, 'getPHID');


        $apis = [];
        foreach ($tasks as $task) {
            $a = ArrayHelper::getValue($task->getParameters(), 'apis', []);
            foreach ($a as $item) {
                $apis[] = str_replace("PHID-COND-", "", $item);
            }
        }

        $methods = (new PhabricatorConduitMethodQuery())
            ->setViewer($this->getViewer())
            ->withMethods($apis)
            ->execute();
        $methods = mpull($methods, null, 'getAPIMethodName');
        /** @var Redis $obj */
        $obj = \Yii::$app->get("redis");

        $rowClass = [];
        foreach ($tasks as $k => $task) {
            $apis = [];
            $a = ArrayHelper::getValue($task->getParameters(), 'apis', []);
            foreach ($a as $item) {
                $apis[] = str_replace("PHID-COND-", "", $item);
            }
            /** @var ConduitAPIMethod[] $dict */
            $dict = array_select_keys($methods, $apis);

            $htmls = [];
            foreach ($dict as $item) {
                $htmls[] = JavelinHtml::phutil_tag_div("badge badge-success rounded-0", $item->getMethodDescription());
            }

            $cacheId = "price:" . $users[$task->user_phid]->getPHID() . ":amount";
            $amount = $obj->getClient()->get($cacheId);

            $cacheId = "price:" . $users[$task->user_phid]->getPHID() . ":times";
            $times = $obj->getClient()->get($cacheId);

            $rowClass[$k] = [
                'sigil' => 'batch-select-row',
                'meta' => [
                    'rowID' => $task->getID(),
                    'price' => $task->amount,
                ]
            ];
            $rows[] = array(
                $task->getID(),
                $users[$task->user_phid]->getUsername(),
                $task->type === "api.time" ? "包年包月" : "按量",
                JavelinHtml::phutil_implode_html("\n", $htmls),
                $task->type === "api.time" ? "不限" : $task->amount + ($amount !== null ? intval($amount) / UserServiceConduitPrice::getPrecision() : 0),
                $task->type === "api.time" ? date("Y-m-d", ArrayHelper::getValue($task->getParameters(), 'expire')) : "永不过期",
                $task->type === "api.time" ? ArrayHelper::getValue($task->getParameters(), 'times') + ($times !== null ? $times : 0) : "不限",
                JavelinHtml::phutil_implode_html("\n", [
                    $task->type === "api.time" ?
                        (new PHUIButtonView())
                            ->setTag("a")
                            ->setText("续费")
                            ->setWorkflow(true)
                            ->setSize("btn-xs")
                            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                            ->setHref(Url::to(['/userservice/index/renew'
                                , 'id' => $task->getID()
                                , 'redirect_uri' => \Yii::$app->request->url
                            ]))
                        : (new PHUIButtonView())
                        ->setTag("a")
                        ->setText("充值")
                        ->setWorkflow(true)
                        ->setSize("btn-xs")
                        ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                        ->setHref(Url::to(['/userservice/index/deposit'
                            , 'id' => $task->getID()
                            , 'redirect_uri' => \Yii::$app->request->url
                        ])),
                    (new PHUIButtonView())
                        ->setTag("a")
                        ->setText("修改")
                        ->setWorkflow(true)
                        ->setSize("btn-xs")
                        ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                        ->setHref(Url::to(['/userservice/index/edit'
                            , 'id' => $task->getID()
                            , 'redirect_uri' => \Yii::$app->request->url
                        ])),
                    (new PHUIButtonView())
                        ->setTag("a")
                        ->setText("禁用")
                        ->setColor(PHUITagView::COLOR_DANGER_800)
                        ->setWorkflow(true)
                        ->setSize("btn-xs")
                        ->setHref(Url::to(['/userservice/index/disable'
                            , 'id' => $task->getID()
                            , 'redirect_uri' => \Yii::$app->request->url
                        ])),

                ])
            );
        }

        $table = new AphrontTableView($rows);
        $table->setRowAttributes($rowClass);
        $table->setHeaders(
            array(
                \Yii::t("app", 'ID'),
                \Yii::t("app", '用户'),
                \Yii::t("app", '类型'),
                \Yii::t("app", '接口名称'),
                \Yii::t("app", '余额'),
                \Yii::t("app", '过期时间'),
                \Yii::t("app", '次数'),
                \Yii::t("app", 'Actions'),
            ));
        $table->setColumnClasses(
            array(
                'n',
                'wide',
                'wide',
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