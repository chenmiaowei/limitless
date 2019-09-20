<?php

namespace orangins\modules\file\engine;

use AphrontWriteGuard;
use Filesystem;
use orangins\lib\env\PhabricatorEnv;
use orangins\modules\file\exception\PhabricatorFileStorageConfigurationException;
use orangins\modules\file\exception\PhabricatorFileUploadException;
use PhutilServiceProfiler;
use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Qiniu\Zone;

/**
 * Amazon S3 file storage engine. This engine scales well but is relatively
 * high-latency since data has to be pulled off S3.
 *
 * @task internal Internals
 */
final class PhabricatorQiniuFileStorageEngine
    extends PhabricatorFileStorageEngine
{


    /* -(  Engine Metadata  )---------------------------------------------------- */


    /**
     * This engine identifies as `amazon-s3`.
     */
    public function getEngineIdentifier()
    {
        return 'qiniu-s3';
    }

    /**
     * @return float|int
     * @author 陈妙威
     */
    public function getEnginePriority()
    {
        return 4;
    }

    /**
     * @return bool
     * @throws \Exception
     * @author 陈妙威
     */
    public function canWriteFiles()
    {
        $bucket = PhabricatorEnv::getEnvConfig('qiniu-s3.bucket');
        $access_key = PhabricatorEnv::getEnvConfig('qiniu-s3.access.key');
        $secret_key = PhabricatorEnv::getEnvConfig('qiniu-s3.secret.key');
        $endpoint = PhabricatorEnv::getEnvConfig('qiniu-s3.endpoint');

        return (strlen($bucket) &&
            strlen($access_key) &&
            strlen($secret_key) &&
            strlen($endpoint)
        );
    }


    /* -(  Managing File Data  )------------------------------------------------- */


    /**
     * Writes file data into Amazon S3.
     * @param $data
     * @param array $params
     * @return string
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    public function writeFile($data, array $params)
    {
        $auth = $this->newS3API();

        // Generate a random name for this file. We add some directories to it
        // (e.g. 'abcdef123456' becomes 'ab/cd/ef123456') to make large numbers of
        // files more browsable with web/debugging tools like the S3 administration
        // tool.
        $seed = Filesystem::readRandomCharacters(20);
        $parts = array();
        $parts[] = 'phabricator';

        $instance_name = PhabricatorEnv::getEnvConfig('cluster.instance');
        if (strlen($instance_name)) {
            $parts[] = $instance_name;
        }

        $parts[] = substr($seed, 0, 2);
        $parts[] = substr($seed, 2, 2);
        $parts[] = substr($seed, 4);

        $name = implode('/', $parts);

        AphrontWriteGuard::willWrite();
        $profiler = PhutilServiceProfiler::getInstance();
        $call_id = $profiler->beginServiceCall(
            array(
                'type' => 'qiniu',
                'method' => 'putObject',
            ));

        // 生成上传 Token
        $token = $auth->uploadToken($this->getBucketName());

        $region = PhabricatorEnv::getEnvConfig('qiniu-s3.region');

        $zone = new Zone("http://up-{$region}", "http://upload-{$region}");
        // 初始化 UploadManager 对象并进行文件的上传。
        $uploadMgr = new UploadManager(new Config($zone));
        list($ret, $err) = $uploadMgr->put($token, $name, $data);
        $profiler->endServiceCall($call_id, array());

        if ($err !== null) {
            throw new PhabricatorFileUploadException(UPLOAD_ERR_EXTENSION);
        } else {
            if (isset($ret['key'])) {
                return $name;
            } else {
                throw new PhabricatorFileUploadException(UPLOAD_ERR_EXTENSION);
            }
        }
    }


    /**
     * Load a stored blob from Amazon S3.
     * @param $handle
     * @return bool|string
     * @throws \Exception
     */
    public function readFile($handle)
    {
        $obsClient = $this->newS3API();

        $profiler = PhutilServiceProfiler::getInstance();
        $call_id = $profiler->beginServiceCall(
            array(
                'type' => 'qiniu',
                'method' => 'getObject',
            ));


//        $str = '';
//        $resp = $obsClient->getObject([
//            'Bucket' => $this->getBucketName(),
//            'Key' => $handle,
//            'Range' => 'bytes=0-10'
//        ]);
//        $body = (string)$resp['Body'];

        $endpoint = PhabricatorEnv::getEnvConfig('qiniu-s3.endpoint');

        $privateDownloadUrl = $obsClient->privateDownloadUrl($endpoint . $handle);
        $requests_Response = \Requests::get($privateDownloadUrl, [], [
            'timeout' => 30
        ]);
        $body = $requests_Response->body;
//        $result = $obsClient
//            ->setParametersForGetObject($handle)
//            ->resolve();
//
        $profiler->endServiceCall($call_id, array());

        return $body;
    }


    /**
     * Delete a blob from Amazon S3.
     * @param $handle
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    public function deleteFile($handle)
    {
        $obsClient = $this->newS3API();

        AphrontWriteGuard::willWrite();
        $profiler = PhutilServiceProfiler::getInstance();
        $call_id = $profiler->beginServiceCall(
            array(
                'type' => 's3',
                'method' => 'deleteObject',
            ));

        $bucketManager = new BucketManager($obsClient);
        $bucketManager->delete($this->getBucketName(), $handle);

        $profiler->endServiceCall($call_id, array());
    }


    /* -(  Internals  )---------------------------------------------------------- */


    /**
     * Retrieve the S3 bucket name.
     *
     * @task internal
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    private function getBucketName()
    {
        $bucket = PhabricatorEnv::getEnvConfig('qiniu-s3.bucket');
        if (!$bucket) {
            throw new PhabricatorFileStorageConfigurationException(
                \Yii::t("app",
                    "No '{0}' specified!", [
                        'storage.s3.bucket'
                    ]));
        }
        return $bucket;
    }

    /**
     * Create a new S3 API object.
     *
     * @task internal
     * @throws \Exception
     * @return Auth
     */
    private function newS3API()
    {
        $accessKey = PhabricatorEnv::getEnvConfig("qiniu-s3.access.key");
        $secretKey = PhabricatorEnv::getEnvConfig("qiniu-s3.secret.key");


        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        return $auth;
    }
}
