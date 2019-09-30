<?php

namespace orangins\modules\file\engine;

use AphrontWriteGuard;
use Filesystem;
use orangins\lib\env\PhabricatorEnv;
use orangins\modules\file\exception\PhabricatorFileStorageConfigurationException;
use OSS\OssClient;
use PhutilServiceProfiler;

/**
 * Amazon S3 file storage engine. This engine scales well but is relatively
 * high-latency since data has to be pulled off S3.
 *
 * @task internal Internals
 */
final class PhabricatorAliyunFileStorageEngine
    extends PhabricatorFileStorageEngine
{


    /* -(  Engine Metadata  )---------------------------------------------------- */


    /**
     * This engine identifies as `amazon-s3`.
     */
    public function getEngineIdentifier()
    {
        return 'aliyun-s3';
    }

    /**
     * @return float|int
     * @author 陈妙威
     */
    public function getEnginePriority()
    {
        return 100;
    }

    /**
     * @return bool
     * @throws \Exception
     * @author 陈妙威
     */
    public function canWriteFiles()
    {
        $bucket = PhabricatorEnv::getEnvConfig('aliyun-s3.bucket');
        $access_key = PhabricatorEnv::getEnvConfig('aliyun-s3.access.key.id');
        $secret_key = PhabricatorEnv::getEnvConfig('aliyun-s3.secret.access.key');
        $endpoint = PhabricatorEnv::getEnvConfig('aliyun-s3.endpoint');
//        $region = PhabricatorEnv::getEnvConfig('amazon-s3.region');

        return (strlen($bucket) &&
            strlen($access_key) &&
            strlen($secret_key) &&
            strlen($endpoint)
//            &&  strlen($region)
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
        $obsClient = $this->newS3API();

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
                'type' => 's3',
                'method' => 'putObject',
            ));

        $obsClient->putObject($this->getBucketName(), $name, $data);


//        $s3
//            ->setParametersForPutObject($name, $data)
//            ->resolve();

        $profiler->endServiceCall($call_id, array());

        return $name;
    }


    /**
     * Load a stored blob from Amazon S3.
     * @param $handle
     * @return
     * @throws \yii\base\Exception
     */
    public function readFile($handle)
    {
        $obsClient = $this->newS3API();

        $profiler = PhutilServiceProfiler::getInstance();
        $call_id = $profiler->beginServiceCall(
            array(
                'type' => 's3',
                'method' => 'getObject',
            ));


        $body = $obsClient->getObject($this->getBucketName(), $handle);
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

        $obsClient->deleteObjects($this->getBucketName(), [
            $handle
        ]);

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
        $bucket = PhabricatorEnv::getEnvConfig('aliyun-s3.bucket');
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
     * @return OssClient
     */
    private function newS3API()
    {
        $access_key = PhabricatorEnv::getEnvConfig('aliyun-s3.access.key.id');
        $secret_key = PhabricatorEnv::getEnvConfig('aliyun-s3.secret.access.key');
        $internal_endpoint = PhabricatorEnv::getEnvConfig('aliyun-s3.internal.endpoint');

        $ossClient = new OssClient($access_key, $secret_key, $internal_endpoint);
        return $ossClient;
    }
}
