<?php

namespace orangins\lib\view\phui;

use orangins\lib\view\AphrontTagView;

/**
 * Class PHUIBoxView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIBoxView extends AphrontTagView
{

    /**
     * @var array
     */
    private $margin = array();
    /**
     * @var array
     */
    private $padding = array();
    /**
     * @var bool
     */
    private $border = false;
    /**
     * @var
     */
    private $color;

    /**
     *
     */
    const BLUE = 'phui-box-blue';
    /**
     *
     */
    const BACKGROUND_GREY = 'phui-box-grey';

    /**
     * @param $margin
     * @return $this
     * @author 陈妙威
     */
    public function addMargin($margin)
    {
        $this->margin[] = $margin;
        return $this;
    }

    /**
     * @param $padding
     * @return $this
     * @author 陈妙威
     */
    public function addPadding($padding)
    {
        $this->padding[] = $padding;
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
     * @param $color
     * @return $this
     * @author 陈妙威
     */
    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $outer_classes = array();
        $outer_classes[] = 'phui-box';

        if ($this->border) {
            $outer_classes[] = 'phui-box-border';
        }

        foreach ($this->margin as $margin) {
            $outer_classes[] = $margin;
        }

        foreach ($this->padding as $padding) {
            $outer_classes[] = $padding;
        }

        if ($this->color) {
            $outer_classes[] = $this->color;
        }

        return array('class' => $outer_classes);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return 'div';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        return $this->renderChildren();
    }
}
