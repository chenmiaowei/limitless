<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/8
 * Time: 4:59 PM
 */

namespace orangins\modules\userservice\tests\unit;


use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\infrastructure\daemon\contentsource\PhabricatorDaemonContentSource;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\modules\people\fixtures\UserFixture;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\userservice\editors\PhabricatorUserServiceEditor;
use orangins\modules\userservice\fixtures\PhabricatorUserServiceFixture;
use orangins\modules\userservice\models\PhabricatorUserService;
use orangins\modules\userservice\models\PhabricatorUserServiceTransaction;
use orangins\modules\userservice\xaction\PhabricatorUserServiceExpireTransaction;

class UserserviceTest extends \Codeception\Test\Unit
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
            PhabricatorUserServiceFixture::class,
            UserFixture::class,
        ];
    }

    /**
     * @throws \Exception
     */
    public function testEdit()
    {
        PhabricatorWorker::setRunAllTasksInProcess(true);

        $static = PhabricatorUserService::findOne(1);
        $viewer = PhabricatorUser::findOne(1);

        $phabricatorContentSource = PhabricatorContentSource::newForSource(PhabricatorDaemonContentSource::SOURCECONST);


        $xactions = array();
        $xactions[] = (new PhabricatorUserServiceTransaction)
            ->setTransactionType(PhabricatorUserServiceExpireTransaction::TRANSACTIONTYPE)
            ->setNewValue(strtotime("+1 year"));

        (new PhabricatorUserServiceEditor())
            ->setActor($viewer)
            ->setContinueOnNoEffect(true)
            ->setContentSource($phabricatorContentSource)
            ->applyTransactions($static, $xactions);
    }
}