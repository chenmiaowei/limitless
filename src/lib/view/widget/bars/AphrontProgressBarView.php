<?php

namespace orangins\lib\view\widget\bars;

/**
 * Class AphrontProgressBarView
 * @package orangins\lib\view\widget\bars
 * @author 陈妙威
 */
final class AphrontProgressBarView extends AphrontBarView
{

    /**
     *
     */
    const WIDTH = 100;

    /**
     * @var
     */
    private $value;
    /**
     * @var int
     */
    private $max = 100;
    /**
     * @var string
     */
    private $alt = '';

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getDefaultColor()
    {
        return parent::COLOR_AUTO_BADNESS;
    }

    /**
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param $max
     * @return $this
     * @author 陈妙威
     */
    public function setMax($max)
    {
        $this->max = $max;
        return $this;
    }

    /**
     * @param $text
     * @return $this
     * @author 陈妙威
     */
    public function setAlt($text)
    {
        $this->alt = $text;
        return $this;
    }

    /**
     * @return float|int
     * @author 陈妙威
     */
    protected function getRatio()
    {
        return min($this->value, $this->max) / $this->max;
    }

    /**
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
//        require_celerity_resource('aphront-bars');
        $ratio = $this->getRatio();
        $width = self::WIDTH * $ratio;

        $color = $this->getColor();

        return phutil_tag_div(
            "aphront-bar progress color-{$color}",
            array(
                phutil_tag(
                    'div',
                    array('title' => $this->alt),
                    phutil_tag(
                        'div',
                        array('style' => "width: {$width}px;"),
                        '')),
                phutil_tag(
                    'span',
                    array(),
                    $this->getCaption()),
            ));
    }

}
