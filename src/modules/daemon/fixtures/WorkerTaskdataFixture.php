<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/20
 * Time: 3:21 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\daemon\fixtures;

use yii\test\ActiveFixture;

class WorkerTaskdataFixture  extends ActiveFixture
{
    public $modelClass = 'orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTaskData';

    public function init()
    {
        parent::init();
        $this->dataFile = codecept_data_dir() .  'worker_taskdata.php';
    }
}