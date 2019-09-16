<?php

namespace orangins\lib\infrastructure\log;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\OranginsObject;
use PhutilDeferredLog;

/**
 * Class PhabricatorAccessLog
 * @package orangins\lib\infrastructure\log
 * @author 陈妙威
 */
final class PhabricatorAccessLog extends OranginsObject
{

    /**
     * @var PhutilDeferredLog
     */
    private static $log;

    /**
     * @author 陈妙威
     */
    public static function init()
    {
        // NOTE: This currently has no effect, but some day we may reuse PHP
        // interpreters to run multiple requests. If we do, it has the effect of
        // throwing away the old log.
        self::$log = null;
    }

    /**
     * @return PhutilDeferredLog
     * @throws \Exception
     * @author 陈妙威
     */
    public static function getLog()
    {
        if (!self::$log) {
            $path = PhabricatorEnv::getEnvConfig('log.access.path');
            $format = PhabricatorEnv::getEnvConfig('log.access.format');
            $format = nonempty(
                $format,
                "[%D]\t%p\t%h\t%r\t%u\t%C\t%m\t%a\t%U\t%R\t%c\t%T");

            // NOTE: Path may be null. We still create the log, it just won't write
            // anywhere.

            $log = (new PhutilDeferredLog($path, $format))
                ->setFailQuietly(true)
                ->setData(
                    array(
                        'D' => date('Y-m-d H:i:s O'),
                        'h' => php_uname('n'),
                        'p' => getmypid(),
                        'e' => time(),
                        'I' => PhabricatorEnv::getEnvConfig('cluster.instance'),
                    ));

            self::$log = $log;
        }

        return self::$log;
    }

}
