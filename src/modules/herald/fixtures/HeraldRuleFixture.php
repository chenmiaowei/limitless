<?php
namespace orangins\modules\herald\fixtures;

use yii\test\ActiveFixture;

/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/31
 * Time: 7:10 PM
 * Email: chenmiaowei0914@gmail.com
 */

class HeraldRuleFixture extends ActiveFixture
{
    public $modelClass = 'orangins\modules\herald\models\HeraldRule';

    public function init()
    {
        parent::init();
        $this->dataFile = codecept_data_dir() .  'HeraldRule.php';
    }
}