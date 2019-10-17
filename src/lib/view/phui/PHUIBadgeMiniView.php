<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/9/2
 * Time: 2:16 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\view\phui;


use orangins\lib\view\AphrontView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class BadgeWidget
 * @package orangins\modules\widgets\components
 * @author 陈妙威
 */
class PHUIBadgeMiniView extends AphrontView
{
    /**
     * @var string
     */
    public $color = "secondary";
    /**
     * @var string
     */
    public $label;
    /**
     * @var
     */
    public $icon;

    /**
     * @var
     */
    public $tag = "span";

    /**
     * @var array
     */
    public $options = [
    ];

    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @param string $color
     * @return self
     */
    public function setColor(string $color): void
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return self
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param mixed $icon
     * @return self
     */
    public function setIcon($icon): void
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param mixed $tag
     * @return self
     */
    public function setTag($tag): void
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return self
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
        return $this;
    }


    /**
     * @author 陈妙威
     */
    public function init()
    {
        parent::init();
        Html::addCssClass($this->options, "badge badge-flat border-grey text-grey-600 pt-1 pb-1 pl-2 pr-2");
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function render()
    {
        $icon = $this->icon ? (new PHUIIconView())
            ->setIcon($this->icon)
            ->addClass('mr-1'): "";
        return Html::tag($this->tag, $icon . $this->label, $this->options);    }
}