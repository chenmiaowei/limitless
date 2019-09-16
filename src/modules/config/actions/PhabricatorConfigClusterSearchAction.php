<?php

namespace orangins\modules\config\actions;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorConfigClusterSearchAction
 * @package orangins\modules\config\actions
 * @author 陈妙威
 */
final class PhabricatorConfigClusterSearchAction
    extends PhabricatorConfigAction
{

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $nav = $this->buildSideNavView();
        $nav->selectFilter('cluster/search/');

        $title = \Yii::t("app", 'Cluster Search');
        $doc_href = PhabricatorEnv::getDoclink('Cluster: Search');

        $button = (new PHUIButtonView())
            ->setIcon('fa-book')
            ->setHref($doc_href)
            ->setTag('a')
            ->setText(\Yii::t("app", 'Documentation'));

        $header = $this->buildHeaderView($title, $button);

        $search_status = $this->buildClusterSearchStatus();

        $crumbs = $this->buildApplicationCrumbs()
            ->addTextCrumb($title)
            ->setBorder(true);

        $content = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setNavigation($nav)
            ->setFixed(true)
            ->setMainColumn($search_status);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($content);
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    private function buildClusterSearchStatus()
    {
        $viewer = $this->getViewer();

        $services = PhabricatorSearchService::getAllServices();
        JavelinHtml::initBehavior(new JavelinTooltipAsset());

        $view = array();
        foreach ($services as $service) {
            $view[] = $this->renderStatusView($service);
        }
        return $view;
    }

    /**
     * @param $service
     * @return array
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    private function renderStatusView($service)
    {
        $head = array_merge(
            array(\Yii::t("app", 'Type')),
            array_keys($service->getStatusViewColumns()),
            array(\Yii::t("app", 'Status')));

        $rows = array();

        $status_map = PhabricatorSearchService::getConnectionStatusMap();
        $stats = false;
        $stats_view = false;

        foreach ($service->getHosts() as $host) {
            try {
                $status = $host->getConnectionStatus();
                $status = ArrayHelper::getValue($status_map, $status, array());
            } catch (Exception $ex) {
                $status['icon'] = 'fa-times';
                $status['label'] = \Yii::t("app", 'Connection Error');
                $status['color'] = 'red';
                $host->didHealthCheck(false);
            }

            if (!$stats_view) {
                try {
                    $stats = $host->getEngine()->getIndexStats($host);
                    $stats_view = $this->renderIndexStats($stats);
                } catch (Exception $e) {
                    $stats_view = false;
                }
            }

            $type_icon = 'fa-search sky';
            $type_tip = $host->getDisplayName();

            $type_icon = (new PHUIIconView())
                ->setIcon($type_icon);
            $status_view = array(
                (new PHUIIconView())->setIcon($status['icon'] . ' ' . $status['color']),
                ' ',
                $status['label'],
            );
            $row = array(array($type_icon, ' ', $type_tip));
            $row = array_merge($row, array_values(
                $host->getStatusViewColumns()));
            $row[] = $status_view;
            $rows[] = $row;
        }

        $table = (new AphrontTableView($rows))
            ->setNoDataString(\Yii::t("app", 'No search servers are configured.'))
            ->setHeaders($head);

        $view = $this->buildConfigBoxView(\Yii::t("app", 'Search Servers'), $table);

        $stats = null;
        if ($stats_view->hasAnyProperties()) {
            $stats = $this->buildConfigBoxView(
                \Yii::t("app", '%s Stats', $service->getDisplayName()),
                $stats_view);
        }

        return array($stats, $view);
    }

    /**
     * @param $stats
     * @return PHUIPropertyListView
     * @author 陈妙威
     */
    private function renderIndexStats($stats)
    {
        $view = (new PHUIPropertyListView());
        if ($stats !== false) {
            foreach ($stats as $label => $val) {
                $view->addProperty($label, $val);
            }
        }
        return $view;
    }

}
