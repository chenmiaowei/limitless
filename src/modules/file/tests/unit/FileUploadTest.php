<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/8
 * Time: 4:59 PM
 */

namespace orangins\modules\file\tests\unit;


use AphrontWriteGuard;
use Filesystem;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\modules\file\fixtures\PhabricatorFileFixture;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\search\worker\PhabricatorSearchWorker;

class FileUploadTest extends \Codeception\Test\Unit
{
    protected $tester;

    /**
     * @return array
     */
    public function _fixtures()
    {
        return [
            PhabricatorFileFixture::class
        ];
    }

    /**
     * @throws \Exception
     */
    public function testBuy()
    {
        $isGuardActive = AphrontWriteGuard::isGuardActive();
        !$isGuardActive && $aphrontWriteGuard = new AphrontWriteGuard(function () {});

        $sampleFilePath = dirname(__DIR__) . "/_data/sx2019050201.zip";
        $file_data = Filesystem::readFile($sampleFilePath);

        $file_name = "sx2019050201.zip";
        $params = array(
            'name' => $file_name,
        );

        PhabricatorSearchWorker::setRunAllTasksInProcess(true);
        $phabricatorFile = PhabricatorFile::newFromFileData($file_data, $params);

        !$isGuardActive && $aphrontWriteGuard->dispose();
    }
}