<?php

namespace orangins\modules\config\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\config\module\PhabricatorConfigModule;

/**
 * Class PhabricatorConfigModuleAction
 * @package orangins\modules\config\actions
 * @author 陈妙威
 */
final class PhabricatorConfigModuleAction
    extends PhabricatorConfigAction
{

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView|Aphront404Response
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $key = $request->getURIData('module');

        $all_modules = PhabricatorConfigModule::getAllModules();
        if (empty($all_modules[$key])) {
            return new Aphront404Response();
        }

        $module = $all_modules[$key];
        $content = $module->renderModuleStatus($request);
        $title = $module->getModuleName();

        $nav = $this->buildSideNavView();
        $nav->selectFilter('module/' . $key . '/');
        $header = $this->buildHeaderView($title);

        $view = $this->buildConfigBoxView($title, $content);

        $crumbs = $this->buildApplicationCrumbs()
            ->addTextCrumb($title)
            ->setBorder(true);

        $content = (new PHUITwoColumnView())
            ->setNavigation($nav)
            ->setFixed(true)
            ->setMainColumn($view);

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($content);
    }

}
