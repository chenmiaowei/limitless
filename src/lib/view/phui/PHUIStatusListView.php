<?php

namespace orangins\lib\view\phui;

use orangins\lib\view\AphrontTagView;

/**
 * Class PHUIStatusListView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIStatusListView extends AphrontTagView
{

    /**
     * @var
     */
    private $items;

    /**
     * @param PHUIStatusItemView $item
     * @return $this
     * @author 陈妙威
     */
    public function addItem(PHUIStatusItemView $item)
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function canAppendChild()
    {
        return false;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return 'table';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
//    require_celerity_resource('phui-status-list-view-css');

        $classes = array();
        $classes[] = 'phui-status-list-view';

        return array(
            'class' => implode(' ', $classes),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        return $this->items;
    }
}
