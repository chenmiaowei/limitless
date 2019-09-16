<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/5
 * Time: 11:54 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\fancytree;


use yii\helpers\Html;
use yii\helpers\Json;
/**
 * The yii2-fancytree-widget is a Yii 2 wrapper for the fancytree.js
 * See more: https://github.com/mar10/fancytree
 *
 * @author Wanderson Bragança <wanderson.wbc@gmail.com>
 */
class FancytreeWidget extends \yii\base\Widget
{
    /**
     * @var array
     */
    public $options = [];
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->registerAssets();
    }
    /**
     * Registers the needed assets
     */
    public function registerAssets()
    {
        $view = $this->getView();
        FancytreeAsset::register($view);
        $id = 'fancyree_' . $this->id;
        if (isset($this->options['id'])) {
            $id = $this->options['id'];
            unset($this->options['id']);
        }

        echo Html::tag('div', '', ['id' => $id]);
        $options = Json::encode($this->options);
        $view->registerJs("\n\$(\"#{$id}\").fancytree({$options});\n");
    }
}