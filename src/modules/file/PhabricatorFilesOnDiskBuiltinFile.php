<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/18
 * Time: 11:40 PM
 */

namespace orangins\modules\file;


use FileFinder;
use orangins\lib\infrastructure\util\PhabricatorHash;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Yii;
use Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * Class OranginsFilesOnDiskBuiltinFile
 * @package orangins\lib
 */
class PhabricatorFilesOnDiskBuiltinFile extends PhabricatorFilesBuiltinFile
{
    /**
     * @var
     */
    private $name;

    /**
     * @return mixed
     */
    public function getBuiltinDisplayName()
    {
        return $this->getName();
    }

    /**
     * @return mixed|string
     * @throws \Exception
     */
    public function getBuiltinFileKey()
    {
        $name = $this->getName();
        $desc = "disk(name={$name})";
        $hash = PhabricatorHash::digestToLength($desc, 40);
        return "builtin:{$hash}";
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException
     */
    public function loadBuiltinFileData()
    {
        $name = $this->getName();
        $available = $this->getAllBuiltinFiles();
        if (empty($available[$name])) {
            throw new Exception(Yii::t('app', 'Builtin "{0}" does not exist!', [$name]));
        }
        $file = $available[$name];
        return @file_get_contents($file);
    }

    /**
     * @return array
     * @throws Exception
     * @throws FilesystemException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    private function getAllBuiltinFiles()
    {
        $root = dirname(phutil_get_library_root('orangins'));
        $root = $root . '/resources/builtin/';

        $map = array();
        $list = (new FileFinder($root))
            ->withType('f')
            ->withFollowSymlinks(true)
            ->find();

        foreach ($list as $file) {
            $map[$file] = $root . $file;
        }
        return $map;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return PhabricatorFilesOnDiskBuiltinFile
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}