<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/25
 * Time: 10:17 PM
 */

namespace orangins\lib\search;


use yii\db\ActiveRecord;
use yii\widgets\ActiveForm;

/**
 * Class OranginsStringSearchType
 * @package orangins\lib\search
 */
class OranginsStringSearchType extends OranginsSearchType
{
    const TYPEKEY = "string";
    /**
     * @param ActiveForm $form
     * @param ActiveRecord $model
     * @param OranginsSearchOption $option
     * @return string
     */
    public function renderControl(ActiveForm $form, ActiveRecord $model, OranginsSearchOption $option)
    {
        return $form->field($model, $option->getKey())->widget(InputWidgetControl::class);
    }
}