<?php

namespace orangins\lib\view\phui;

use orangins\lib\view\AphrontTagView;
use Yii;

/**
 * Class PHUIIconCircleView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIIconCircleView extends AphrontTagView
{

    /**
     * @var null
     */
    private $href = null;
    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $color;
    /**
     * @var
     */
    private $size;
    /**
     * @var
     */
    private $state;

    /**
     *
     */
    const SMALL = 'circle-small';
    /**
     *
     */
    const MEDIUM = 'circle-medium';

    /**
     *
     */
    const STATE_FAIL = 'fa-times-circle';
    /**
     *
     */
    const STATE_INFO = 'fa-info-circle';
    /**
     *
     */
    const STATE_STOP = 'fa-stop-circle';
    /**
     *
     */
    const STATE_START = 'fa-play-circle';
    /**
     *
     */
    const STATE_PAUSE = 'fa-pause-circle';
    /**
     *
     */
    const STATE_SUCCESS = 'fa-check-circle';
    /**
     *
     */
    const STATE_WARNING = 'fa-exclamation-circle';
    /**
     *
     */
    const STATE_PLUS = 'fa-plus-circle';
    /**
     *
     */
    const STATE_MINUS = 'fa-minus-circle';
    /**
     *
     */
    const STATE_UNKNOWN = 'fa-question-circle';

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
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        $tag = 'span';
        if ($this->href) {
            $tag = 'a';
        }
        return $tag;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
//    require_celerity_resource('phui-icon-view-css');

        $classes = array();
        $classes[] = 'phui-icon-circle';

        if ($this->color) {
            $classes[] = 'hover-' . $this->color;
        } else {
            $classes[] = 'hover-sky';
        }

        if ($this->size) {
            $classes[] = $this->size;
        }

        if ($this->state) {
            $classes[] = 'phui-icon-circle-state';
        }

        return array(
            'href' => $this->href,
            'class' => $classes,
        );
    }

    /**
     * @return array|PHUIIconView
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $state = null;
        if ($this->state) {
            $state = (new PHUIIconView())
                ->setIcon($this->state . ' ' . $this->color)
                ->addClass('phui-icon-circle-state-icon');
        }

        return (new PHUIIconView())
            ->setIcon($this->icon)
            ->addClass('phui-icon-circle-icon')
            ->appendChild($state);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getStateMap()
    {
        return array(
            self::STATE_FAIL => Yii::t("app", 'Failure'),
            self::STATE_INFO => Yii::t("app", 'Information'),
            self::STATE_STOP => Yii::t("app", 'Stop'),
            self::STATE_START => Yii::t("app", 'Start'),
            self::STATE_PAUSE => Yii::t("app", 'Pause'),
            self::STATE_SUCCESS => Yii::t("app", 'Success'),
            self::STATE_WARNING => Yii::t("app", 'Warning'),
            self::STATE_PLUS => Yii::t("app", 'Plus'),
            self::STATE_MINUS => Yii::t("app", 'Minus'),
            self::STATE_UNKNOWN => Yii::t("app", 'Unknown'),
        );
    }

}
