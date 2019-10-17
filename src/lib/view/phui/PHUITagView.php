<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/27
 * Time: 11:16 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\view\phui;

use PhutilInvalidStateException;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\modules\widgets\javelin\JavelinHoverCardAsset;
use Yii;
use yii\helpers\ArrayHelper;

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
     * @throws \ReflectionException
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
     * @throws \yii\base\Exception
     * @throws \Exception
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
     * @return array
     * @author 陈妙威
     */
    public static function getShades()
    {
        return array_keys(self::getShadeMap());
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getShadeMap()
    {
        return array(
            self::COLOR_DANGER => Yii::t("app",'Red'),
            self::COLOR_ORANGE => Yii::t("app",'Orange'),
            self::COLOR_WARNING => Yii::t("app",'Yellow'),
            self::COLOR_BLUE => Yii::t("app",'Blue'),
            self::COLOR_INDIGO => Yii::t("app",'Indigo'),
            self::COLOR_VIOLET => Yii::t("app",'Violet'),
            self::COLOR_GREEN => Yii::t("app",'Green'),
            self::COLOR_GREY => Yii::t("app",'Grey'),
            self::COLOR_PINK => Yii::t("app",'Pink'),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getShadeCode()
    {
        return array(
            self::COLOR_DANGER => "#F44336",
            self::COLOR_PRIMARY => "#2196F3",
            self::COLOR_SUCCESS => "#4CAF50",
            self::COLOR_WARNING => "#FF5722",
            self::COLOR_INFO => "#00BCD4",
            self::COLOR_PINK => "#E91E63",
            self::COLOR_VIOLET => "#9C27B0",
            self::COLOR_PURPLE => "#673AB7",
            self::COLOR_INDIGO => "#3F51B5",
            self::COLOR_BLUE => "#03A9F4",
            self::COLOR_TEAL => "#009688",
            self::COLOR_GREEN => "#8BC34A",
            self::COLOR_ORANGE => "#FF9800",
            self::COLOR_BROWN => "#795548",
            self::COLOR_GREY => "#777777",
            self::COLOR_SLATE => "#607D8B",
        );
    }


    /**
     * @param $shade
     * @return mixed
     * @author 陈妙威
     */
    public static function getShadeName($shade)
    {
        return ArrayHelper::getValue(self::getShadeMap(), $shade, $shade);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getOutlines()
    {
        return array_keys(self::getOutlineMap());
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getOutlineMap()
    {
        return array(
            self::COLOR_DANGER => Yii::t("app",'Red'),
            self::COLOR_ORANGE => Yii::t("app",'Orange'),
            self::COLOR_WARNING => Yii::t("app",'Yellow'),
            self::COLOR_BLUE => Yii::t("app",'Blue'),
            self::COLOR_INDIGO => Yii::t("app",'Indigo'),
            self::COLOR_VIOLET => Yii::t("app",'Violet'),
            self::COLOR_GREEN => Yii::t("app",'Green'),
            self::COLOR_GREY => Yii::t("app",'Grey'),
            self::COLOR_PINK => Yii::t("app",'Pink'),
        );
    }

    /**
     * @param $outline
     * @return mixed
     * @author 陈妙威
     */
    public static function getOutlineName($outline)
    {
        return ArrayHelper::getValue(self::getOutlineMap(), $outline, $outline);
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