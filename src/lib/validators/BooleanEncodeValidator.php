<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/16
 * Time: 11:19 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\validators;

use yii\validators\Validator;

class BooleanEncodeValidator extends Validator
{
    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $model->$attribute = $model->$attribute === "true" ? true : ($model->$attribute === "false" ? false : !!$model->$attribute);
    }
}