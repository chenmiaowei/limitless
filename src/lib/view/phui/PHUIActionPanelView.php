<?php

namespace orangins\lib\view\phui;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;

/**
 * Class PHUIActionPanelView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIActionPanelView extends AphrontTagView
{

    /**
     * @var
     */
    private $href;
    /**
     * @var
     */
    private $fontIcon;
    /**
     * @var
     */
    private $image;
    /**
     * @var
     */
    private $header;
    /**
     * @var
     */
    private $subHeader;
    /**
     * @var
     */
    private $bigText;
    /**
     * @var
     */
    private $state;
    /**
     * @var
     */
    private $status;


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
     * @param $image
     * @return $this
     * @author 陈妙威
     */
    public function setIcon($image)
    {
        $this->fontIcon = $image;
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
     * @param $text
     * @return $this
     * @author 陈妙威
     */
    public function setBigText($text)
    {
        $this->bigText = $text;
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
     * @param $sub
     * @return $this
     * @author 陈妙威
     */
    public function setSubHeader($sub)
    {
        $this->subHeader = $sub;
        return $this;
    }

    /**
     * @param $state
     * @return $this
     * @author 陈妙威
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @param $text
     * @return $this
     * @author 陈妙威
     */
    public function setStatus($text)
    {
        $this->status = $text;
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
//        require_celerity_resource('phui-action-panel-css');

        $classes = array();
        $classes[] = 'phui-action-panel';
        if ($this->state) {
            $classes[] = $this->state;
        }
        if ($this->bigText) {
            $classes[] = 'phui-action-panel-bigtext';
        }

        return array(
            'class' => implode(' ', $classes),
        );
    }

    /**
     * @return array|string
     * @throws Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {

        $icon = null;
        if ($this->fontIcon) {
            $fonticon = (new PHUIIconView())
                ->setIcon($this->fontIcon);
            $icon = JavelinHtml::phutil_tag(
                'span',
                array(
                    'class' => 'phui-action-panel-icon',
                ),
                $fonticon);
        }

        if ($this->image) {
            $image = JavelinHtml::phutil_tag(
                'img',
                array(
                    'class' => 'phui-action-panel-image',
                    'src' => $this->image,
                ));
            $icon = JavelinHtml::phutil_tag(
                'span',
                array(
                    'class' => 'phui-action-panel-icon',
                ),
                $image);
        }

        $header = null;
        if ($this->header) {
            $header = JavelinHtml::phutil_tag(
                'span',
                array(
                    'class' => 'phui-action-panel-header',
                ),
                $this->header);
        }

        $subheader = null;
        if ($this->subHeader) {
            $subheader = JavelinHtml::phutil_tag(
                'span',
                array(
                    'class' => 'phui-action-panel-subheader',
                ),
                $this->subHeader);
        }

        $row = JavelinHtml::phutil_tag(
            'span',
            array(
                'class' => 'phui-action-panel-row',
            ),
            array(
                $icon,
                $subheader,
            ));

        $table = JavelinHtml::phutil_tag(
            'span',
            array(
                'class' => 'phui-action-panel-table',
            ),
            $row);

        return JavelinHtml::phutil_tag(
            'a',
            array(
                'href' => $this->href,
                'class' => 'phui-action-panel-hitarea',
            ),
            array($header, $table));

    }

}
