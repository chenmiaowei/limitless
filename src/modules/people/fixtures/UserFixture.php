<?php
namespace orangins\modules\people\fixtures;

use yii\test\ActiveFixture;

/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/31
 * Time: 7:10 PM
 * Email: chenmiaowei0914@gmail.com
 */

class UserFixture extends ActiveFixture
{
    /**
     * @var string
     */
    public $modelClass = 'orangins\modules\people\models\PhabricatorUser';

    /**
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function init()
    {
        parent::init();
        $this->dataFile = codecept_data_dir() .  'user.php';
    }
}