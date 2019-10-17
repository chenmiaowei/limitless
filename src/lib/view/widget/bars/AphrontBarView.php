<?php

namespace orangins\lib\view\widget\bars;

use orangins\lib\view\AphrontView;

/**
 * Class AphrontBarView
 * @package orangins\lib\view\widget\bars
 * @author 陈妙威
 */
abstract class AphrontBarView extends AphrontView
{

    /**
     * @var
     */
    private $color;
    /**
     * @var string
     */
    private $caption = '';

    /**
     *
     */
    const COLOR_DEFAULT = 'default';
    /**
     *
     */
    const COLOR_WARNING = 'warning';
    /**
     *
     */
    const COLOR_DANGER = 'danger';

    /**
     *
     */
    const COLOR_AUTO_BADNESS = 'auto_badness';   // more = bad!  :(
    /**
     *
     */
    const COLOR_AUTO_GOODNESS = 'auto_goodness';  // more = good! :)

    /**
     *
     */
    const THRESHOLD_DANGER = 0.85;
    /**
     *
     */
    const THRESHOLD_WARNING = 0.75;

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getRatio();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getDefaultColor();

    /**
     * @param $color
     * @return $this
     * @author 陈妙威
     */
    final public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @param $text
     * @return $this
     * @author 陈妙威
     */
    final public function setCaption($text)
    {
        $this->caption = $text;
        return $this;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    final protected function getColor()
    {
        $color = $this->color;
        if (!$color) {
            $color = $this->getDefaultColor();
        }

        switch ($color) {
            case self::COLOR_DEFAULT:
            case self::COLOR_WARNING:
            case self::COLOR_DANGER:
                return $color;
        }

        $ratio = $this->getRatio();
        if ($color === self::COLOR_AUTO_GOODNESS) {
            $ratio = 1.0 - $ratio;
        }

        if ($ratio >= self::THRESHOLD_DANGER) {
            return self::COLOR_DANGER;
        } else if ($ratio >= self::THRESHOLD_WARNING) {
            return self::COLOR_WARNING;
        } else {
            return self::COLOR_DEFAULT;
        }
    }

    /**
     * @return string
     * @author 陈妙威
     */
    final protected function getCaption()
    {
        return $this->caption;
    }

}
