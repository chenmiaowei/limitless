<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use Exception;

/**
 * Class PHUITabGroupView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUITabGroupView extends AphrontTagView
{

    /**
     * @var PHUITabView[]
     */
    private $tabs = array();
    /**
     * @var
     */
    private $selectedTab;

    /**
     * @var
     */
    private $hideSingleTab;

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function canAppendChild()
    {
        return false;
    }

    /**
     * @param $hide_single_tab
     * @return $this
     * @author 陈妙威
     */
    public function setHideSingleTab($hide_single_tab)
    {
        $this->hideSingleTab = $hide_single_tab;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHideSingleTab()
    {
        return $this->hideSingleTab;
    }

    /**
     * @param PHUITabView $tab
     * @return $this
     * @author 陈妙威
     * @throws \PhutilInvalidStateException
     * @throws Exception
     */
    public function addTab(PHUITabView $tab)
    {
        $key = $tab->getKey();
        $tab->lockKey();

        if (isset($this->tabs[$key])) {
            throw new Exception(
                \Yii::t("app",
                    'Each tab in a tab group must have a unique key; attempting to add ' .
                    'a second tab with a duplicate key ("%s").',
                    $key));
        }

        $this->tabs[$key] = $tab;

        return $this;
    }

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     * @throws Exception
     */
    public function selectTab($key)
    {
        if (empty($this->tabs[$key])) {
            throw new Exception(
                \Yii::t("app",
                    'Unable to select tab ("{0}") which does not exist.',
                    [
                        $key
                    ]));
        }

        $this->selectedTab = $key;

        return $this;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getSelectedTabKey()
    {
        if (!$this->tabs) {
            return null;
        }

        if ($this->selectedTab !== null) {
            return $this->selectedTab;
        }

        return head($this->tabs)->getKey();
    }


    /**
     * @return array
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $tabs = (new PHUIListView())
            ->setType(PHUIListView::TYPE_TABS)
            ->addClass("mb-0")
            ->addClass("nav-tabs-highlight");
        $content = array();

        $selected_tab = $this->getSelectedTabKey();
        foreach ($this->tabs as $tab) {
            $item = $tab->newMenuItem();
            $item->setHref("#{$tab->getContentID()}");
            $item->setLinkTagAttributes([
               'data-toggle' => 'tab'
            ]);
            $tab_key = $tab->getKey();

            $style = "";
            if ($tab_key == $selected_tab) {
                $item->setSelected(true);
                $style = "active show";
            }

            $tabs->addMenuItem($item);

            $content[] = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => "tab-pane fade {$style}",
                    'id' => $tab->getContentID(),
                ),
                $tab);
        }

        if ($this->hideSingleTab && (count($this->tabs) == 1)) {
            $tabs = null;
        }

        return array(
            $tabs,
            JavelinHtml::phutil_tag("div", [
                "class" => "tab-content card card-body border border-top-0 rounded-top-0 shadow-0 mb-0",
            ], $content
            ),
        );
    }

}
