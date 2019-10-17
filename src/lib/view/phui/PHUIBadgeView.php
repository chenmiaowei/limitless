<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\modules\badges\constants\PhabricatorBadgesQuality;
use orangins\modules\widgets\javelin\JavelinBadgeViewAsset;

/**
 * Class PHUIBadgeView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIBadgeView extends AphrontTagView
{

    /**
     * @var
     */
    private $href;
    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $quality;
    /**
     * @var
     */
    private $source;
    /**
     * @var
     */
    private $header;
    /**
     * @var
     */
    private $subhead;
    /**
     * @var array
     */
    private $bylines = array();

    /**
     * @param $icon
     * @return $this
     * @author 陈妙威
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @param $href
     * @return $this
     * @author 陈妙威
     */
    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }

    /**
     * @param $quality
     * @return $this
     * @author 陈妙威
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getQualityColor()
    {
        return PhabricatorBadgesQuality::getQualityColor($this->quality);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getQualityName()
    {
        return PhabricatorBadgesQuality::getQualityName($this->quality);
    }

    /**
     * @param $source
     * @return $this
     * @author 陈妙威
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @param $header
     * @return $this
     * @author 陈妙威
     */
    public function setHeader($header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @param $subhead
     * @return $this
     * @author 陈妙威
     */
    public function setSubhead($subhead)
    {
        $this->subhead = $subhead;
        return $this;
    }

    /**
     * @param $byline
     * @return $this
     * @author 陈妙威
     */
    public function addByline($byline)
    {
        $this->bylines[] = $byline;
        return $this;
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
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
//        require_celerity_resource('phui-badge-view-css');
        $id = JavelinHtml::generateUniqueNodeId();

        JavelinHtml::initBehavior(new JavelinBadgeViewAsset(), array());

        $classes = array();
        $classes[] = 'phui-badge-view';
        if ($this->quality) {
            $color = $this->getQualityColor();
            $classes[] = 'phui-badge-view-' . $color;
        }

        return array(
            'class' => implode(' ', $classes),
            'sigil' => 'jx-badge-view',
            'id' => $id,
            'meta' => array(
                'map' => array(
                    $id => 'card-flipped',
                ),
            ),
        );
    }

    /**
     * @return array|string
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {

        $icon = (new PHUIIconView())
            ->setIcon($this->icon);

        $illustration = JavelinHtml::phutil_tag_div('phui-badge-illustration', $icon);

        $header = null;
        if ($this->header) {
            $header = JavelinHtml::phutil_tag(
                ($this->href) ? 'a' : 'span',
                array(
                    'class' => 'phui-badge-view-header',
                    'href' => $this->href,
                ),
                $this->header);
        }

        $subhead = null;
        if ($this->subhead) {
            $subhead = JavelinHtml::phutil_tag_div('phui-badge-view-subhead', $this->subhead);
        }

        $information = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-badge-view-information',
            ),
            array($header, $subhead));

        $quality = JavelinHtml::phutil_tag_div('phui-badge-quality', $this->getQualityName());
        $source = JavelinHtml::phutil_tag_div('phui-badge-source', $this->source);

        $bylines = array();
        if ($this->bylines) {
            foreach ($this->bylines as $byline) {
                $bylines[] = JavelinHtml::phutil_tag_div('phui-badge-byline', $byline);
            }
        }

        $card_front_1 = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-badge-inner-front',
            ),
            array(
                $illustration,
            ));

        $card_front_2 = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-badge-inner-front',
            ),
            array(
                $information,
            ));

        $back_info = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-badge-view-information',
            ),
            array(
                $quality,
                $source,
                $bylines,
            ));

        $card_back = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-badge-inner-back',
            ),
            array(
                $back_info,
            ));

        $inner_front = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-badge-front-view',
            ),
            array(
                $card_front_1,
                $card_front_2,
            ));

        $inner_back = JavelinHtml::phutil_tag_div('phui-badge-back-view', $card_back);
        $front = JavelinHtml::phutil_tag_div('phui-badge-card-front', $inner_front);
        $back = JavelinHtml::phutil_tag_div('phui-badge-card-back', $inner_back);

        $card = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-badge-card',
            ),
            array(
                $front,
                $back,
            ));

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-badge-card-container',
            ),
            $card);

    }

}
