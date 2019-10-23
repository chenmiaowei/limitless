<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/8
 * Time: 4:59 PM
 */

namespace orangins\modules\file\tests\unit;


use AphrontWriteGuard;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\modules\people\fixtures\UserFixture;
use orangins\modules\search\worker\PhabricatorSearchWorker;

class PeopleIndexTest extends \Codeception\Test\Unit
{
    protected $tester;

    /**
     * @return array
     */
    public function _fixtures()
    {
        return [
            UserFixture::class
        ];
    }

    /**
     * @throws \Exception
     */
    public function testBuy()
    {
        $isGuardActive = AphrontWriteGuard::isGuardActive();
        !$isGuardActive && $aphrontWriteGuard = new AphrontWriteGuard(function () {});

        PhabricatorWorker::setRunAllTasksInProcess(true);
        PhabricatorSearchWorker::queueDocumentForIndexing(
            'PHID-USER-ylfu4gwkyq2z674oorf3',
            array(
                'force' => true,
            ));

        !$isGuardActive && $aphrontWriteGuard->dispose();
    }
}