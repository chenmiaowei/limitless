<?php

namespace orangins\lib\view\phui;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\lib\view\layout\PHUICurtainView;

/**
 * Class PHUITwoColumnView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUITwoColumnView extends AphrontTagView
{

    /**
     * @var
     */
    private $mainColumn;
    /**
     * @var null
     */
    private $sideColumn = null;
    /**
     * @var
     */
    private $navigation;
    /**
     * @var
     */
    private $display;
    /**
     * @var
     */
    private $fixed;
    /**
     * @var PHUIHeaderView
     */
    private $header;
    /**
     * @var
     */
    private $subheader;
    /**
     * @var
     */
    private $footer;
    /**
     * @var
     */
    private $tabs;
    /**
     * @var array
     */
    private $propertySection = array();
    /**
     * @var
     */
    private $curtain;

    /**
     *
     */
    const DISPLAY_LEFT = 'phui-side-column-left';
    /**
     *
     */
    const DISPLAY_RIGHT = 'phui-side-column-right';

    /**
     * @param $main
     * @return $this
     * @author 陈妙威
     */
    public function setMainColumn($main)
    {
        $this->mainColumn = $main;
        return $this;
    }

    /**
     * @param $side
     * @return $this
     * @author 陈妙威
     */
    public function setSideColumn($side)
    {
        $this->sideColumn = $side;
        return $this;
    }

    /**
     * @param $nav
     * @return $this
     * @author 陈妙威
     */
    public function setNavigation($nav)
    {
        $nav->addClass('w-lg-100');
        $this->navigation = $nav;
        $this->display = self::DISPLAY_LEFT;
        return $this;
    }

    /**
     * @param PHUIHeaderView $header
     * @return $this
     * @author 陈妙威
     */
    public function setHeader(PHUIHeaderView $header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @param $subheader
     * @return $this
     * @author 陈妙威
     */
    public function setSubheader($subheader)
    {
        $this->subheader = $subheader;
        return $this;
    }

    /**
     * @param PHUIListView $tabs
     * @return $this
     * @author 陈妙威
     */
    public function setTabs(PHUIListView $tabs)
    {
        $tabs->setType(PHUIListView::TABBAR_LIST);
        $this->tabs = $tabs;
        return $this;
    }

    /**
     * @param $footer
     * @return $this
     * @author 陈妙威
     */
    public function setFooter($footer)
    {
        $this->footer = $footer;
        return $this;
    }

    /**
     * @param $title
     * @param $section
     * @return $this
     * @author 陈妙威
     */
    public function addPropertySection($title, $section)
    {
        $this->propertySection[] = array(
            'header' => $title,
            'content' => $section,
        );
        return $this;
    }

    /**
     * @param PHUICurtainView $curtain
     * @return $this
     * @author 陈妙威
     */
    public function setCurtain(PHUICurtainView $curtain)
    {
        $this->curtain = $curtain;
        return $this;
    }

    /**
     * @return PHUICurtainView
     * @author 陈妙威
     */
    public function getCurtain()
    {
        return $this->curtain;
    }

    /**
     * @param $fixed
     * @return $this
     * @author 陈妙威
     */
    public function setFixed($fixed)
    {
        $this->fixed = $fixed;
        return $this;
    }

    /**
     * @param $display
     * @return $this
     * @author 陈妙威
     */
    public function setDisplay($display)
    {
        $this->display = $display;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getDisplay()
    {
        if ($this->display) {
            return $this->display;
        } else {
            return self::DISPLAY_RIGHT;
        }
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $classes = array();
        $classes[] = 'phui-two-column-view';
        $classes[] = $this->getDisplay();

        if ($this->fixed) {
            $classes[] = 'phui-two-column-fixed';
        }

        if ($this->tabs) {
            $classes[] = 'with-tabs';
        }

        if ($this->subheader) {
            $classes[] = 'with-subheader';
        }

        if (!$this->header) {
            $classes[] = 'without-header';
        }

        return array(
            'class' => implode(' ', $classes),
        );
    }

    /**
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $main = $this->buildMainColumn();
        $side = $this->buildSideColumn();
        $footer = $this->buildFooter();

        $order = array($main, $side);

        $inner = JavelinHtml::phutil_tag_div('row', $order);
        $table = JavelinHtml::phutil_tag_div('phui-two-column-content', $inner);

        $header = null;
        if ($this->header) {
            $curtain = $this->getCurtain();
            if ($curtain) {
                $action_list = $curtain->getActionList();
                $this->header->setActionListID($action_list->getID());
            }

            $header = JavelinHtml::phutil_tag_div('phui-two-column-header mb-3', $this->header);
        }

        $tabs = null;
        if ($this->tabs) {
            $tabs = JavelinHtml::phutil_tag_div(
                'phui-two-column-tabs', $this->tabs);
        }

        $subheader = null;
        if ($this->subheader) {
            $subheader = JavelinHtml::phutil_tag_div(
                'phui-two-column-subheader', $this->subheader);
        }

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-two-column-container',
            ),
            array(
                $header,
                $tabs,
                $subheader,
                $table,
                $footer,
            ));
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function buildMainColumn()
    {

        $view = array();
        $sections = $this->propertySection;

        if ($sections) {
            foreach ($sections as $section) {
                $section_header = $section['header'];

                $section_content = $section['content'];
                if ($section_content === null) {
                    continue;
                }

                if ($section_header instanceof PHUIHeaderView) {
                    $header = $section_header;
                } else {
                    $header = (new PHUIHeaderView())
                        ->setHeader($section_header);
                }

                $view[] = (new PHUIObjectBoxView())
                    ->setHeader($header)
                    ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
                    ->appendChild($section_content);
            }
        }

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'col-md-9',
            ),
            array(
                $view,
                $this->mainColumn,
            ));
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function buildSideColumn()
    {

        $classes = array();
        $classes[] = 'col-md-3';
//        $navigation = null;
//        if ($this->navigation) {
//            $classes[] = 'side-has-nav';
//            $navigation = (new PHUIObjectBoxView())
//                ->addBodyClass(PHUI::PADDING_NONE)
//                ->appendChild($this->navigation);
//        }

        $navigation = $this->navigation;

        $curtain = $this->getCurtain();
        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => implode($classes, ' '),
            ),
            array(
                $navigation,
                $curtain,
                $this->sideColumn,
            ));
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function buildFooter()
    {

        $footer = $this->footer;

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-two-column-content phui-two-column-footer',
            ),
            array(
                $footer,
            ));

    }
}
