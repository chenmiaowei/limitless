<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/25
 * Time: 10:14 PM
 */

namespace orangins\lib\search;

use orangins\lib\OranginsObject;
use yii\db\ActiveRecord;
use yii\widgets\ActiveForm;

/**
 * Class OranginsSearchType
 * @package orangins\lib\search
 */
abstract class OranginsSearchType extends OranginsObject
{
    /**
     * @param ActiveForm $form
     * @param ActiveRecord $model
     * @param OranginsSearchOption $option
     * @return string
     */
    abstract public function renderControl(ActiveForm $form, ActiveRecord $model, OranginsSearchOption $option);

    /**
     * @return array
     */
    public static function getAllTypes()
    {
        return [
            OranginsStringSearchType::TYPEKEY => OranginsStringSearchType::class
        ];
    }
}