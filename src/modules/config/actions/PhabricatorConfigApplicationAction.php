<?php

namespace orangins\modules\config\actions;

use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use yii\helpers\Url;

/**
 * Class PhabricatorConfigApplicationAction
 * @package orangins\modules\config\actions
 * @author 陈妙威
 */
final class PhabricatorConfigApplicationAction
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
        $viewer = $request->getViewer();

        $nav = $this->buildSideNavView();
        $nav->selectFilter('application/');

        $groups = PhabricatorApplicationConfigOptions::loadAll();
        $apps_list = $this->buildConfigOptionsList($groups, 'apps');
        $apps_list = $this->buildConfigBoxView(\Yii::t("app",'Applications'), $apps_list);

        $title = \Yii::t("app",'Application Settings');
        $header = $this->buildHeaderView($title);

        $content = (new PHUITwoColumnView())
            ->setNavigation($nav)
            ->setFixed(true)
            ->setMainColumn($apps_list);

        $crumbs = $this->buildApplicationCrumbs()
            ->addTextCrumb($title)
            ->setBorder(true);

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($content);
    }

    /**
     * @param array $groups
     * @param $type
     * @return PHUIObjectItemListView
     * @author 陈妙威
     */
    private function buildConfigOptionsList(array $groups, $type)
    {
        assert_instances_of($groups, PhabricatorApplicationConfigOptions::class);

        $list = new PHUIObjectItemListView();
        $list->setBig(true);
        $groups = msort($groups, 'getName');
        foreach ($groups as $group) {
            if ($group->getGroup() == $type) {
                $icon = (new PHUIIconView())
                    ->addClass("mr-2")
                    ->setIcon($group->getIcon())
                    ->addClass('text-violet');
                $item = (new PHUIObjectItemView())
                    ->setHeader($group->getName())
                    ->setHref(Url::to(['/config/index/group', 'key' => $group->getKey()]))
                    ->addAttribute($group->getDescription())
                    ->setImageIcon($icon);
                $list->addItem($item);
            }
        }

        return $list;
    }

}
