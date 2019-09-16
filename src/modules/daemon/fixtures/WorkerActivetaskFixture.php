<?php
namespace orangins\modules\daemon\fixtures;

use yii\test\ActiveFixture;

class WorkerActivetaskFixture extends ActiveFixture
{
    public $modelClass = 'orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerActiveTask';

    public $depends = [
        'orangins\modules\daemon\fixtures\WorkerTaskdataFixture',
    ];

    public function init()
    {
        parent::init();
        $this->dataFile = codecept_data_dir() .  'worker_activetask.php';
    }
}