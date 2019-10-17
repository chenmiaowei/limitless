<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;

/**
 * Class PHUIImageMaskView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIImageMaskView extends AphrontTagView
{

    /**
     * @var
     */
    private $image;
    /**
     * @var
     */
    private $withMask;

    /**
     * @var
     */
    private $displayWidth;
    /**
     * @var
     */
    private $displayHeight;

    /**
     * @var
     */
    private $centerX;
    /**
     * @var
     */
    private $centerY;
    /**
     * @var
     */
    private $maskH;
    /**
     * @var
     */
    private $maskW;

    /**
     * @param $image
     * @return $this
     * @author 陈妙威
     */
    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @param $width
     * @return $this
     * @author 陈妙威
     */
    public function setDisplayWidth($width)
    {
        $this->displayWidth = $width;
        return $this;
    }

    /**
     * @param $height
     * @return $this
     * @author 陈妙威
     */
    public function setDisplayHeight($height)
    {
        $this->displayHeight = $height;
        return $this;
    }

    /**
     * @param $x
     * @param $y
     * @param $h
     * @param $w
     * @return $this
     * @author 陈妙威
     */
    public function centerViewOnPoint($x, $y, $h, $w)
    {
        $this->centerX = $x;
        $this->centerY = $y;
        $this->maskH = $h;
        $this->maskW = $w;
        return $this;
    }

    /**
     * @param $mask
     * @return $this
     * @author 陈妙威
     */
    public function withMask($mask)
    {
        $this->withMask = $mask;
        return $this;
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
    protected function getTagAttributes()
    {
//    require_celerity_resource('phui-image-mask-css');

        $classes = array();
        $classes[] = 'phui-image-mask';

        $styles = array();
        $styles[] = 'height: ' . $this->displayHeight . 'px;';
        $styles[] = 'width: ' . $this->displayWidth . 'px;';

        return array(
            'class' => implode(' ', $classes),
            'styles' => implode(' ', $styles),
        );

    }

    /**
     * @return array|string
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {

        /* Center it in the middle of the selected area */
        $center_x = round($this->centerX + ($this->maskW / 2));
        $center_y = round($this->centerY + ($this->maskH / 2));
        $center_x = round($center_x - ($this->displayWidth / 2));
        $center_y = round($center_y - ($this->displayHeight / 2));

        $center_x = -$center_x;
        $center_y = -$center_y;

        $classes = array();
        $classes[] = 'phui-image-mask-image';

        $styles = array();
        $styles[] = 'height: ' . $this->displayHeight . 'px;';
        $styles[] = 'width: ' . $this->displayWidth . 'px;';
        $styles[] = 'background-image: url(' . $this->image . ');';
        $styles[] = 'background-position: ' . $center_x . 'px ' . $center_y . 'px;';

        $mask = null;
        if ($this->withMask) {
            /*  The mask is a 300px border around a transparent box.
                so we do the math here to position the box correctly. */
            $border = 300;
            $left = round((($this->displayWidth - $this->maskW) / 2) - $border);
            $top = round((($this->displayHeight - $this->maskH) / 2) - $border);

            $mstyles = array();
            $mstyles[] = 'left: ' . $left . 'px;';
            $mstyles[] = 'top: ' . $top . 'px;';
            $mstyles[] = 'height: ' . $this->maskH . 'px;';
            $mstyles[] = 'width: ' . $this->maskW . 'px;';

            $mask = JavelinHtml::phutil_tag(
                'span',
                array(
                    'class' => 'phui-image-mask-mask',
                    'style' => implode(' ', $mstyles),
                ),
                null);
        }

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => implode(' ', $classes),
                'style' => implode(' ', $styles),
            ),
            $mask);
    }
}
