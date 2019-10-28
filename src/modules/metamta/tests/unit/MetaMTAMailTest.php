<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/8
 * Time: 4:59 PM
 */

namespace orangins\modules\metamta\tests\unit;


use orangins\modules\config\fixtures\ConfigFixture;
use orangins\modules\people\fixtures\UserEmailFixture;
use orangins\modules\people\fixtures\UserFixture;
use orangins\modules\metamta\fixtures\MetaMTAMailFixture;

class MetaMTAMailTest extends \Codeception\Test\Unit
{

    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    /**
     * @return array
     */
    public function _fixtures()
    {
        return [
            ConfigFixture::class,
            UserFixture::class,
            UserEmailFixture::class,
            MetaMTAMailFixture::class,
        ];
    }

    /**
     * @return \orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTask
     * @throws \AphrontQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws \ReflectionException
     */
    public function testSend()
    {
//        PhabricatorWorker::setRunAllTasksInProcess(true);
//        return PhabricatorWorker::scheduleTask(
//            'PhabricatorMetaMTAWorker', 1,
//            array('priority' => 1));
    }
}