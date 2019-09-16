<?php
namespace orangins\modules\config\fixtures;

use yii\test\ActiveFixture;

/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/31
 * Time: 7:10 PM
 * Email: chenmiaowei0914@gmail.com
 */

class ConfigFixture extends ActiveFixture
{
    public $modelClass = 'orangins\modules\config\models\PhabricatorConfigEntry';

    public function init()
    {
        parent::init();
        $this->dataFile = codecept_data_dir() .  'config.php';
    }
}