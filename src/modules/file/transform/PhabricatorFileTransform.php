<?php

namespace orangins\modules\file\transform;

use orangins\lib\OranginsObject;
use orangins\modules\file\models\PhabricatorFile;
use PhutilClassMapQuery;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorFileTransform
 * @package orangins\modules\file\transform
 * @author 陈妙威
 */
abstract class PhabricatorFileTransform extends OranginsObject
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getTransformName();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getTransformKey();

    /**
     * @param PhabricatorFile $file
     * @return mixed
     * @author 陈妙威
     */
    abstract public function canApplyTransform(PhabricatorFile $file);

    /**
     * @param PhabricatorFile $file
     * @return mixed
     * @author 陈妙威
     */
    abstract public function applyTransform(PhabricatorFile $file);

    /**
     * @param PhabricatorFile $file
     * @return null
     * @author 陈妙威
     */
    public function getDefaultTransform(PhabricatorFile $file)
    {
        return null;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function generateTransforms()
    {
        return array($this);
    }

    /**
     * @param PhabricatorFile $file
     * @return mixed|null
     * @author 陈妙威
     */
    public function executeTransform(PhabricatorFile $file)
    {
        if ($this->canApplyTransform($file)) {
            try {
                return $this->applyTransform($file);
            } catch (Exception $ex) {
                // Ignore.
            }
        }

        return $this->getDefaultTransform($file);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public static function getAllTransforms()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setExpandMethod('generateTransforms')
            ->setUniqueMethod('getTransformKey')
            ->execute();
    }

    /**
     * @param $key
     * @return PhabricatorFileTransform
     * @throws Exception
     * @author 陈妙威
     */
    public static function getTransformByKey($key)
    {
        $all = self::getAllTransforms();

        $xform = ArrayHelper::getValue($all, $key);
        if (!$xform) {
            throw new Exception(
                \Yii::t("app",
                    'No file transform with key "%s" exists.',
                    $key));
        }

        return $xform;
    }

}
