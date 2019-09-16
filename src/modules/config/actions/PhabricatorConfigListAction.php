<?php

namespace orangins\modules\config\actions;

use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use orangins\modules\settings\panelgroup\PhabricatorSettingsPanelGroup;
use yii\helpers\Url;

/**
 * Class PhabricatorConfigListAction
 * @package orangins\modules\config\actions
 * @author 陈妙威
 */
final class PhabricatorConfigListAction extends PhabricatorConfigAction
{

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \yii\base\Exception
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $nav = $this->buildSideNavView();
        $nav->selectFilter('/');

        $groups = PhabricatorApplicationConfigOptions::loadAll();
        $core_list = $this->buildConfigOptionsList($groups, 'core');
        $core_list = $this->buildConfigBoxView(\Yii::t("app",'Core'), $core_list);

        $title = \Yii::t("app",'Core Settings');
        $header = $this->buildHeaderView($title);

        $crumbs = $this->buildApplicationCrumbs()
            ->addTextCrumb($title)
            ->setBorder(true);

        $content = (new PHUITwoColumnView())
            ->setNavigation($nav)
            ->setFixed(true)
            ->setMainColumn($core_list);

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($content);
    }

    /**
     * @param PhabricatorSettingsPanelGroup[] $groups
     * @param $type
     * @return PHUIObjectItemListView
     * @author 陈妙威
     */
    private function buildConfigOptionsList(array $groups, $type)
    {
        assert_instances_of($groups, PhabricatorApplicationConfigOptions::class);
        $list = new PHUIObjectItemListView();
        $list->setBig(true);

        /** @var PhabricatorApplicationConfigOptions[] $groups */
        $groups = msort($groups, 'getName');
        foreach ($groups as $group) {
            if ($group->getGroup() == $type) {
                $icon = (new PHUIIconView())
                    ->addClass("mr-2")
                    ->addClass("text-blue")
                    ->setIcon($group->getIcon());
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
