<?php

namespace orangins\modules\auth\constants;

use orangins\lib\OranginsObject;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorAuthFactorProviderStatus
 * @package orangins\modules\auth\constants
 * @author 陈妙威
 */
final class PhabricatorAuthFactorProviderStatus
    extends OranginsObject
{

    /**
     * @var
     */
    private $key;
    /**
     * @var array
     */
    private $spec = array();

    /**
     *
     */
    const STATUS_ACTIVE = 'active';
    /**
     *
     */
    const STATUS_DEPRECATED = 'deprecated';
    /**
     *
     */
    const STATUS_DISABLED = 'disabled';

    /**
     * @param $status
     * @return PhabricatorAuthFactorProviderStatus
     * @author 陈妙威
     */
    public static function newForStatus($status)
    {
        $result = new self();

        $result->key = $status;
        $result->spec = self::newSpecification($status);

        return $result;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getName()
    {
        return ArrayHelper::getValue($this->spec, 'name', $this->key);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getStatusHeaderIcon()
    {
        return ArrayHelper::getValue($this->spec, 'header.icon');
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getStatusHeaderColor()
    {
        return ArrayHelper::getValue($this->spec, 'header.color');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isActive()
    {
        return ($this->key === self::STATUS_ACTIVE);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getListIcon()
    {
        return ArrayHelper::getValue($this->spec, 'list.icon');
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getListColor()
    {
        return ArrayHelper::getValue($this->spec, 'list.color');
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getFactorIcon()
    {
        return ArrayHelper::getValue($this->spec, 'factor.icon');
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getFactorColor()
    {
        return ArrayHelper::getValue($this->spec, 'factor.color');
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getOrder()
    {
        return ArrayHelper::getValue($this->spec, 'order', 0);
    }

    /**
     * @return \dict
     * @author 陈妙威
     */
    public static function getMap()
    {
        $specs = self::newSpecifications();
        return ipull($specs, 'name');
    }

    /**
     * @param $key
     * @return object
     * @author 陈妙威
     */
    private static function newSpecification($key)
    {
        $specs = self::newSpecifications();
        return ArrayHelper::getValue($specs, $key, array());
    }

    /**
     * @return array
     * @author 陈妙威
     */
    private static function newSpecifications()
    {
        return array(
            self::STATUS_ACTIVE => array(
                'name' => pht('Active'),
                'header.icon' => 'fa-check',
                'header.color' => null,
                'list.icon' => null,
                'list.color' => null,
                'factor.icon' => 'fa-check',
                'factor.color' => 'green',
                'order' => 1,
            ),
            self::STATUS_DEPRECATED => array(
                'name' => pht('Deprecated'),
                'header.icon' => 'fa-ban',
                'header.color' => 'indigo',
                'list.icon' => 'fa-ban',
                'list.color' => 'indigo',
                'factor.icon' => 'fa-ban',
                'factor.color' => 'indigo',
                'order' => 2,
            ),
            self::STATUS_DISABLED => array(
                'name' => pht('Disabled'),
                'header.icon' => 'fa-times',
                'header.color' => 'red',
                'list.icon' => 'fa-times',
                'list.color' => 'red',
                'factor.icon' => 'fa-times',
                'factor.color' => 'grey',
                'order' => 3,
            ),
        );
    }

}
