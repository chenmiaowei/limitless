<?php

namespace orangins\lib\view\phui;

use orangins\lib\view\AphrontTagView;

/**
 * Class PHUIButtonBarView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIButtonBarView extends AphrontTagView
{

    /**
     * @var array
     */
    private $buttons = array();
    /**
     * @var
     */
    private $borderless;

    /**
     * @param $button
     * @return $this
     * @author 陈妙威
     */
    public function addButton($button)
    {
        $this->buttons[] = $button;
        return $this;
    }

    /**
     * @param $borderless
     * @return $this
     * @author 陈妙威
     */
    public function setBorderless($borderless)
    {
        $this->borderless = $borderless;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $classes = array();
        $classes[] = 'phui-button-bar';
        if ($this->borderless) {
            $classes[] = 'phui-button-bar-borderless';
        }
        return array('class' => implode(' ', $classes));
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return 'span';
    }

    /**
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
//    require_celerity_resource('phui-button-bar-css');

        $i = 1;
        $j = count($this->buttons);
        foreach ($this->buttons as $button) {
            // LeeLoo Dallas Multi-Pass
            if ($j > 1) {
                if ($i == 1) {
                    $button->addClass('phui-button-bar-first');
                } else if ($i == $j) {
                    $button->addClass('phui-button-bar-last');
                } else if ($j > 1) {
                    $button->addClass('phui-button-bar-middle');
                }
            }
            $this->appendChild($button);
            $i++;
        }

        return $this->renderChildren();
    }
}
