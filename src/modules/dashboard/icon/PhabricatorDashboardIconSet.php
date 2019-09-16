<?php

namespace orangins\modules\dashboard\icon;

use orangins\modules\file\iconset\PhabricatorIconSet;
use orangins\modules\file\iconset\PhabricatorIconSetIcon;

/**
 * Class PhabricatorDashboardIconSet
 * @author 陈妙威
 */
final class PhabricatorDashboardIconSet
    extends PhabricatorIconSet
{

    /**
     *
     */
    const ICONSETKEY = 'dashboards';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSelectIconTitleText()
    {
        return \Yii::t("app", 'Choose Dashboard Icon');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function newIcons()
    {
        $map = array(
            'fa-home' => \Yii::t("app", 'Home'),
            'fa-dashboard' => \Yii::t("app", 'Dashboard'),
            'fa-th-large' => \Yii::t("app", 'Blocks'),
            'fa-columns' => \Yii::t("app", 'Columns'),
            'fa-bookmark' => \Yii::t("app", 'Page Saver'),

            'fa-book' => \Yii::t("app", 'Knowledge'),
            'fa-bomb' => \Yii::t("app", 'Kaboom'),
            'fa-pie-chart' => \Yii::t("app", 'Apple Blueberry'),
            'fa-bar-chart' => \Yii::t("app", 'Serious Business'),
            'fa-briefcase' => \Yii::t("app", 'Project'),

            'fa-bell' => \Yii::t("app", 'Ding Ding'),
            'fa-credit-card' => \Yii::t("app", 'Plastic Debt'),
            'fa-code' => \Yii::t("app", 'PHP is Life'),
            'fa-sticky-note' => \Yii::t("app", 'To Self'),
            'fa-newspaper-o' => \Yii::t("app", 'Stay Woke'),

            'fa-server' => \Yii::t("app", 'Metallica'),
            'fa-hashtag' => \Yii::t("app", 'Corned Beef'),
            'fa-anchor' => \Yii::t("app", 'Tasks'),
            'fa-calendar' => \Yii::t("app", 'Calendar'),
            'fa-compass' => \Yii::t("app", 'Wayfinding'),

            'fa-futbol-o' => \Yii::t("app", 'Sports'),
            'fa-flag' => \Yii::t("app", 'Flag'),
            'fa-ship' => \Yii::t("app", 'Water Vessel'),
            'fa-feed' => \Yii::t("app", 'Wireless'),
            'fa-bullhorn' => \Yii::t("app", 'Announcement'),

        );

        $icons = array();
        foreach ($map as $key => $label) {
            $icons[] = (new PhabricatorIconSetIcon())
                ->setKey($key)
                ->setLabel($label);
        }
        return $icons;
    }
}
