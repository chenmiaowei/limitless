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
     * @var array
     */
    public $iconOptions = ["class" => "mr-1"];
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
     * @author 陈妙威
     */
    public function init()
    {
        parent::init();
        Html::addCssClass($this->options, "badge badge-flat border-grey text-grey-600 pt-1 pb-1 pl-2 pr-2");
    }

    /**
     * @author 陈妙威
     * @throws \Exception
     */
    public function run()
    {
        $config = ArrayHelper::merge([
            "options" => $this->iconOptions
        ], [
            "icon" => $this->icon
        ]);
        $icon = $this->icon ? PHUIIconView::widget($config) : "";
        return Html::tag($this->tag, $icon . $this->label, $this->options);
    }
}