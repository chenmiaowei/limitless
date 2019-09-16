<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/8
 * Time: 4:59 PM
 */

namespace orangins\modules\daemon\tests\unit;


use PhutilBacktraceSignalHandler;
use PhutilConsoleMetricsSignalHandler;
use PhutilSignalRouter;
use orangins\modules\daemon\fixtures\WorkerActivetaskFixture;
use PhutilJSONParser;
use PHPUnit\Framework\TestResult;

class TaskmasterDaemonTest extends \Codeception\Test\Unit
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
            WorkerActivetaskFixture::class
        ];
    }

    /**
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException

     */
    public function testBuy()
    {

//        $router = PhutilSignalRouter::initialize();
//        $handler = new PhutilBacktraceSignalHandler();
//        $router->installHandler('phutil.backtrace', $handler);
//
//        $handler = new PhutilConsoleMetricsSignalHandler();
//        $router->installHandler('phutil.winch', $handler);


//        $phabricatorTaskmasterDaemon = new PhabricatorTaskmasterDaemon([]);
//        $phabricatorTaskmasterDaemon->execute();
    }
}