<?php

namespace orangins\modules\config\actions;

use orangins\lib\view\phui\PHUIInfoView;

final class PhabricatorConfigIssueViewAction
    extends PhabricatorConfigAction
{

    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $issue_key = $request->getURIData('key');

        $engine = new PhabricatorSetupEngine();
        $response = $engine->execute();
        if ($response) {
            return $response;
        }
        $issues = $engine->getIssues();

        $nav = $this->buildSideNavView();
        $nav->selectFilter('issue/');

        if (empty($issues[$issue_key])) {
            $content = (new PHUIInfoView())
                ->setSeverity(PHUIInfoView::SEVERITY_NOTICE)
                ->setTitle(\Yii::t("app",'Issue Resolved'))
                ->appendChild(\Yii::t("app",'This setup issue has been resolved. '))
                ->appendChild(
                    phutil_tag(
                        'a',
                        array(
                            'href' => $this->getApplicationURI('issue/'),
                        ),
                        \Yii::t("app",'Return to Open Issue List')));
            $title = \Yii::t("app",'Resolved Issue');
        } else {
            $issue = $issues[$issue_key];
            $content = $this->renderIssue($issue);
            $title = $issue->getShortName();
        }

        $header = $this->buildHeaderView($title);

        $crumbs = $this
            ->buildApplicationCrumbs()
            ->setBorder(true)
            ->addTextCrumb(\Yii::t("app",'Setup Issues'), $this->getApplicationURI('issue/'))
            ->addTextCrumb($title, $request->getRequestURI())
            ->setBorder(true);

        $content = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setNavigation($nav)
            ->setFixed(true)
            ->setMainColumn($content);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($content);
    }

    private function renderIssue(PhabricatorSetupIssue $issue)
    {
//        require_celerity_resource('setup-issue-css');

        $view = new PhabricatorSetupIssueView();
        $view->setIssue($issue);

        $container = phutil_tag(
            'div',
            array(
                'class' => 'setup-issue-background',
            ),
            $view->render());

        return $container;
    }

}
