<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/14
 * Time: 11:12 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\finance\actions;


use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use yii\helpers\Url;

/**
 * Class FinanceDashboardAction
 * @package orangins\modules\finance\actions
 * @author 陈妙威
 */
class FinanceDashboardAction extends FinanceAction
{
    /**
     * @author 陈妙威
     */
    public function run()
    {
        $title = \Yii::t("app", "Dashboard");

        $view = [];
        $view[] = JavelinHtml::phutil_tag_div("text-center", JavelinHtml::phutil_tag("i", [
            "class" => "icon-cash3 icon-2x text-danger border-danger border-3 rounded-round p-3 mb-3"
        ]));
        $view[] = JavelinHtml::phutil_tag("h1", [], [
            "￥ 0.00"
        ]);

        $header = (new PHUIHeaderView())
            ->addActionItem((new PHUIButtonView())->setTag("a")->setText("充值")->setWorkflow(true)->addClass("btn-xs")->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))->setHref(Url::to(['/finance/index/deposit'])))
            ->setHeader(\Yii::t("app", "可用余额"));
        $card = (new PHUIObjectBoxView())
            ->setHeader($header)
            ->addBodyClass("text-center")
            ->addClass('phabricator-notification-list')
            ->appendChild($view);


        $view = [];
        $view[] = JavelinHtml::phutil_tag_div("text-center", JavelinHtml::phutil_tag("i", [
            "class" => "icon-printer4 icon-2x text-danger border-danger border-3 rounded-round p-3 mb-3"
        ]));
        $view[] = JavelinHtml::phutil_tag("h1", [], [
            "￥ 0.00"
        ]);
        $header1 = (new PHUIHeaderView())
            ->addActionItem((new PHUIButtonView())->setTag("a")->setText("消费清单")->setWorkflow(true)->addClass("btn-xs")->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))->setHref(Url::to(['/finance/index/deposit'])))
            ->setHeader(\Yii::t("app", "本月消费"));
        $card1 = (new PHUIObjectBoxView())
            ->setHeader($header1)
            ->addBodyClass("text-center")
            ->addClass('phabricator-notification-list')
            ->appendChild($view);

        $row = JavelinHtml::phutil_tag_div("row", [
            JavelinHtml::phutil_tag_div("col-lg-6", [
                $card,
            ]),
            JavelinHtml::phutil_tag_div("col-lg-6", [
                $card1,
            ]),
        ]);

        $nav = $this->buildSideNavView();
        $nav->selectFilter("dashboard");

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", $title));
        $crumbs->setBorder(true);

        $header = new PHUIPageHeaderView();
        $header->setHeaderIcon("fa-dashboard");
        $header->setHeader($title);

        return $this->newPage()
            ->setCrumbs($crumbs)
            ->setHeader($header)
            ->setNavigation($nav)
            ->appendChild($row)
            ->setTitle($title);
    }
}