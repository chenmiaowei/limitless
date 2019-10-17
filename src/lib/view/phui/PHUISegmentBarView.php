<?php

namespace orangins\lib\view\phui;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;

/**
 * Class PHUISegmentBarView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUISegmentBarView extends AphrontTagView
{

    /**
     * @var
     */
    private $label;
    /**
     * @var array
     */
    private $segments = array();

    /**
     * @param $label
     * @return $this
     * @author 陈妙威
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return PHUISegmentBarSegmentView
     * @author 陈妙威
     */
    public function newSegment()
    {
        $segment = new PHUISegmentBarSegmentView();
        $this->segments[] = $segment;
        return $segment;
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
            'class' => 'phui-segment-bar-view',
        );
    }

    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
//        require_celerity_resource('phui-segment-bar-view-css');

        $label = $this->label;
        if (strlen($label)) {
            $label = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-segment-bar-label',
                ),
                $label);
        }

        $segments = $this->segments;

        $position = 0;
        foreach ($segments as $segment) {
            $segment->setPosition($position);
            $position += $segment->getWidth();
        }

        $segments = array_reverse($segments);

        $segments = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-segment-bar-segments',
            ),
            $segments);

        return array(
            $label,
            $segments,
        );
    }

}
