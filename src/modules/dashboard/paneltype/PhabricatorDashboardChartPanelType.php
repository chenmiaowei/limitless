<?php

namespace orangins\modules\dashboard\paneltype;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\modules\dashboard\editfield\PhabricatorDashboardChartCountPanelColorEditField;
use orangins\modules\dashboard\editfield\PhabricatorDashboardChartCountPanelEditField;
use orangins\modules\dashboard\editfield\PhabricatorDashboardChartCountPanelIconEditField;
use orangins\modules\dashboard\editfield\PhabricatorDashboardChartLinePanelEditField;
use orangins\modules\dashboard\editfield\PhabricatorDashboardChartPanelTimeEditField;
use orangins\modules\dashboard\editfield\PhabricatorDashboardChartPanelTypeEditField;
use orangins\modules\dashboard\editfield\PhabricatorDashboardChartPiePanelEditField;
use orangins\modules\dashboard\engine\PhabricatorDashboardPanelRenderingEngine;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\dashboard\paneltype\chart\PhabricatorDashboardPanelChartCountDataSourceEngine;
use orangins\modules\dashboard\paneltype\chart\PhabricatorDashboardPanelChartLineDataSourceEngine;
use orangins\modules\dashboard\xaction\panel\PhabricatorDashboardChartCountPanelColorTransaction;
use orangins\modules\dashboard\xaction\panel\PhabricatorDashboardChartCountPanelDataTransaction;
use orangins\modules\dashboard\xaction\panel\PhabricatorDashboardChartCountPanelIconTransaction;
use orangins\modules\dashboard\xaction\panel\PhabricatorDashboardChartLinePanelDataTransaction;
use orangins\modules\dashboard\xaction\panel\PhabricatorDashboardChartPanelTimeTransaction;
use orangins\modules\dashboard\xaction\panel\PhabricatorDashboardChartPiePanelDataTransaction;
use orangins\modules\dashboard\xaction\panel\PhabricatorDashboardChartTypePanelDataTransaction;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\widgets\javelin\JavelinEchartBehaviorAsset;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorDashboardTextPanelType
 * @package orangins\modules\dashboard\paneltype
 * @author 陈妙威
 */
