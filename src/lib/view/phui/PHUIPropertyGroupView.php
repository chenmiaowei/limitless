<?php

namespace orangins\lib\view\phui;

use orangins\lib\view\AphrontTagView;

/**
 * Class PHUIPropertyGroupView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIPropertyGroupView extends AphrontTagView
{

    /**
     * @var
     */
    private $items;

    /**
     * @param PHUIPropertyListView $item
     * @author 陈妙威
     */
    public function addPropertyList(PHUIPropertyListView $item)
    {
        $this->items[] = $item;
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
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        return array(
            'class' => 'phui-property-list-view',
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
