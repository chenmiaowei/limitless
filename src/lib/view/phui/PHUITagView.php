<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/27
 * Time: 11:16 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\view\phui;

use Exception;
use PhutilInvalidStateException;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\modules\widgets\javelin\JavelinHoverCardAsset;
use ReflectionException;
use Yii;

/**
 * Class PHUITagView
 * @package orangins\modules\widgets\components
 * @author 陈妙威
 */
class PHUITagView extends AphrontTagView
{
    /**
     *
     */
    const TYPE_PERSON = 'person';
    /**
     *
     */
    const TYPE_OBJECT = 'object';
    /**
     *
     */
    const TYPE_STATE = 'state';
    /**
     *
     */
    const TYPE_SHADE = 'shade';
    /**
     *
     */
    const TYPE_OUTLINE = 'outline';
    /**
     *
     */
    const COLOR_OBJECT = 'object';
    /**
     *
     */
    const COLOR_PERSON = 'person';
    /**
     *
     */
    const BORDER_NONE = 'border-none';

    /**
     * @var
     */
    private $type;
    /**
     * @var
     */
    private $href;
    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $phid;
    /**
     * @var
     */
    private $color = "indigo-400";
    /**
     * @var
     */
    private $backgroundColor;
    /**
     * @var
     */
    private $dotColor;
    /**
     * @var
     */
    private $closed;
    /**
     * @var
     */
    private $external;
    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $shade;
    /**
     * @var
     */
    private $slimShady;
    /**
     * @var
     */
    private $border;

    /**
     * @param $type
     * @return $this
     * @author 陈妙威
     */
    public function setType($type)
    {
        $this->type = $type;
        switch ($type) {
            case self::TYPE_SHADE:
            case self::TYPE_OUTLINE:
                break;
            case self::TYPE_OBJECT:
                $this->setBackgroundColor(self::COLOR_OBJECT);
                break;
            case self::TYPE_PERSON:
                $this->setBackgroundColor(self::COLOR_PERSON);
                break;
        }
        return $this;
    }

    /**
     * This method has been deprecated, use @{method:setColor} instead.
     *
     * @param $shade
     * @return PHUITagView
     * @deprecated
     */
    public function setShade($shade)
    {
        Yii::error(
            Yii::t("app",'Deprecated call to setShade(), use setColor() instead.'));
        $this->color = $shade;
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
     * @param $dot_color
     * @return $this
     * @author 陈妙威
     */
    public function setDotColor($dot_color)
    {
        $this->dotColor = $dot_color;
        return $this;
    }

    /**
     * @param $background_color
     * @return $this
     * @author 陈妙威
     */
    public function setBackgroundColor($background_color)
    {
        $this->backgroundColor = $background_color;
        return $this;
    }

    /**
     * @param $phid
     * @return $this
     * @author 陈妙威
     */
    public function setPHID($phid)
    {
        $this->phid = $phid;
        return $this;
    }

    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * @param $closed
     * @return $this
     * @author 陈妙威
     */
    public function setClosed($closed)
    {
        $this->closed = $closed;
        return $this;
    }

    /**
     * @param $border
     * @return $this
     * @author 陈妙威
     */
    public function setBorder($border)
    {
        $this->border = $border;
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
     * @param $is_eminem
     * @return $this
     * @author 陈妙威
     */
    public function setSlimShady($is_eminem)
    {
        $this->slimShady = $is_eminem;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return strlen($this->href) ? 'a' : 'span';
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws ReflectionException
     */
    protected function getTagAttributes()
    {
        $classes = array(
            "btn btn-xs btn-outline bg-{$this->color} text-{$this->color} border-{$this->color}",
        );

        if ($this->slimShady) {
            $classes[] = 'phui-tag-slim';
        }

        if ($this->type == self::TYPE_SHADE) {
            $classes[] = 'phui-tag-shade';
        }

        if ($this->icon) {
            $classes[] = 'phui-tag-icon-view';
        }

        if ($this->border) {
            $classes[] = 'phui-tag-' . $this->border;
        }

        $attributes = array(
            'href' => $this->href,
            'class' => $classes,
        );

        if ($this->external) {
            $attributes += array(
                'target' => '_blank',
                'rel' => 'noreferrer',
            );
        }

        if ($this->phid) {
            JavelinHtml::initBehavior(new JavelinHoverCardAsset());

            $attributes += array(
                'sigil' => 'hovercard',
                'meta' => array(
                    'hoverPHID' => $this->phid,
                ),
            );
        }

        return $attributes;
    }

    /**
     * @return array|string
     * @throws PhutilInvalidStateException
     * @throws Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        if (!$this->type) {
            throw new PhutilInvalidStateException('setType', 'render');
        }

        $color = null;
        if (!$this->shade && $this->backgroundColor) {
            $color = 'phui-tag-color-' . $this->backgroundColor;
        }

        if ($this->dotColor) {
            $dotcolor = 'phui-tag-color-' . $this->dotColor;
            $dot = JavelinHtml::phutil_tag(
                'span',
                array(
                    'class' => 'phui-tag-dot ' . $dotcolor,
                ),
                '');
        } else {
            $dot = null;
        }

        if ($this->icon) {
            $icon = (new PHUIIconView())
                ->addClass("mr-1")
                ->setIcon($this->icon);
        } else {
            $icon = null;
        }

        $content = JavelinHtml::phutil_tag(
            'span',
            array(
                'class' => 'phui-tag-core ' . $color,
            ),
            array($dot, $icon, $this->name));

        if ($this->closed) {
            $content = JavelinHtml::phutil_tag(
                'span',
                array(
                    'class' => 'phui-tag-core-closed',
                ),
                array($icon, $content));
        }

        return $content;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getTagTypes()
    {
        return array(
            self::TYPE_PERSON,
            self::TYPE_OBJECT,
            self::TYPE_STATE,
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getColors()
    {
        return array(
            self::COLOR_ORANGE,
            self::COLOR_WARNING,
            self::COLOR_DANGER,
            self::COLOR_BLUE,
            self::COLOR_INDIGO,
            self::COLOR_VIOLET,
            self::COLOR_GREEN,
            self::COLOR_GREY,
            self::TEXT_WHITE,
            self::COLOR_OBJECT,
            self::COLOR_PERSON,
        );
    }



    /**
     * @param $external
     * @return $this
     * @author 陈妙威
     */
    public function setExternal($external)
    {
        $this->external = $external;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getExternal()
    {
        return $this->external;
    }
}