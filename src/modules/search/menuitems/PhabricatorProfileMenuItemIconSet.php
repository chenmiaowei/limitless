<?php

namespace orangins\modules\search\menuitems;

use orangins\modules\file\iconset\PhabricatorIconSet;
use orangins\modules\file\iconset\PhabricatorIconSetIcon;

/**
 * Class PhabricatorProfileMenuItemIconSet
 * @package orangins\modules\search\menuitems
 * @author 陈妙威
 */
final class PhabricatorProfileMenuItemIconSet extends PhabricatorIconSet
{

    /**
     *
     */
    const ICONSETKEY = 'profilemenuitem';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSelectIconTitleText()
    {
        return \Yii::t("app",'Choose Item Icon');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function newIcons()
    {
        $list = array(
            array(
                'key' => 'link',
                'icon' => 'fa-link',
                'name' => \Yii::t("app",'Link'),
            ),
            array(
                'key' => 'maniphest',
                'icon' => 'fa-anchor',
                'name' => \Yii::t("app",'Maniphest'),
            ),
            array(
                'key' => 'feed',
                'icon' => 'fa-newspaper-o',
                'name' => \Yii::t("app",'Feed'),
            ),
            array(
                'key' => 'phriction',
                'icon' => 'fa-book',
                'name' => \Yii::t("app",'Phriction'),
            ),
            array(
                'key' => 'conpherence',
                'icon' => 'fa-comments',
                'name' => \Yii::t("app",'Conpherence'),
            ),
            array(
                'key' => 'differential',
                'icon' => 'fa-cog',
                'name' => \Yii::t("app",'Differential'),
            ),
            array(
                'key' => 'diffusion',
                'icon' => 'fa-code',
                'name' => \Yii::t("app",'Diffusion'),
            ),
            array(
                'key' => 'calendar',
                'icon' => 'fa-calendar',
                'name' => \Yii::t("app",'Calendar'),
            ),
            array(
                'key' => 'create',
                'icon' => 'fa-plus',
                'name' => \Yii::t("app",'Create'),
            ),
        );

        $icons = array();
        foreach ($list as $spec) {
            $icons[] = (new PhabricatorIconSetIcon())
                ->setKey($spec['key'])
                ->setIcon($spec['icon'])
                ->setLabel($spec['name']);
        }

        return $icons;
    }

}
