<?php

namespace orangins\lib\view\layout;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\view\phui\PHUITagView;
use PhutilInvalidStateException;
use orangins\lib\helpers\JavelinHtml;
use PhutilURI;
use orangins\lib\view\phui\PHUICrumbsView;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\lib\view\phui\PHUIListView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\AphrontView;

/**
 * Provides a navigation sidebar. For example:
 *
 *    $nav = new AphrontSideNavFilterView();
 *    $nav
 *      ->setBaseURI($some_uri)
 *      ->addLabel('Cats')
 *      ->addFilter('meow', 'Meow')
 *      ->addFilter('purr', 'Purr')
 *      ->addLabel('Dogs')
 *      ->addFilter('woof', 'Woof')
 *      ->addFilter('bark', 'Bark');
 *    $valid_filter = $nav->selectFilter($user_selection, $default = 'meow');
 *
 */
final class AphrontSideNavFilterView extends AphrontView
{

    /**
     * @var array
     */
    private $items = array();
    /**
     * @var
     */
    private $baseURI;
    /**
     * @var bool
     */
    private $selectedFilter = false;
    /**
     * @var
     */
    private $flexible;
    /**
     * @var bool
     */
    private $collapsed = false;
    /**
     * @var
     */
    private $active;
    /**
     * @var PHUIListView
     */
    private $menu;
    /**
     * @var
     */
    private $crumbs;
    /**
     * @var array
     */
    private $classes = array();
    /**
     * @var
     */
    private $menuID;
    /**
     * @var
     */
    private $mainID;
    /**
     * @var
     */
    private $isProfileMenu;

    /**
     * @var PHUIPageHeaderView
     */
    private $contentHeader;
    /**
     * @var array
     */
    private $footer = array();
    /**
     * @var
     */
    private $width;

    /**
     * @return PHUIPageHeaderView
     */
    public function getContentHeader()
    {
        return $this->contentHeader;
    }

    /**
     * @param PHUIPageHeaderView $contentHeader
     * @return self
     */
    public function setContentHeader($contentHeader)
    {
        $this->contentHeader = $contentHeader;
        return $this;
    }

