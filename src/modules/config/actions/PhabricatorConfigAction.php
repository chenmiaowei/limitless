<?php

namespace orangins\modules\config\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\modules\config\module\PhabricatorConfigModule;
use orangins\modules\file\models\PhabricatorFile;
use PhutilURI;

/**
 * Class PhabricatorConfigAction
 * @package orangins\modules\config\actions
 * @author 陈妙威
 */
abstract class PhabricatorConfigAction extends PhabricatorAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireAdmin()
    {
        return true;
    }

    /**
     * @param null $filter
     * @param bool $for_app
     * @return AphrontSideNavFilterView
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSideNavView($filter = null, $for_app = false)
    {

        $guide_href = new PhutilURI('/guides/');
        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));
        $nav->addFilter('/', \Yii::t("app",'Core Settings'), $this->getApplicationURI(), 'fa-gear');
        $nav->addFilter('application/',
            \Yii::t("app",'Application Settings'), $this->getApplicationURI('index/application'), 'fa-globe');
        $nav->addFilter('history/',
            \Yii::t("app",'Settings History'), $this->getApplicationURI('index/history'), 'fa-history');
        $nav->addFilter('version/',
            \Yii::t("app",'Version Information'), $this->getApplicationURI('index/version'), 'fa-download');
        $nav->addFilter('all/',
            \Yii::t("app",'All Settings'), $this->getApplicationURI('index/all'), 'fa-list-ul');
        $nav->addLabel(\Yii::t("app",'Setup'));
        $nav->addFilter('issue/',
            \Yii::t("app",'Setup Issues'), $this->getApplicationURI('issue/index'), 'fa-warning');
        $nav->addFilter(null,
            \Yii::t("app",'Installation Guide'), $guide_href, 'fa-book');
        $nav->addLabel(\Yii::t("app",'Database'));
        $nav->addFilter('database/',
            \Yii::t("app",'Database Status'),  $this->getApplicationURI('index/database'), 'fa-heartbeat');
        $nav->addFilter('dbissue/',
            \Yii::t("app",'Database Issues'), $this->getApplicationURI('index/dbissue'), 'fa-exclamation-circle');
        $nav->addLabel(\Yii::t("app",'Cache'));
        $nav->addFilter('cache/',
            \Yii::t("app",'Cache Status'), $this->getApplicationURI('cache/index'), 'fa-home');
        $nav->addLabel(\Yii::t("app",'Cluster'));
        $nav->addFilter('cluster/databases/',
            \Yii::t("app",'Database Servers'), $this->getApplicationURI('cluster/databases'), 'fa-database');
        $nav->addFilter('cluster/notifications/',
            \Yii::t("app",'Notification Servers'), $this->getApplicationURI('cluster/notifications'), 'fa-bell-o');
        $nav->addFilter('cluster/repositories/',
            \Yii::t("app",'Repository Servers'), $this->getApplicationURI('cluster/repositories'), 'fa-code');
        $nav->addFilter('cluster/search/',
            \Yii::t("app",'Search Servers'), $this->getApplicationURI('cluster/search'), 'fa-search');
        $nav->addLabel(\Yii::t("app",'Modules'));

        $modules = PhabricatorConfigModule::getAllModules();
        foreach ($modules as $key => $module) {
            $nav->addFilter('module/' . $key . '/', $module->getModuleName(), $this->getApplicationURI('index/module', ['module' => $key]), 'fa-puzzle-piece');
        }
        return $nav;
    }

    /**
     * @return null|\orangins\lib\view\phui\PHUIListView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        return $this->buildSideNavView(null, true)->getMenu();
    }

    /**
     * @param $text
     * @param null $action
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    public function buildHeaderView($text, $action = null)
    {
        $viewer = $this->getViewer();

        $file = PhabricatorFile::loadBuiltin('projects/v3/manage.png', $viewer);
        $image = $file->getBestURI($file);
        $header = (new PHUIPageHeaderView())
            ->setHeader($text)
            ->setProfileHeader(true)
            ->setImage($image);

        if ($action) {
            $header->addActionLink($action);
        }

        return $header;
    }

    /**
     * @param $title
     * @param $content
     * @param null $action
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    public function buildConfigBoxView($title, $content, $action = null)
    {
        $header = (new PHUIHeaderView())
            ->setHeader($title);

        if ($action) {
            $header->addActionItem($action);
        }

        $view = (new PHUIObjectBoxView())
            ->addBodyClass("p-0")
            ->setHeader($header)
            ->appendChild($content)
            ->setBackground(PHUIObjectBoxView::WHITE_CONFIG);

        return $view;
    }

}
