<?php

namespace orangins\lib\infrastructure\util;

use Filesystem;
use orangins\lib\OranginsObject;
use TempFile;
use Exception;

/**
 * Class PhabricatorSSHKeyGenerator
 * @package orangins\lib\infrastructure\util
 * @author 陈妙威
 */
final class PhabricatorSSHKeyGenerator extends OranginsObject
{

    /**
     * @throws Exception
     * @author 陈妙威
     */
    public static function assertCanGenerateKeypair()
    {
        $binary = 'ssh-keygen';
        if (!Filesystem::resolveBinary($binary)) {
            throw new Exception(
                \Yii::t("app",
                    'Can not generate keys: unable to find "{0}" in PATH!',
                    [
                        $binary
                    ]));
        }
    }

    /**
     * @return array
     * @throws Exception
     * @throws \FilesystemException
     * @author 陈妙威
     */
    public static function generateKeypair()
    {
        self::assertCanGenerateKeypair();

        $tempfile = new TempFile();
        $keyfile = dirname($tempfile) . DIRECTORY_SEPARATOR . 'keytext';

        execx(
            'ssh-keygen -t rsa -N %s -f %s',
            '',
            $keyfile);

        $public_key = Filesystem::readFile($keyfile . '.pub');
        $private_key = Filesystem::readFile($keyfile);

        return array($public_key, $private_key);
    }
}
