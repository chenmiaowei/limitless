<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;

/**
 * Class PHUILeftRightView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUILeftRightView extends AphrontTagView
{

    /**
     * @var
     */
    private $left;
    /**
     * @var
     */
    private $right;
    /**
     * @var
     */
    private $verticalAlign;

    /**
     *
     */
    const ALIGN_TOP = 'top';
    /**
     *
     */
    const ALIGN_MIDDLE = 'middle';
    /**
     *
     */
    const ALIGN_BOTTOM = 'bottom';

    /**
     * @param $left
     * @return $this
     * @author 陈妙威
     */
    public function setLeft($left)
    {
        $this->left = $left;
        return $this;
    }

    /**
     * @param $right
     * @return $this
     * @author 陈妙威
     */
    public function setRight($right)
    {
        $this->right = $right;
        return $this;
    }

    /**
     * @param $align
     * @return $this
     * @author 陈妙威
     */
    public function setVerticalAlign($align)
    {
        $this->verticalAlign = $align;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
//    require_celerity_resource('phui-left-right-css');

        $classes = array();
        $classes[] = 'phui-left-right-view';

        if ($this->verticalAlign) {
            $classes[] = 'phui-lr-view-' . $this->verticalAlign;
        }

        return array('class' => implode(' ', $classes));
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
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $left = JavelinHtml::phutil_tag_div('phui-left-view', $this->left);
        $right = JavelinHtml::phutil_tag_div('phui-right-view', $this->right);

        return JavelinHtml::phutil_tag_div('phui-lr-container', array($left, $right));
    }
}
