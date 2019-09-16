<?php

namespace orangins\modules\people\iconset;

use orangins\lib\helpers\OranginsUtil;
use orangins\modules\file\iconset\PhabricatorIconSet;
use orangins\modules\file\iconset\PhabricatorIconSetIcon;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorPeopleIconSet
 * @package orangins\modules\people\iconset
 * @author 陈妙威
 */
final class PhabricatorPeopleIconSet extends PhabricatorIconSet
{

    /**
     *
     */
    const ICONSETKEY = 'people';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSelectIconTitleText()
    {
        return \Yii::t("app", 'Choose User Icon');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function newIcons()
    {
        $specifications = self::getIconSpecifications();

        $icons = array();
        foreach ($specifications as $spec) {
            $icons[] = (new PhabricatorIconSetIcon())
                ->setKey($spec['key'])
                ->setIcon($spec['icon'])
                ->setLabel($spec['name']);
        }

        return $icons;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public static function getDefaultIconKey()
    {
        $specifications = self::getIconSpecifications();

        foreach ($specifications as $spec) {
            if (ArrayHelper::getValue($spec, 'default')) {
                return $spec['key'];
            }
        }

        return null;
    }

    /**
     * @param $key
     * @return mixed
     * @author 陈妙威
     */
    public static function getIconIcon($key)
    {
        $specifications = self::getIconSpecifications();
        $map = ipull($specifications, 'icon', 'key');
        return ArrayHelper::getValue($map, $key);
    }

    /**
     * @param $key
     * @return mixed
     * @author 陈妙威
     */
    public static function getIconName($key)
    {
        $specifications = self::getIconSpecifications();
        $map = ipull($specifications, 'name', 'key');
        return ArrayHelper::getValue($map, $key);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    private static function getIconSpecifications()
    {
        return self::getDefaultSpecifications();
    }

    /**
     * @return array
     * @author 陈妙威
     */
    private static function getDefaultSpecifications()
    {
        return array(
            array(
                'key' => 'person',
                'icon' => 'fa-user',
                'name' => \Yii::t("app", 'User'),
                'default' => true,
            ),
            array(
                'key' => 'engineering',
                'icon' => 'fa-code',
                'name' => \Yii::t("app", 'Engineering'),
            ),
            array(
                'key' => 'operations',
                'icon' => 'fa-space-shuttle',
                'name' => \Yii::t("app", 'Operations'),
            ),
            array(
                'key' => 'resources',
                'icon' => 'fa-heart',
                'name' => \Yii::t("app", 'Resources'),
            ),
            array(
                'key' => 'camera',
                'icon' => 'fa-camera-retro',
                'name' => \Yii::t("app", 'Design'),
            ),
            array(
                'key' => 'music',
                'icon' => 'fa-headphones',
                'name' => \Yii::t("app", 'Musician'),
            ),
            array(
                'key' => 'spy',
                'icon' => 'fa-user-secret',
                'name' => \Yii::t("app", 'Spy'),
            ),
            array(
                'key' => 'android',
                'icon' => 'fa-android',
                'name' => \Yii::t("app", 'Bot'),
            ),
            array(
                'key' => 'relationships',
                'icon' => 'fa-glass',
                'name' => \Yii::t("app", 'Relationships'),
            ),
            array(
                'key' => 'administration',
                'icon' => 'fa-fax',
                'name' => \Yii::t("app", 'Administration'),
            ),
            array(
                'key' => 'security',
                'icon' => 'fa-shield',
                'name' => \Yii::t("app", 'Security'),
            ),
            array(
                'key' => 'logistics',
                'icon' => 'fa-truck',
                'name' => \Yii::t("app", 'Logistics'),
            ),
            array(
                'key' => 'research',
                'icon' => 'fa-flask',
                'name' => \Yii::t("app", 'Research'),
            ),
            array(
                'key' => 'analysis',
                'icon' => 'fa-bar-chart-o',
                'name' => \Yii::t("app", 'Analysis'),
            ),
            array(
                'key' => 'executive',
                'icon' => 'fa-angle-double-up',
                'name' => \Yii::t("app", 'Executive'),
            ),
            array(
                'key' => 'animal',
                'icon' => 'fa-paw',
                'name' => \Yii::t("app", 'Animal'),
            ),
        );
    }

}
