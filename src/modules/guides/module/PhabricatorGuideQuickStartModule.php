<?php

namespace orangins\modules\guides\module;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\request\AphrontRequest;
use orangins\modules\guides\view\PhabricatorGuideItemView;
use orangins\modules\guides\view\PhabricatorGuideListView;
use yii\helpers\Url;

/**
 * Class PhabricatorGuideQuickStartModule
 * @package orangins\modules\guides\module
 * @author 陈妙威
 */
final class PhabricatorGuideQuickStartModule extends PhabricatorGuideModule
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleKey()
    {
        return 'quickstart';
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getModuleName()
    {
        return \Yii::t("app",'Quick Start');
    }

    /**
     * @return int|mixed
     * @author 陈妙威
     */
    public function getModulePosition()
    {
        return 30;
    }

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    public function getIsModuleEnabled()
    {
        return true;
    }

    /**
     * @param AphrontRequest $request
     * @return array|mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    public function renderModuleStatus(AphrontRequest $request)
    {
        $viewer = $request->getViewer();
        $instance = PhabricatorEnv::getEnvConfig('cluster.instance');

        $guide_items = new PhabricatorGuideListView();

        $title = \Yii::t("app",'Create a Repository');
        $repository_check = (new PhabricatorRepositoryQuery())
            ->setViewer($viewer)
            ->execute();
        $href = PhabricatorEnv::getURI('/diffusion/');
        if ($repository_check) {
            $icon = 'fa-check';
            $icon_bg = 'bg-green';
            $description = \Yii::t("app",
                "You've created at least one repository.");
        } else {
            $icon = 'fa-code';
            $icon_bg = 'bg-sky';
            $description =
                \Yii::t("app",'If you are here for code review, let\'s set up your first ' .
                    'repository.');
        }

        $item = (new PhabricatorGuideItemView())
            ->setTitle($title)
            ->setHref($href)
            ->setIcon($icon)
            ->setIconBackground($icon_bg)
            ->setDescription($description);
        $guide_items->addItem($item);


        $title = \Yii::t("app",'Create a Project');
        $project_check = (new PhabricatorProjectQuery())
            ->setViewer($viewer)
            ->execute();
        $href = PhabricatorEnv::getURI('/project/');
        if ($project_check) {
            $icon = 'fa-check';
            $icon_bg = 'bg-green';
            $description = \Yii::t("app",
                "You've created at least one project.");
        } else {
            $icon = 'fa-briefcase';
            $icon_bg = 'bg-sky';
            $description =
                \Yii::t("app",'Project tags define everything. Create them for teams, tags, ' .
                    'or actual projects.');
        }

        $item = (new PhabricatorGuideItemView())
            ->setTitle($title)
            ->setHref($href)
            ->setIcon($icon)
            ->setIconBackground($icon_bg)
            ->setDescription($description);
        $guide_items->addItem($item);


        $title = \Yii::t("app",'Create a Task');
        $task_check = (new ManiphestTaskQuery())
            ->setViewer($viewer)
            ->execute();
        $href = PhabricatorEnv::getURI('/maniphest/');
        if ($task_check) {
            $icon = 'fa-check';
            $icon_bg = 'bg-green';
            $description = \Yii::t("app",
                "You've created at least one task.");
        } else {
            $icon = 'fa-anchor';
            $icon_bg = 'bg-sky';
            $description =
                \Yii::t("app",'Create some work for the interns in Maniphest.');
        }

        $item = (new PhabricatorGuideItemView())
            ->setTitle($title)
            ->setHref($href)
            ->setIcon($icon)
            ->setIconBackground($icon_bg)
            ->setDescription($description);
        $guide_items->addItem($item);

        $title = \Yii::t("app",'Build a Dashboard');
        $have_dashboard = (bool)PhabricatorDashboardInstall::getDashboard(
            $viewer,
            PhabricatorHomeApplication::DASHBOARD_DEFAULT,
            'PhabricatorHomeApplication');
        $href = PhabricatorEnv::getURI('/dashboard/');
        if ($have_dashboard) {
            $icon = 'fa-check';
            $icon_bg = 'bg-green';
            $description = \Yii::t("app",
                "You've created at least one dashboard.");
        } else {
            $icon = 'fa-dashboard';
            $icon_bg = 'bg-sky';
            $description =
                \Yii::t("app",'Customize the default homepage layout and items.');
        }

        $item = (new PhabricatorGuideItemView())
            ->setTitle($title)
            ->setHref($href)
            ->setIcon($icon)
            ->setIconBackground($icon_bg)
            ->setDescription($description);
        $guide_items->addItem($item);


        $title = \Yii::t("app",'Personalize your Install');
        $wordmark = PhabricatorEnv::getEnvConfig('ui.logo');

        $href = Url::to(['/config/index/edit', 'key' => 'ui.logo']);
        if ($wordmark) {
            $icon = 'fa-check';
            $icon_bg = 'bg-green';
            $description = \Yii::t("app",
                'It looks amazing, good work. Home Sweet Home.');
        } else {
            $icon = 'fa-home';
            $icon_bg = 'bg-sky';
            $description =
                \Yii::t("app",'Change the name and add your company logo, just to give it a ' .
                    'little extra polish.');
        }

        $item = (new PhabricatorGuideItemView())
            ->setTitle($title)
            ->setHref($href)
            ->setIcon($icon)
            ->setIconBackground($icon_bg)
            ->setDescription($description);
        $guide_items->addItem($item);

        $title = \Yii::t("app",'Explore Applications');
        $href = PhabricatorEnv::getURI('/applications/');
        $icon = 'fa-globe';
        $icon_bg = 'bg-sky';
        $description =
            \Yii::t("app",'See all the applications included in Phabricator.');

        $item = (new PhabricatorGuideItemView())
            ->setTitle($title)
            ->setHref($href)
            ->setIcon($icon)
            ->setIconBackground($icon_bg)
            ->setDescription($description);
        $guide_items->addItem($item);

        if (!$instance) {
            $title = \Yii::t("app",'Invite Collaborators');
            $people_check = PhabricatorUser::find()
                ->setViewer($viewer)
                ->execute();
            $people = count($people_check);
            $href = PhabricatorEnv::getURI('/people/invite/send/');
            if ($people > 1) {
                $icon = 'fa-check';
                $icon_bg = 'bg-green';
                $description = \Yii::t("app",
                    'Your invitations have been accepted. You will not be alone on ' .
                    'this journey.');
            } else {
                $icon = 'fa-group';
                $icon_bg = 'bg-sky';
                $description =
                    \Yii::t("app",'Invite the rest of your team to get started on Phabricator.');
            }

            $item = (new PhabricatorGuideItemView())
                ->setTitle($title)
                ->setHref($href)
                ->setIcon($icon)
                ->setIconBackground($icon_bg)
                ->setDescription($description);
            $guide_items->addItem($item);
        }

        $intro = \Yii::t("app",
            'If you\'re new to Phabricator, these optional steps can help you learn ' .
            'the basics. Conceptually, Phabricator is structured as a graph, and ' .
            'repositories, tasks, and projects are all independent from each other. ' .
            'Feel free to set up Phabricator for how you work best, and explore ' .
            'these features at your own pace.');

        $intro = new PHUIRemarkupView($viewer, $intro);
        $intro = (new PHUIDocumentView())
            ->appendChild($intro);

        return array($intro, $guide_items);

    }

}
