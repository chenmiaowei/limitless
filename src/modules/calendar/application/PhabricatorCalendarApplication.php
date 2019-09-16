<?php

namespace orangins\modules\calendar\application;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\PhabricatorApplication;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorCalendarApplication
 * @package orangins\modules\cms\calendar
 * @author 陈妙威
 */
final class PhabricatorCalendarApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'calendar';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\calendar\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/calendar/index/query';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app",'Calendar');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return \Yii::t("app",'Upcoming Events');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getFlavorText()
    {
        return \Yii::t("app",'Never miss an episode ever again.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-calendar';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTitleGlyph()
    {
        // Unicode has a calendar character but it's in some distant code plane,
        // use "keyboard" since it looks vaguely similar.
        return "\xE2\x8C\xA8";
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationGroup()
    {
        return self::GROUP_UTILITIES;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isPrototype()
    {
        return true;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getRemarkupRules()
    {
        return array(
            new PhabricatorCalendarRemarkupRule(),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getRoutes()
    {
        return array(
            '/E(?P<id>[1-9]\d*)(?:/(?P<sequence>\d+)/)?'
            => 'PhabricatorCalendarEventViewController',
            '/calendar/' => array(
                '(?:query/(?P<queryKey>[^/]+)/(?:(?P<year>\d+)/' .
                '(?P<month>\d+)/)?(?:(?P<day>\d+)/)?)?'
                => 'PhabricatorCalendarEventListController',
                'event/' => array(
                    $this->getEditRoutePattern('edit/')
                    => 'PhabricatorCalendarEventEditController',
                    'drag/(?P<id>[1-9]\d*)/'
                    => 'PhabricatorCalendarEventDragController',
                    'cancel/(?P<id>[1-9]\d*)/'
                    => 'PhabricatorCalendarEventCancelController',
                    '(?P<action>join|decline|accept)/(?P<id>[1-9]\d*)/'
                    => 'PhabricatorCalendarEventJoinController',
                    'export/(?P<id>[1-9]\d*)/(?P<filename>[^/]*)'
                    => 'PhabricatorCalendarEventExportController',
                    'availability/(?P<id>[1-9]\d*)/(?P<availability>[^/]+)/'
                    => 'PhabricatorCalendarEventAvailabilityController',
                ),
                'export/' => array(
                    $this->getQueryRoutePattern()
                    => 'PhabricatorCalendarExportListController',
                    $this->getEditRoutePattern('edit/')
                    => 'PhabricatorCalendarExportEditController',
                    '(?P<id>[1-9]\d*)/'
                    => 'PhabricatorCalendarExportViewController',
                    'ics/(?P<secretKey>[^/]+)/(?P<filename>[^/]*)'
                    => 'PhabricatorCalendarExportICSController',
                    'disable/(?P<id>[1-9]\d*)/'
                    => 'PhabricatorCalendarExportDisableController',
                ),
                'import/' => array(
                    $this->getQueryRoutePattern()
                    => 'PhabricatorCalendarImportListController',
                    $this->getEditRoutePattern('edit/')
                    => 'PhabricatorCalendarImportEditController',
                    '(?P<id>[1-9]\d*)/'
                    => 'PhabricatorCalendarImportViewController',
                    'disable/(?P<id>[1-9]\d*)/'
                    => 'PhabricatorCalendarImportDisableController',
                    'delete/(?P<id>[1-9]\d*)/'
                    => 'PhabricatorCalendarImportDeleteController',
                    'reload/(?P<id>[1-9]\d*)/'
                    => 'PhabricatorCalendarImportReloadController',
                    'drop/'
                    => 'PhabricatorCalendarImportDropController',
                    'log/' => array(
                        $this->getQueryRoutePattern()
                        => 'PhabricatorCalendarImportLogListController',
                    ),
                ),
            ),
        );
    }

    /**
     * @param PhabricatorUser $viewer
     * @return array
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function getHelpDocumentationArticles(PhabricatorUser $viewer)
    {
        return array(
            array(
                'name' => \Yii::t("app",'Calendar User Guide'),
                'href' => PhabricatorEnv::getDoclink('Calendar User Guide'),
            ),
            array(
                'name' => \Yii::t("app",'Importing Events'),
                'href' => PhabricatorEnv::getDoclink(
                    'Calendar User Guide: Importing Events'),
            ),
            array(
                'name' => \Yii::t("app",'Exporting Events'),
                'href' => PhabricatorEnv::getDoclink(
                    'Calendar User Guide: Exporting Events'),
            ),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getMailCommandObjects()
    {
        return array(
            'event' => array(
                'name' => \Yii::t("app",'Email Commands: Events'),
                'header' => \Yii::t("app",'Interacting with Calendar Events'),
                'object' => new PhabricatorCalendarEvent(),
                'summary' => \Yii::t("app",
                    'This page documents the commands you can use to interact with ' .
                    'events in Calendar. These commands work when creating new tasks ' .
                    'via email and when replying to existing tasks.'),
            ),
        );
    }

//    /**
//     * @return array
//     * @author 陈妙威
//     */
//    protected function getCustomCapabilities()
//    {
//        return array(
//            PhabricatorCalendarEventDefaultViewCapability::CAPABILITY => array(
//                'caption' => \Yii::t("app",'Default view policy for newly created events.'),
//                'template' => PhabricatorCalendarEventPHIDType::TYPECONST,
//                'capability' => PhabricatorPolicyCapability::CAN_VIEW,
//            ),
//            PhabricatorCalendarEventDefaultEditCapability::CAPABILITY => array(
//                'caption' => \Yii::t("app",'Default edit policy for newly created events.'),
//                'template' => PhabricatorCalendarEventPHIDType::TYPECONST,
//                'capability' => PhabricatorPolicyCapability::CAN_EDIT,
//            ),
//        );
//    }
}
