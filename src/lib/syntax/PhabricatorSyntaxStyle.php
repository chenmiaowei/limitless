<?php

namespace orangins\lib\syntax;

use orangins\lib\OranginsObject;
use PhutilClassMapQuery;
use PhutilSortVector;

/**
 * Class PhabricatorSyntaxStyle
 * @package orangins\lib\syntax
 * @author 陈妙威
 */
abstract class PhabricatorSyntaxStyle extends OranginsObject
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getStyleName();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getStyleMap();

    /**
     * @return string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final public function getStyleOrder()
    {
        return (string)(new PhutilSortVector())
            ->addInt($this->isDefaultStyle() ? 0 : 1)
            ->addString($this->getStyleName());
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final public function getSyntaxStyleKey()
    {
        return $this->getPhobjectClassConstant('STYLEKEY');
    }

    /**
     * @return bool
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final public function isDefaultStyle()
    {
        return ($this->getSyntaxStyleKey() == 'default');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public static function getAllStyles()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getSyntaxStyleKey')
            ->setSortMethod('getStyleName')
            ->execute();
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    final public function getRemarkupStyleMap()
    {
        $map = array(
            'rbw_r' => 'color: red',
            'rbw_o' => 'color: orange',
            'rbw_y' => 'color: yellow',
            'rbw_g' => 'color: green',
            'rbw_b' => 'color: blue',
            'rbw_i' => 'color: indigo',
            'rbw_v' => 'color: violet',
        );

        return $map + $this->getStyleMap();
    }

}
