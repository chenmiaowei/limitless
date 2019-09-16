<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/9/6
 * Time: 9:28 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\validators;

use yii\helpers\Json;
use yii\validators\Validator;

class JsonEncodeValidator extends Validator
{
    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $model->$attribute = is_array($model->$attribute) || is_object($model->$attribute) ? Json::encode($model->$attribute) : $model->$attribute;
    }
}