<?php

namespace orangins\lib\view\phui;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;

/**
 * Class PHUIHeadThingView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIHeadThingView extends AphrontTagView
{

    /**
     * @var
     */
    private $image;
    /**
     * @var
     */
    private $imageHref;
    /**
     * @var
     */
    private $content;
    /**
     * @var
     */
    private $size;

    /**
     *
     */
    const SMALL = 'head-thing-small';
    /**
     *
     */
    const MEDIUM = 'head-thing-medium';

    /**
     * @param $href
     * @return $this
     * @author 陈妙威
     */
    public function setImageHref($href)
    {
        $this->imageHref = $href;
        return $this;
    }

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
     * @param $content
     * @return $this
     * @author 陈妙威
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @param $size
     * @return $this
     * @author 陈妙威
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
//        require_celerity_resource('phui-head-thing-view-css');

        $classes = array();
        $classes[] = 'phui-head-thing-view';
        if ($this->image) {
            $classes[] = 'phui-head-has-image';
        }

        if ($this->size) {
            $classes[] = $this->size;
        } else {
            $classes[] = self::SMALL;
        }

        return array(
            'class' => $classes,
        );
    }

    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {

        $image = JavelinHtml::phutil_tag(
            'a',
            array(
                'class' => 'phui-head-thing-image visual-only',
                'style' => 'background-image: url(' . $this->image . ');',
                'href' => $this->imageHref,
            ));

        if ($this->image) {
            return array($image, $this->content);
        } else {
            return $this->content;
        }

    }

}
