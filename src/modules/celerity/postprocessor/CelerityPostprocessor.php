<?php

namespace orangins\modules\celerity\postprocessor;

use orangins\lib\OranginsObject;
use PhutilClassMapQuery;
use yii\helpers\ArrayHelper;

/**
 * Class CelerityPostprocessor
 * @package orangins\modules\celerity\postprocessor
 * @author 陈妙威
 */
abstract class CelerityPostprocessor
    extends OranginsObject
{

    /**
     * @var
     */
    private $default;

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getPostprocessorKey();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getPostprocessorName();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function buildVariables();

    /**
     * @return CelerityDefaultPostprocessor
     * @author 陈妙威
     */
    public function buildDefaultPostprocessor()
    {
        return new CelerityDefaultPostprocessor();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getVariables()
    {
        $variables = $this->buildVariables();

        $default = $this->getDefault();
        if ($default) {
            $variables += $default->getVariables();
        }

        return $variables;
    }

    /**
     * @return CelerityDefaultPostprocessor
     * @author 陈妙威
     */
    final public function getDefault()
    {
        if ($this->default === null) {
            $this->default = $this->buildDefaultPostprocessor();
        }
        return $this->default;
    }

    /**
     * @param $key
     * @return mixed
     * @author 陈妙威
     */
    final public static function getPostprocessor($key)
    {
        return ArrayHelper::getValue(self::getAllPostprocessors(), $key);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public static function getAllPostprocessors()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getPostprocessorKey')
            ->execute();
    }

}
