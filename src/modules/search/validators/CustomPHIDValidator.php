<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/9/9
 * Time: 6:36 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\search\validators;

use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use yii\validators\Validator;

class CustomPHIDValidator extends Validator
{
    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $model->$attribute = $model->$attribute === PhabricatorProfileMenuItemConfiguration::INSTALL_PERSONAL ? \Yii::$app->user->identity->phid : null;
    }
}