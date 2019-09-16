<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/27
 * Time: 2:31 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\metamta\models;


use orangins\lib\helpers\OranginsUtil;
use PhutilJSON;
use yii\behaviors\AttributeBehavior;
use yii\db\BaseActiveRecord;
use yii\db\Expression;
use yii\helpers\Json;

class MetaMTAMailParamBehavior extends AttributeBehavior
{
    public function init()
    {
        parent::init();
        if (empty($this->attributes)) {
            $this->attributes = [
                BaseActiveRecord::EVENT_AFTER_FIND => "parameters",
            ];
        }
    }

    /**
     * @inheritdoc
     */
    protected function getValue($event)
    {
        if ($this->value instanceof Expression) {
            return $this->value;
        } else {
            $decode = OranginsUtil::phutil_json_decode($this->owner->parameters);
            return $this->owner->parameters = $decode;
        }
    }
}