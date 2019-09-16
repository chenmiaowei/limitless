<?php

namespace orangins\lib\view\layout;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;

/**
 * Class PhabricatorAnchorView
 * @package orangins\lib\view\layout
 * @author 陈妙威
 */
final class PhabricatorAnchorView extends AphrontView
{

    /**
     * @var
     */
    private $anchorName;
    /**
     * @var
     */
    private $navigationMarker;

    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function setAnchorName($name)
    {
        $this->anchorName = $name;
        return $this;
    }

    /**
     * @param $marker
     * @return $this
     * @author 陈妙威
     */
    public function setNavigationMarker($marker)
    {
        $this->navigationMarker = $marker;
        return $this;
    }

    /**
     * @return array|string
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function render()
    {
        $marker = null;
        if ($this->navigationMarker) {
            $marker = JavelinHtml::tag('legend', '', array(
                'class' => 'phabricator-anchor-navigation-marker',
                'sigil' => 'marker',
                'meta' => array(
                    'anchor' => $this->anchorName,
                ),
            ));
        }

        $anchor = JavelinHtml::tag('a', '', array(
            'name' => $this->anchorName,
            'id' => $this->anchorName,
            'class' => 'phabricator-anchor-view',
        ));

        return array($marker, $anchor);
    }

}
