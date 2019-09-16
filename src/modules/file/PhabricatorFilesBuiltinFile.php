<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/18
 * Time: 11:39 PM
 */
namespace orangins\modules\file;

use orangins\lib\OranginsObject;

/**
 * Class OranginsFilesBuiltinFile
 * @package orangins\lib
 */
abstract class PhabricatorFilesBuiltinFile extends OranginsObject
{
    /**
     * @return mixed
     */
    abstract public function getBuiltinFileKey();

    /**
     * @return mixed
     */
    abstract public function getBuiltinDisplayName();

    /**
     * @return mixed
     */
    abstract public function loadBuiltinFileData();
}