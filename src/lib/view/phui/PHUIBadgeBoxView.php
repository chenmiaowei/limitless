<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;

/**
 * Class PHUIBadgeBoxView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIBadgeBoxView extends AphrontTagView
{

    /**
     * @var array
     */
    private $items = array();
    /**
     * @var
     */
    private $collapsed;

    /**
     * @param $item
     * @return $this
     * @author 陈妙威
     */
    public function addItem($item)
    {
        $this->items[] = $item;
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
     * @param $items
     * @return $this
     * @author 陈妙威
     */
    public function addItems($items)
    {
        foreach ($items as $item) {
            $this->items[] = $item;
        }
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return 'ul';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
//        require_celerity_resource('phui-badge-view-css');

        $classes = array();
        $classes[] = 'phui-badge-flex-view';
        $classes[] = 'grouped';
        if ($this->collapsed) {
            $classes[] = 'flex-view-collapsed';
        }

        return array(
            'class' => implode(' ', $classes),
        );
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $items = array();
        foreach ($this->items as $item) {
            $items[] = JavelinHtml::phutil_tag(
                'li',
                array(
                    'class' => 'phui-badge-flex-item',
                ),
                $item);
        }
        return $items;

    }
}
