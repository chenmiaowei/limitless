<?php

namespace orangins\modules\config\actions;

use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\config\check\PhabricatorSetupCheck;
use orangins\modules\config\engine\PhabricatorSetupEngine;
use orangins\modules\config\issue\PhabricatorSetupIssue;

/**
 * Class PhabricatorConfigIssueListAction
 * @package orangins\modules\config\actions
 * @author 陈妙威
 */
final class PhabricatorConfigIssueListAction extends PhabricatorConfigAction
{

    /**
     * @return null|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $nav = $this->buildSideNavView();
        $nav->selectFilter('issue/');

        $engine = new PhabricatorSetupEngine();
        $response = $engine->execute();
        if ($response) {
            return $response;
        }
        $issues = $engine->getIssues();

        $important = $this->buildIssueList(
            $issues,
            PhabricatorSetupCheck::GROUP_IMPORTANT,
            'fa-warning');
        $php = $this->buildIssueList(
            $issues,
            PhabricatorSetupCheck::GROUP_PHP,
            'fa-code');
        $mysql = $this->buildIssueList(
            $issues,
            PhabricatorSetupCheck::GROUP_MYSQL,
            'fa-database');
        $other = $this->buildIssueList(
            $issues,
            PhabricatorSetupCheck::GROUP_OTHER,
            'fa-question-circle');

        $title = \Yii::t("app", 'Setup Issues');
        $header = $this->buildHeaderView($title);

        if (!$issues) {
            $issue_list = (new PHUIInfoView())
                ->setTitle(\Yii::t("app", 'No Issues'))
                ->appendChild(
                    \Yii::t("app", 'Your install has no current setup issues to resolve.'))
                ->setSeverity(PHUIInfoView::SEVERITY_NOTICE);
        } else {
            $issue_list = array(
                $important,
                $php,
                $mysql,
                $other,
            );

            $issue_list = $this->buildConfigBoxView(\Yii::t("app", 'Issues'), $issue_list);
        }

        $crumbs = $this->buildApplicationCrumbs()
            ->addTextCrumb($title)
            ->setBorder(true);

        $content = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setNavigation($nav)
            ->setFixed(true)
            ->setMainColumn($issue_list);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($content);
    }

    /**
     * @param array $issues
     * @param $group
     * @param $fonticon
     * @return null|PHUIObjectItemListView
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    private function buildIssueList(array $issues, $group, $fonticon)
    {
        assert_instances_of($issues, PhabricatorSetupIssue::class);
        $list = new PHUIObjectItemListView();
        $list->setBig(true);
        $ignored_items = array();
        $items = 0;

        foreach ($issues as $issue) {
            if ($issue->getGroup() == $group) {
                $items++;
                $href = $this->getApplicationURI('/issue/' . $issue->getIssueKey() . '/');
                $item = (new PHUIObjectItemView())
                    ->setHeader($issue->getName())
                    ->setHref($href)
                    ->addAttribute($issue->getSummary());
                if (!$issue->getIsIgnored()) {
                    $icon = (new PHUIIconView())
                        ->setIcon($fonticon)
                        ->setBackground('bg-sky');
                    $item->setImageIcon($icon);
                    $list->addItem($item);
                } else {
                    $icon = (new PHUIIconView())
                        ->setIcon('fa-eye-slash')
                        ->setBackground('bg-grey');
                    $item->setDisabled(true);
                    $item->setImageIcon($icon);
                    $ignored_items[] = $item;
                }
            }
        }

        foreach ($ignored_items as $item) {
            $list->addItem($item);
        }

        if ($items == 0) {
            return null;
        } else {
            return $list;
        }
    }

}