final class PhabricatorDashboardChartPanelType extends PhabricatorDashboardPanelType
{
    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelTypeKey()
    {
        return 'chart';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelTypeName()
    {
        return \Yii::t("app", 'Chart Panel');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-paragraph';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelTypeDescription()
    {
        return \Yii::t("app",
            '添加可视化图标到桌面，您可以配置可视化数据来源.');
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return array|mixed
     * @author 陈妙威
     */
    protected function newEditEngineFields(PhabricatorDashboardPanel $panel)
    {
        $typeControl = (new PhabricatorDashboardChartPanelTypeEditField())
            ->setKey('chart_type')
            ->setLabel(\Yii::t("app", '图标类型'))
            ->setTransactionType(
                PhabricatorDashboardChartTypePanelDataTransaction::TRANSACTIONTYPE)
            ->setValue($panel->getProperty('chart_type', null));

        return array(
            $typeControl,
            (new PhabricatorDashboardChartPanelTimeEditField())
                ->setKey('chart_time')
                ->setLabel(\Yii::t("app", '时间'))
                ->setTransactionType(
                    PhabricatorDashboardChartPanelTimeTransaction::TRANSACTIONTYPE)
                ->setValue($panel->getProperty('chart_time', [])),
            (new PhabricatorDashboardChartLinePanelEditField())
                ->setKey('line_datasource')
                ->setLabel(\Yii::t("app", '数据源'))
                ->setTypeControlID($typeControl->getID())
                ->setTransactionType(
                    PhabricatorDashboardChartLinePanelDataTransaction::TRANSACTIONTYPE)
                ->setValue($panel->getProperty('line_datasource', [])),
            (new PhabricatorDashboardChartPiePanelEditField())
                ->setKey('pie_datasource')
                ->setLabel(\Yii::t("app", '数据源'))
                ->setTypeControlID($typeControl->getID())
                ->setTransactionType(
                    PhabricatorDashboardChartPiePanelDataTransaction::TRANSACTIONTYPE)
                ->setValue($panel->getProperty('pie_datasource', [])),
            (new PhabricatorDashboardChartCountPanelIconEditField())
                ->setKey('count_icon')
                ->setLabel(\Yii::t("app", '图标'))
                ->setControlInputID($typeControl->getID())
                ->setTransactionType(
                    PhabricatorDashboardChartCountPanelIconTransaction::TRANSACTIONTYPE)
                ->setValue($panel->getProperty('count_icon', null)),
            (new PhabricatorDashboardChartCountPanelColorEditField())
                ->setKey('count_bg_color')
                ->setLabel(\Yii::t("app", '背景颜色'))
                ->setTypeControlID($typeControl->getID())
                ->setTransactionType(
                    PhabricatorDashboardChartCountPanelColorTransaction::TRANSACTIONTYPE)
                ->setValue($panel->getProperty('count_bg_color', null)),
            (new PhabricatorDashboardChartCountPanelEditField())
                ->setKey('count_datasource')
                ->setLabel(\Yii::t("app", '数据源'))
                ->setTypeControlID($typeControl->getID())
                ->setTransactionType(
                    PhabricatorDashboardChartCountPanelDataTransaction::TRANSACTIONTYPE)
                ->setValue($panel->getProperty('count_datasource', [])),

        );
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRenderAsync()
    {
        // Rendering text panels is normally a cheap cache hit.
        return true;
    }


    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorDashboardPanel $panel
     * @param PhabricatorDashboardPanelRenderingEngine $engine
     * @param PHUIHeaderView $header
     * @author 陈妙威
     * @return null
     * @throws Exception
     */
    public function adjustPanelHeader(
        PhabricatorUser $viewer,
        PhabricatorDashboardPanel $panel,
        PhabricatorDashboardPanelRenderingEngine $engine,
        PHUIHeaderView $header)
    {
        $type = $panel->getProperty('chart_type', []);
        if ($type === 'count') {
            return null;
        } else {
            return $header;
        }
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return array
     * @author 陈妙威
     */
    public function getCardClasses(PhabricatorDashboardPanel $panel)
    {
        $type = $panel->getProperty('chart_type', []);
        $count_bg_color = $panel->getProperty('count_bg_color', null);
        if ($type === 'count') {
            return ["bg-{$count_bg_color}"];
        } else {
            return [];
        }
    }


    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorDashboardPanel $panel
     * @param PhabricatorDashboardPanelRenderingEngine $engine
     * @return mixed
     * @author 陈妙威
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function renderPanelContent(
        PhabricatorUser $viewer,
        PhabricatorDashboardPanel $panel,
        PhabricatorDashboardPanelRenderingEngine $engine)
    {
        $type = $panel->getProperty('chart_type', []);
        $chart_time = $panel->getProperty('chart_time', 'all');

        if ($type === 'line') {
            $key = $panel->getProperty('line_datasource', []);
            $phabricatorDashboardPanelChartLineEngines = PhabricatorDashboardPanelChartLineDataSourceEngine::getAllEngines();

            $defaultColors = [
                "#1565C0",
                "#C62828",
                "#2E7D32",
                "#D84315",
                "#00838F",
                "#AD1457",
                "#6A1B9A",
                "#4527A0",
                "#283593",
                "#0277BD",
                "#00695C",
                "#558B2F",
                "#EF6C00",
                "#4E342E",
                "#444444",
                "#37474F",
            ];
            $color = [];

            /** @var PhabricatorDashboardPanelChartLineDataSourceEngine[] $dict */
            $dict = array_select_keys($phabricatorDashboardPanelChartLineEngines, $key);


            $titles = [];
            $key = [];
            $data = [];
            foreach ($dict as $item) {
                $titles[] = $item->getDescription();
                $source = $item->getData($chart_time === 'all' ? null : date("Y-m-d", strtotime($chart_time)));
                $key = ArrayHelper::getValue($source, 'key', []);
                $data[] = ArrayHelper::getValue($source, 'value', []);
                $array_splice = array_splice($defaultColors, rand(0, count($defaultColors)), 1);
                $color[] = head($array_splice);
            }

            $generateUniqueNodeId = JavelinHtml::generateUniqueNodeId();
            JavelinHtml::initBehavior(new JavelinEchartBehaviorAsset(), [
                'id' => $generateUniqueNodeId,
                'colors' => $color,
                'keys' => $key,
                'titles' => $titles,
                'data' => $data,
                'chart_type' => 'line'
            ]);
            return JavelinHtml::phutil_tag("div", [
                'id' => $generateUniqueNodeId,
                'class' => 'p-3 chart has-fixed-height'
            ]);
        } else if ($type === 'count') {
//            <div class="col-sm-6 col-xl-3">
//						<div class="card card-body bg-danger-400 has-bg-image">
//							<div class="media">
//								<div class="media-body">
//									<h3 class="mb-0">389,438</h3>
//									<span class="text-uppercase font-size-xs">total orders</span>
//								</div>
//
//								<div class="ml-3 align-self-center">
//									<i class="icon-bag icon-3x opacity-75"></i>
//								</div>
//							</div>
//						</div>
//					</div>

            $key = $panel->getProperty('count_datasource', []);
            $phabricatorDashboardPanelChartLineEngines = PhabricatorDashboardPanelChartCountDataSourceEngine::getAllEngines();
            $dict = array_select_keys($phabricatorDashboardPanelChartLineEngines, $key);


            $count_icon = $panel->getProperty('count_icon', null);


            /** @var PhabricatorDashboardPanelChartCountDataSourceEngine $wild */
            $wild = head($dict);
            if ($wild) {
                $data1 = $wild->getData($chart_time === 'all' ? null : date("Y-m-d", strtotime($chart_time)));
                return JavelinHtml::phutil_tag("div", [
                    "class" => "media"
                ], [
                    JavelinHtml::phutil_tag("div", [
                        "class" => "media-body"
                    ], [
                        JavelinHtml::phutil_tag("h3", [
                            "class" => "mb-0"
                        ], ArrayHelper::getValue($data1, 'value', 0)),
                        JavelinHtml::phutil_tag("span", [
                            "class" => "text-uppercase font-size-xs"
                        ], $wild->getDescription()),
                    ]),
                    JavelinHtml::phutil_tag("div", [
                        "class" => "ml-3 align-self-center"
                    ], [
                        JavelinHtml::phutil_tag("i", [
                            "class" => "fa {$count_icon} fa-3x"
                        ])
                    ])
                ]);
            } else {
                return '';
            }
        } else {
            return '';
        }
    }
}
