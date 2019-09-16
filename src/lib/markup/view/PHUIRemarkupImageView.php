<?php

namespace orangins\lib\markup\view;

use orangins\lib\view\AphrontView;

/**
 * Class PHUIRemarkupImageView
 * @package orangins\lib\markup\view
 * @author 陈妙威
 */
final class PHUIRemarkupImageView
    extends AphrontView
{

    /**
     * @var
     */
    private $uri;
    /**
     * @var
     */
    private $width;
    /**
     * @var
     */
    private $height;
    /**
     * @var
     */
    private $alt;
    /**
     * @var array
     */
    private $classes = array();

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
     * @return mixed
     * @author 陈妙威
     */
    public function getURI()
    {
        return $this->uri;
    }

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
     * @param $height
     * @return $this
     * @author 陈妙威
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param $alt
     * @return $this
     * @author 陈妙威
     */
    public function setAlt($alt)
    {
        $this->alt = $alt;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAlt()
    {
        return $this->alt;
    }

    /**
     * @param $class
     * @return $this
     * @author 陈妙威
     */
    public function addClass($class)
    {
        $this->classes[] = $class;
        return $this;
    }

    /**
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        $id = JavelinHtml::celerity_generate_unique_node_id();

        Javelin::initBehavior(
            'remarkup-load-image',
            array(
                'uri' => (string)$this->uri,
                'imageID' => $id,
            ));

        $classes = null;
        if ($this->classes) {
            $classes = implode(' ', $this->classes);
        }

        return phutil_tag(
            'img',
            array(
                'id' => $id,
                'width' => $this->getWidth(),
                'height' => $this->getHeight(),
                'alt' => $this->getAlt(),
                'class' => $classes,
            ));
    }

}
