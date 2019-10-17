<?php

namespace orangins\lib\view\phui;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\lib\view\layout\AphrontMultiColumnView;

/**
 * Class PHUIWorkboardView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIWorkboardView extends AphrontTagView
{

    /**
     * @var array
     */
    private $panels = array();
    /**
     * @var array
     */
    private $actions = array();

    /**
     * @param PHUIWorkpanelView $panel
     * @return $this
     * @author 陈妙威
     */
    public function addPanel(PHUIWorkpanelView $panel)
    {
        $this->panels[] = $panel;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        return array(
            'class' => 'phui-workboard-view',
        );
    }

    /**
     * @return array|string
     * @throws Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
//        require_celerity_resource('phui-workboard-view-css');

        $view = new AphrontMultiColumnView();
        $view->setGutter(AphrontMultiColumnView::GUTTER_MEDIUM);
        foreach ($this->panels as $panel) {
            $view->addColumn($panel);
        }

        $board = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-workboard-view-shadow',
                'sigil' => 'workboard-shadow lock-scroll-y-while-dragging',
            ),
            $view);

        return $board;
    }
}
