<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;

/**
 * Class PHUISegmentBarSegmentView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUISegmentBarSegmentView extends AphrontTagView
{

    /**
     * @var
     */
    private $width;
    /**
     * @var
     */
    private $color;
    /**
     * @var
     */
    private $position;
    /**
     * @var
     */
    private $tooltip;

    /**
     * @param $width
     * @return $this
     * @author 陈妙威
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getWidth()
    {
        return $this->width;
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
     * @param $position
     * @return $this
     * @author 陈妙威
     */
    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @param $tooltip
     * @return $this
     * @author 陈妙威
     */
    public function setTooltip($tooltip)
    {
        $this->tooltip = $tooltip;
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
     * @return array
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $classes = array(
            'phui-segment-bar-segment-view',
        );

        if ($this->color) {
            $classes[] = $this->color;
        }

        // Convert width to a percentage, and round it up slightly so that bars
        // are full if they have, e.g., three segments at 1/3 + 1/3 + 1/3.
        $width = 100 * $this->width;
        $width = ceil(100 * $width) / 100;
        $width = sprintf('%.2f%%', $width);

        $left = 100 * $this->position;
        $left = floor(100 * $left) / 100;
        $left = sprintf('%.2f%%', $left);

        $tooltip = $this->tooltip;
        if (strlen($tooltip)) {
            JavelinHtml::initBehavior(new JavelinTooltipAsset());

            $sigil = 'has-tooltip';
            $meta = array(
                'tip' => $tooltip,
                'align' => 'E',
            );
        } else {
            $sigil = null;
            $meta = null;
        }

        return array(
            'class' => implode(' ', $classes),
            'style' => "left: {$left}; width: {$width};",
            'sigil' => $sigil,
            'meta' => $meta,
        );
    }

}
