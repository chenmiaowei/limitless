<?php
namespace orangins\modules\metamta\fixtures;

use yii\test\ActiveFixture;

/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/31
 * Time: 7:10 PM
 * Email: chenmiaowei0914@gmail.com
 */

class MetaMTAMailFixture extends ActiveFixture
{
    public $modelClass = 'orangins\modules\metamta\models\PhabricatorMetaMTAMail';

    public function init()
    {
        parent::init();
        $this->dataFile = codecept_data_dir() .  'MetaMTAMail.php';
    }
}