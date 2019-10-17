<?php

namespace orangins\modules\badges\constants;

use orangins\lib\OranginsObject;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorBadgesQuality
 * @package orangins\modules\badges\constants
 * @author 陈妙威
 */
final class PhabricatorBadgesQuality
    extends OranginsObject
{

    /**
     *
     */
    const POOR = 140;
    /**
     *
     */
    const COMMON = 120;
    /**
     *
     */
    const UNCOMMON = 100;
    /**
     *
     */
    const RARE = 80;
    /**
     *
     */
    const EPIC = 60;
    /**
     *
     */
    const LEGENDARY = 40;
    /**
     *
     */
    const HEIRLOOM = 20;

    /**
     *
     */
    const DEFAULT_QUALITY = 140;

    /**
     * @param $quality
     * @return object
     * @author 陈妙威
     */
    public static function getQualityName($quality)
    {
        $map = self::getQualityDictionary($quality);
        $default = pht('Unknown Quality ("%s")', $quality);
        return ArrayHelper::getValue($map, 'name', $default);
    }

    /**
     * @param $quality
     * @return object
     * @author 陈妙威
     */
    public static function getQualityColor($quality)
    {
        $map = self::getQualityDictionary($quality);
        $default = 'grey';
        return ArrayHelper::getValue($map, 'color', $default);
    }

    /**
     * @param $quality
     * @return object
     * @author 陈妙威
     */
    private static function getQualityDictionary($quality)
    {
        $map = self::getQualityMap();
        $default = array();
        return idx($map, $quality, $default);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getQualityMap()
    {
        return array(
            self::POOR => array(
                'rarity' => 140,
                'name' => pht('Poor'),
                'color' => 'grey',
            ),
            self::COMMON => array(
                'rarity' => 120,
                'name' => pht('Common'),
                'color' => 'white',
            ),
            self::UNCOMMON => array(
                'rarity' => 100,
                'name' => pht('Uncommon'),
                'color' => 'green',
            ),
            self::RARE => array(
                'rarity' => 80,
                'name' => pht('Rare'),
                'color' => 'blue',
            ),
            self::EPIC => array(
                'rarity' => 60,
                'name' => pht('Epic'),
                'color' => 'indigo',
            ),
            self::LEGENDARY => array(
                'rarity' => 40,
                'name' => pht('Legendary'),
                'color' => 'orange',
            ),
            self::HEIRLOOM => array(
                'rarity' => 20,
                'name' => pht('Heirloom'),
                'color' => 'yellow',
            ),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getDropdownQualityMap()
    {
        $map = self::getQualityMap();
        return ipull($map, 'name');
    }
}
