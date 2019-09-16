<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;

/**
 * Class PHUIPinboardItemView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIPinboardItemView extends AphrontView
{

    /**
     * @var
     */
    private $imageURI;
    /**
     * @var
     */
    private $uri;
    /**
     * @var
     */
    private $header;
    /**
     * @var array
     */
    private $iconBlock = array();
    /**
     * @var
     */
    private $disabled;
    /**
     * @var
     */
    private $object;
    /**
     * @var
     */
    private $imageWidth;
    /**
     * @var
     */
    private $imageHeight;

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
     * @param $uri
     * @return $this
     * @author 陈妙威
     */
    public function setURI($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @param $image_uri
     * @return $this
     * @author 陈妙威
     */
    public function setImageURI($image_uri)
    {
        $this->imageURI = $image_uri;
        return $this;
    }

    /**
     * @param $x
     * @param $y
     * @return $this
     * @author 陈妙威
     */
    public function setImageSize($x, $y)
    {
        $this->imageWidth = $x;
        $this->imageHeight = $y;
        return $this;
    }

    /**
     * @param $icon
     * @param $count
     * @return $this
     * @author 陈妙威
     */
    public function addIconCount($icon, $count)
    {
        $this->iconBlock[] = array($icon, $count);
        return $this;
    }

    /**
     * @param $disabled
     * @return $this
     * @author 陈妙威
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return mixed|string
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function render()
    {
//        require_celerity_resource('phui-pinboard-view-css');
        $header = null;
        if ($this->header) {
            $header_color = null;
            if ($this->disabled) {
                $header_color = 'phui-pinboard-disabled';
            }
            $header = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-pinboard-item-header ' . $header_color,
                ),
                array(
                    (new PHUISpacesNamespaceContextView())
                        ->setUser($this->getUser())
                        ->setObject($this->object),
                    JavelinHtml::phutil_tag(
                        'a',
                        array(
                            'href' => $this->uri,
                        ),
                        $this->header),
                ));
        }

        $image = null;
        if ($this->imageWidth) {
            $image = JavelinHtml::phutil_tag(
                'a',
                array(
                    'href' => $this->uri,
                    'class' => 'phui-pinboard-item-image-link',
                ),
                JavelinHtml::phutil_tag(
                    'img',
                    array(
                        'src' => $this->imageURI,
                        'width' => $this->imageWidth,
                        'height' => $this->imageHeight,
                    )));
        }

        $icons = array();
        if ($this->iconBlock) {
            $icon_list = array();
            foreach ($this->iconBlock as $block) {
                $icon = (new PHUIIconView())
                    ->setIcon($block[0] . ' lightgreytext')
                    ->addClass('phui-pinboard-icon');

                $count = JavelinHtml::phutil_tag('span', array(), $block[1]);
                $icon_list[] = JavelinHtml::phutil_tag(
                    'span',
                    array(
                        'class' => 'phui-pinboard-item-count',
                    ),
                    array($icon, $count));
            }
            $icons = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-pinboard-icons',
                ),
                $icon_list);
        }

        $content = $this->renderChildren();
        if ($content) {
            $content = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-pinboard-item-content',
                ),
                $content);
        }

        $classes = array();
        $classes[] = 'phui-pinboard-item-view';
        if ($this->disabled) {
            $classes[] = 'phui-pinboard-item-disabled';
        }

        $item = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => implode(' ', $classes),
            ),
            array(
                $image,
                $header,
                $content,
                $icons,
            ));

        return JavelinHtml::phutil_tag(
            'li',
            array(
                'class' => 'phui-pinboard-list-item',
            ),
            $item);
    }

}
