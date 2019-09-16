<?php

namespace orangins\modules\config\actions;

use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\cache\spec\PhabricatorCacheSpec;
use orangins\modules\cache\spec\PhabricatorDataCacheSpec;
use orangins\modules\cache\spec\PhabricatorOpcodeCacheSpec;
use PhutilNumber;
use yii\helpers\Url;

/**
 * Class PhabricatorConfigCacheAction
 * @package orangins\modules\config\actions
 * @author 陈妙威
 */
final class PhabricatorConfigCacheAction
    extends PhabricatorConfigAction
{

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $nav = $this->buildSideNavView();
        $nav->selectFilter('cache/');

        $purge_button = (new PHUIButtonView())
            ->setText(\Yii::t("app",'Purge Caches'))
            ->setHref(Url::to(['/config/cache/purge']))
            ->setTag('a')
            ->setWorkflow(true)
            ->setIcon('fa-exclamation-triangle');

        $title = \Yii::t("app",'Cache Status');
        $header = $this->buildHeaderView($title, $purge_button);

        $code_box = $this->renderCodeBox();
        $data_box = $this->renderDataBox();

        $page = array(
            $code_box,
            $data_box,
        );

        $crumbs = $this->buildApplicationCrumbs()
            ->addTextCrumb($title)
            ->setBorder(true);

        $content = (new PHUITwoColumnView())
            ->setNavigation($nav)
            ->setFixed(true)
            ->setMainColumn($page);

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($content);
    }

    /**
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderCodeBox()
    {
        $cache = PhabricatorOpcodeCacheSpec::getActiveCacheSpec();
        $properties = (new PHUIPropertyListView())->addClass("m-3");
        $this->renderCommonProperties($properties, $cache);
        return $this->buildConfigBoxView(\Yii::t("app",'Opcode Cache'), $properties);
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderDataBox()
    {
        $cache = PhabricatorDataCacheSpec::getActiveCacheSpec();

        $properties = (new PHUIPropertyListView())->addClass("m-3");

        $this->renderCommonProperties($properties, $cache);

        $table = null;
        if ($cache->getName() !== null) {
            $total_memory = $cache->getTotalMemory();

            $summary = $cache->getCacheSummary();
            $summary = isort($summary, 'total');
            $summary = array_reverse($summary, true);

            $rows = array();
            foreach ($summary as $key => $info) {
                $rows[] = array(
                    $key,
                    \Yii::t("app",'%s', new PhutilNumber($info['count'])),
                    phutil_format_bytes($info['max']),
                    phutil_format_bytes($info['total']),
                    sprintf('%.1f%%', (100 * ($info['total'] / $total_memory))),
                );
            }

            $table = (new AphrontTableView($rows))
                ->setHeaders(
                    array(
                        \Yii::t("app",'Pattern'),
                        \Yii::t("app",'Count'),
                        \Yii::t("app",'Largest'),
                        \Yii::t("app",'Total'),
                        \Yii::t("app",'Usage'),
                    ))
                ->setColumnClasses(
                    array(
                        'wide',
                        'n',
                        'n',
                        'n',
                        'n',
                    ));
        }

        $properties = $this->buildConfigBoxView(\Yii::t("app",'Data Cache'), $properties);
        $table = $this->buildConfigBoxView(\Yii::t("app",'Cache Storage'), $table);
        return array($properties, $table);
    }

    /**
     * @param PHUIPropertyListView $properties
     * @param PhabricatorCacheSpec $cache
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderCommonProperties(
        PHUIPropertyListView $properties,
        PhabricatorCacheSpec $cache)
    {

        if ($cache->getName() !== null) {
            $name = $this->renderYes($cache->getName());
        } else {
            $name = $this->renderNo(\Yii::t("app",'None'));
        }
        $properties->addProperty(\Yii::t("app",'Cache'), $name);

        if ($cache->getIsEnabled()) {
            $enabled = $this->renderYes(\Yii::t("app",'Enabled'));
        } else {
            $enabled = $this->renderNo(\Yii::t("app",'Not Enabled'));
        }
        $properties->addProperty(\Yii::t("app",'Enabled'), $enabled);

        $version = $cache->getVersion();
        if ($version) {
            $properties->addProperty(\Yii::t("app",'Version'), $this->renderInfo($version));
        }

        if ($cache->getName() === null) {
            return;
        }

        $mem_total = $cache->getTotalMemory();
        $mem_used = $cache->getUsedMemory();

        if ($mem_total) {
            $percent = 100 * ($mem_used / $mem_total);

            $properties->addProperty(
                \Yii::t("app",'Memory Usage'),
                \Yii::t("app",
                    '%s of %s',
                    phutil_tag('strong', array(), sprintf('%.1f%%', $percent)),
                    phutil_format_bytes($mem_total)));
        }

        $entry_count = $cache->getEntryCount();
        if ($entry_count !== null) {
            $properties->addProperty(
                \Yii::t("app",'Cache Entries'),
                \Yii::t("app",'%s', new PhutilNumber($entry_count)));
        }

    }

    /**
     * @param $info
     * @return array
     * @author 陈妙威
     */
    private function renderYes($info)
    {
        return array(
            (new PHUIIconView())->setIcon('fa-check', 'green'),
            ' ',
            $info,
        );
    }

    /**
     * @param $info
     * @return array
     * @author 陈妙威
     */
    private function renderNo($info)
    {
        return array(
            (new PHUIIconView())->setIcon('fa-times-circle', 'red'),
            ' ',
            $info,
        );
    }

    /**
     * @param $info
     * @return array
     * @author 陈妙威
     */
    private function renderInfo($info)
    {
        return array(
            (new PHUIIconView())->setIcon('fa-info-circle', 'grey'),
            ' ',
            $info,
        );
    }

}
