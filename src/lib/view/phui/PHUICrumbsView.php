<?php

namespace orangins\lib\view\phui;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\view\AphrontView;

/**
 * Class PHUICrumbsView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUICrumbsView extends AphrontView
{

    /**
     * @var array
     */
    private $crumbs = array();
    /**
     * @var PHUIListItemView[]
     */
    private $actions = array();
    /**
     * @var
     */
    private $border;

    /**
     * @var bool
     */
    private $boxShadow = true;

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function canAppendChild()
    {
        return false;
    }


    /**
     * Convenience method for adding a simple crumb with just text, or text and
     * a link.
     *
     * @param $text
     * @param null $href
     * @return PHUICrumbsView
     */
    public function addTextCrumb($text, $href = null)
    {
        return $this->addCrumb(
            (new PHUICrumbView())
                ->setName($text)
                ->setHref($href));
    }

    /**
     * @param PHUICrumbView $crumb
     * @return $this
     * @author 陈妙威
     */
    public function addCrumb(PHUICrumbView $crumb)
    {
        $this->crumbs[] = $crumb;
        return $this;
    }

    /**
     * @param PHUIListItemView $action
     * @return $this
     * @author 陈妙威
     */
    public function addAction(PHUIListItemView $action)
    {
        $this->actions[] = $action;
        return $this;
    }

    /**
     * @param $border
     * @return $this
     * @author 陈妙威
     */
    public function setBorder($border)
    {
        $this->border = $border;
        return $this;
    }

    /**
     * @param $boxShadow
     * @return $this
     * @author 陈妙威
     */
    public function setBoxShadow($boxShadow)
    {
        $this->boxShadow = $boxShadow;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public function render()
    {
//        require_celerity_resource('phui-crumbs-view-css');
        $action_view = null;
        if ($this->actions) {
            /** @var PHUIListItemView[] $actions */
            $actions = array();
            foreach ($this->actions as $action) {
                if ($action->getType() == PHUIListItemView::TYPE_DIVIDER) {
                    $actions[] = JavelinHtml::tag('span', '', array(
                        'class' => 'phui-crumb-action-divider',
                    ));
                    continue;
                }

                $icon = null;
                if ($action->getIcon()) {
                    $icon_name = $action->getIcon();
                    if ($action->getDisabled()) {
                        $icon_name .= ' lightgreytext';
                    }

                    $icon = (new PHUIIconView())
                        ->addClass("mr-2")
                        ->setIcon($icon_name);

                }

                $action_classes = $action->getClasses();
                $action_classes[] = 'breadcrumb-elements-item';

                $name = null;
                if ($action->getName()) {
                    $name = $action->getName();
                } else {
                    $action_classes[] = 'phui-crumbs-action-icon';
                }

                $action_sigils = $action->getSigils();
                if ($action->getWorkflow()) {
                    $action_sigils[] = 'workflow';
                }

                if ($action->getDisabled()) {
                    $action_classes[] = 'phui-crumbs-action-disabled';
                }

                $actions[] = JavelinHtml::tag('a', array(
                    $icon,
                    $name,
                ), array(
                    'id' => $action->getID(),
                    'href' => $action->getHref(),
                    'class' => implode(' ', $action_classes),
                    'sigil' => implode(' ', $action_sigils),
                    'style' => $action->getStyle(),
                    'meta' => $action->getMetadata(),
                ));
            }

            $action_view = JavelinHtml::tag('div', JavelinHtml::tag("div", $actions, [
                "class" => "breadcrumb justify-content-center"
            ]), array(
                'class' => 'header-elements d-none',
            ));
        }

        if ($this->crumbs) {
            OranginsUtil::last($this->crumbs)->setIsLastCrumb(true);
        }

        $classes = array();
        $classes[] = ' breadcrumb-line-light header-elements-md-inline';
        if ($this->border) {
            $classes[] = 'phui-crumbs-border';
        }
        if ($this->boxShadow) {
            $classes[] = 'breadcrumb-line';
        }

        return JavelinHtml::tag('div', array(
            JavelinHtml::phutil_tag_div('d-flex', JavelinHtml::phutil_tag_div('breadcrumb', $this->crumbs)),
            $action_view,
        ), array(
            'class' => implode(' ', $classes),
        ));
    }

}
