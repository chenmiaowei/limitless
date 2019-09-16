<?php
namespace orangins\lib\infrastructure\log;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\OranginsObject;
use PhutilDeferredLog;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorSSHLog
 * @author 陈妙威
 */
final class PhabricatorSSHLog extends OranginsObject
{

    /**
     * @var
     */
    private static $log;

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public static function getLog()
    {
        if (!self::$log) {
            $path = PhabricatorEnv::getEnvConfig('log.ssh.path');
            $format = PhabricatorEnv::getEnvConfig('log.ssh.format');
            $format = nonempty(
                $format,
                "[%D]\t%p\t%h\t%r\t%s\t%S\t%u\t%C\t%U\t%c\t%T\t%i\t%o");

            // NOTE: Path may be null. We still create the log, it just won't write
            // anywhere.

            $data = array(
                'D' => date('r'),
                'h' => php_uname('n'),
                'p' => getmypid(),
                'e' => time(),
                'I' => PhabricatorEnv::getEnvConfig('cluster.instance'),
            );

            $sudo_user = PhabricatorEnv::getEnvConfig('phd.user');
            if (strlen($sudo_user)) {
                $data['S'] = $sudo_user;
            }

            if (function_exists('posix_geteuid')) {
                $system_uid = posix_geteuid();
                $system_info = posix_getpwuid($system_uid);
                $data['s'] = ArrayHelper::getValue($system_info, 'name');
            }

            $client = getenv('SSH_CLIENT');
            if (strlen($client)) {
                $remote_address = head(explode(' ', $client));
                $data['r'] = $remote_address;
            }

            $log = (new PhutilDeferredLog($path, $format))
                ->setFailQuietly(true)
                ->setData($data);

            self::$log = $log;
        }

        return self::$log;
    }

}
