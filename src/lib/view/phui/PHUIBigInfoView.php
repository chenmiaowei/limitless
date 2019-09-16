<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;

/**
 * Class PHUIBigInfoView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIBigInfoView extends AphrontTagView
{

    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $title;
    /**
     * @var
     */
    private $description;
    /**
     * @var
     */
    private $image;
    /**
     * @var array
     */
    private $actions = array();

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
     * @param $title
     * @return $this
     * @author 陈妙威
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param $description
     * @return $this
     * @author 陈妙威
     */
    public function setDescription($description)
    {
        $this->description = $description;
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
     * @param PHUIButtonView $button
     * @return $this
     * @author 陈妙威
     */
    public function addAction(PHUIButtonView $button)
    {
        $this->actions[] = $button;
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
        $classes = array();
        $classes[] = 'text-center pt-3 pb-3 phui-big-info-view';

        return array(
            'class' => implode(' ', $classes),
        );
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
//    require_celerity_resource('phui-big-info-view-css');

        $icon = null;
        if ($this->icon) {
            $icon = (new PHUIIconView())
                ->setIcon($this->icon)
                ->addClass('border-3 rounded-round p-3 mb-3 mt-1 phui-big-info-icon');

            $icon = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-big-info-icon-container',
                ),
                $icon);
        }

        if ($this->image) {
            $image = JavelinHtml::phutil_tag(
                'img',
                array(
                    'class' => 'phui-big-info-image',
                    'src' => $this->image,
                ));
            $icon = JavelinHtml::phutil_tag(
                'span',
                array(
                    'class' => 'phui-big-info-icon-container',
                ),
                $image);
        }

        $title = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-big-info-title',
            ),
            $this->title);

        $description = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-big-info-description',
            ),
            $this->description);

        $buttons = array();
        foreach ($this->actions as $button) {
            $buttons[] = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-big-info-button',
                ),
                $button);
        }

        $actions = null;
        if ($buttons) {
            $actions = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'mt-3 phui-big-info-actions',
                ),
                $buttons);
        }

        return array($icon, $title, $description, $actions);

    }
}