    /**
     * @param $menu_id
     * @return $this
     * @author 陈妙威
     */
    public function setMenuID($menu_id)
    {
        $this->menuID = $menu_id;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMenuID()
    {
        return $this->menuID;
    }

    /**
     * AphrontSideNavFilterView constructor.
     */
    public function __construct()
    {
        $this->menu = new PHUIListView();
    }

    /**
     * @param $class
     * @return $this
     * @author 陈妙威
     */
    public function addClass($class)
    {
        $this->classes[] = $class;
        return $this;
    }

    /**
     * @param PHUICrumbsView $crumbs
     * @return $this
     * @author 陈妙威
     */
    public function setCrumbs(PHUICrumbsView $crumbs)
    {
        $this->crumbs = $crumbs;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCrumbs()
    {
        return $this->crumbs;
    }

    /**
     * @param $is_profile
     * @return $this
     * @author 陈妙威
     */
    public function setIsProfileMenu($is_profile)
    {
        $this->isProfileMenu = $is_profile;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsProfileMenu()
    {
        return $this->isProfileMenu;
    }

    /**
     * @param $active
     * @return $this
     * @author 陈妙威
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @param $flexible
     * @return $this
     * @author 陈妙威
     */
    public function setFlexible($flexible)
    {
        $this->flexible = $flexible;
        return $this;
    }

    /**
     * @param $collapsed
     * @return $this
     * @author 陈妙威
     */
    public function setCollapsed($collapsed)
    {
        $this->collapsed = $collapsed;
        return $this;
    }

    /**
     * @param $width
     * @return $this
     * @author 陈妙威
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * @return PHUIListView
     * @author 陈妙威
     */
    public function getMenuView()
    {
        return $this->menu;
    }

    /**
     * @param PHUIListItemView $item
     * @return $this
     * @throws \Exception
     * @author 陈妙威
     */
    public function addMenuItem(PHUIListItemView $item)
    {
        $this->menu->addMenuItem($item);
        return $this;
    }

    /**
     * @return PHUIListView
     * @author 陈妙威
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     * @param $key
     * @param $name
     * @param null $uri
     * @param null $icon
     * @return AphrontSideNavFilterView
     * @author 陈妙威
     * @throws \Exception
     */
    public function addFilter($key, $name, $uri = null, $icon = null)
    {
        return $this->addThing(
            $key, $name, $uri, PHUIListItemView::TYPE_LINK, $icon);
    }

    /**
     * @param $key
     * @param $name
     * @param null $uri
     * @return AphrontSideNavFilterView
     * @author 陈妙威
     * @throws \Exception
     */
    public function addButton($key, $name, $uri = null)
    {
        return $this->addThing(
            $key, $name, $uri, PHUIListItemView::TYPE_BUTTON);
    }

    /**
     * @param $key
     * @param $name
     * @param $uri
     * @param $type
     * @param null $icon
     * @return AphrontSideNavFilterView
     * @throws \Exception
     * @author 陈妙威
     */
    private function addThing($key, $name, $uri, $type, $icon = null)
    {
        $item = (new PHUIListItemView())
            ->setName($name)
            ->setType($type);

        if (strlen($icon)) {
            $item->setIcon($icon);
        }


        if (strlen($key)) {
            $item->setKey($key);
        }

//        if ($uri) {
        $item->setHref($uri);
//        } else {
//            $href = clone $this->baseURI;
//            $href->setPath(rtrim($href->getPath() . $key, '/') . '/');
//            $href = (string)$href;
//
//            $item->setHref($href);
//        }

        return $this->addMenuItem($item);
    }

    /**
     * @param $block
     * @return $this
     * @throws \Exception
     * @author 陈妙威
     */
    public function addCustomBlock($block)
    {
        $this->menu->addMenuItem(
            (new PHUIListItemView())
                ->setType(PHUIListItemView::TYPE_CUSTOM)
                ->appendChild($block));
        return $this;
    }

    /**
     * @param $name
     * @return AphrontSideNavFilterView
     * @author 陈妙威
     * @throws \Exception
     */
    public function addLabel($name)
    {
        return $this->addMenuItem(
            (new PHUIListItemView())
                ->setType(PHUIListItemView::TYPE_LABEL)
                ->setName($name));
    }

    /**
     * @param PhutilURI $uri
     * @return $this
     * @author 陈妙威
     */
    public function setBaseURI(PhutilURI $uri)
    {
        $this->baseURI = $uri;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBaseURI()
    {
        return $this->baseURI;
    }

    /**
     * @param $key
     * @param null $default
     * @return bool|null
     * @author 陈妙威
     */
    public function selectFilter($key, $default = null)
    {
        $this->selectedFilter = $default;
        if ($this->menu->getItem($key) && strlen($key)) {
            $this->selectedFilter = $key;
        }
        return $this->selectedFilter;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getSelectedFilter()
    {
        return $this->selectedFilter;
    }

    /**
     * @param $footer
     * @return $this
     * @author 陈妙威
     */
    public function appendFooter($footer)
    {
        $this->footer[] = $footer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMainID()
    {
        if (!$this->mainID) {
            $this->mainID = JavelinHtml::generateUniqueNodeId();
        }
        return $this->mainID;
    }

    /**
     * @return string
     * @throws PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        $var = PHUITagView::getColorCode(PhabricatorEnv::getEnvConfig("ui.widget-color"));
        \Yii::$app->getView()->registerCss(<<<STR
.sidebar-light .nav-sidebar>.nav-item-open>.nav-link:not(.disabled), .sidebar-light .nav-sidebar>.nav-item>.nav-link.active {
    background-color: {$var};
    color: #fff;
}
.sidebar-light .nav-sidebar>.nav-item>.nav-link[class*=bg-]:hover {
    background-color: {$var};
    color: #fff;
}
.sidebar-dark .nav-sidebar>.nav-item-open>.nav-link:not(.disabled), .sidebar-dark .nav-sidebar>.nav-item>.nav-link.active, .sidebar-light .card[class*=bg-]:not(.bg-light):not(.bg-white):not(.bg-transparent) .nav-sidebar>.nav-item-open>.nav-link:not(.disabled), .sidebar-light .card[class*=bg-]:not(.bg-light):not(.bg-white):not(.bg-transparent) .nav-sidebar>.nav-item>.nav-link.active {
    background-color: {$var};
    color: #fff;
}
STR
        );

        if ($this->menu->getItems()) {
            if (!$this->baseURI) {
                throw new PhutilInvalidStateException('setBaseURI');
            }
            if ($this->selectedFilter === false) {
                throw new PhutilInvalidStateException('selectFilter');
            }
        }

        if ($this->selectedFilter !== null) {

            $selected_item = $this->menu->getItem($this->selectedFilter);
            if ($selected_item) {
                if (is_array($selected_item->getSubListItems()) && count($selected_item->getSubListItems())) {
                    $selected_item->addClass('nav-item-submenu nav-item-open');
                    $selected_item->addLinkClass("bg-" . PhabricatorEnv::getEnvConfig("ui.widget-color"));
                    foreach ($selected_item->getSubListItems() as $subListItem) {
                        if ($subListItem->getKey() === $this->selectedFilter) {
                            $subListItem->setSelected(true);
                        }
                    }
                } else {
                    $selected_item->addClass('phui-list-item-selected');
                    $selected_item->addLinkClass("active");
                    $selected_item->addLinkClass("bg-" . PhabricatorEnv::getEnvConfig("ui.widget-color"));
                }
            }
        }
        return $this->renderFlexNav();
    }

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderFlexNav()
    {
        $nav_classes = array();

        $colorDeep = PhabricatorEnv::getEnvConfig("ui.widget-dark") ? "sidebar-dark" : "sidebar-light";

        $nav_classes[] = "sidebar {$colorDeep} sidebar-main sidebar-expand-md";

        $nav_id = null;
        $drag_id = null;
        $content_id = JavelinHtml::generateUniqueNodeId();
        $local_id = null;
        $background_id = null;
        $local_menu = null;
        $main_id = $this->getMainID();

        $width = $this->width;
        if ($width) {
            $width = min($width, 600);
            $width = max($width, 150);
        } else {
            $width = null;
        }

        if ($width && !$this->collapsed) {
            $width_drag_style = 'left: ' . $width . 'px';
            $width_panel_style = 'width: ' . $width . 'px';
            $width_margin_style = 'margin-left: ' . ($width + 7) . 'px';
        } else {
            $width_drag_style = null;
            $width_panel_style = null;
            $width_margin_style = null;
        }

        if ($this->flexible) {
            $drag_id = JavelinHtml::generateUniqueNodeId();
            $flex_bar = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phabricator-nav-drag',
                    'id' => $drag_id,
                    'style' => $width_drag_style,
                ),
                '');
        } else {
            $flex_bar = null;
        }

        $nav_classes = array_merge($nav_classes, $this->classes);


        $nav_menu = null;
        if ($this->menu->getItems()) {
            $local_id = JavelinHtml::generateUniqueNodeId();
            $background_id = JavelinHtml::generateUniqueNodeId();

            if (!$this->collapsed) {
                $nav_classes[] = 'has-local-nav';
            }


            $local_menu = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => implode(" ", $nav_classes),
                    'id' => $local_id,
                    'style' => $width_panel_style,
                ),
                $this->menu->setID($this->getMenuID()));
        }

//        if ($this->flexible) {
//            if (!$this->collapsed) {
//                $nav_classes[] = 'has-drag-nav';
//            } else {
//                $nav_classes[] = 'has-closed-nav';
//            }
//
//            JavelinHtml::initBehavior(
//                'phabricator-nav',
//                array(
//                    'mainID' => $main_id,
//                    'localID' => $local_id,
//                    'dragID' => $drag_id,
//                    'contentID' => $content_id,
//                    'backgroundID' => $background_id,
//                    'collapsed' => $this->collapsed,
//                    'width' => $width,
//                ));
//
//            if ($this->active) {
//                JavelinHtml::initBehavior(
//                    'phabricator-active-nav',
//                    array(
//                        'localID' => $local_id,
//                    ));
//            }
//        }


//        $menu = JavelinHtml::phutil_tag(
//            'div',
//            array(
//                'class' => implode(' ', $nav_classes),
//                'id' => $main_id,
//            ),
//            array(
//                $local_menu,
//                $flex_bar,
//                JavelinHtml::phutil_tag(
//                    'div',
//                    array(
//                        'class' => 'phabricator-nav-content plb',
//                        'id' => $content_id,
//                        'style' => $width_margin_style,
//                    ),
//                    array(
//                        $crumbs,
//                        $this->renderChildren(),
//                        $this->footer,
//                    )),
//            ));

//        $classes = array();
//        $classes[] = 'phui-navigation-shell';
//
//        if ($this->getIsProfileMenu()) {
//            $classes[] = 'phui-profile-menu phui-basic-nav';
//        } else {
//            $classes[] = 'phui-basic-nav';
//        }

//        $shell = JavelinHtml::phutil_tag(
//            'div',
//            array(
//                'class' => implode(' ', $classes),
//            ),
//            array(
//                $menu,
//            ));


        $header = null;
        if ($this->getContentHeader()) {
            $this->getContentHeader()->setCrumbs($this->getCrumbs());
            $header = $this->getContentHeader();
        } else {
            $header = $this->getCrumbs();
        }
        return array(array(
            $local_menu,
            $flex_bar,
            JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'content-wrapper',
                    'id' => $content_id,
                    'style' => $width_margin_style,
                ),
                array(
                    $header,
                    JavelinHtml::phutil_tag_div('content', $this->renderChildren()),
                    $this->footer,
                )),
        ));
    }

}
