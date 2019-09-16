<?php

namespace orangins\modules\guides\module;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\request\AphrontRequest;
use orangins\modules\config\models\PhabricatorConfigEntry;
use orangins\modules\guides\view\PhabricatorGuideListView;

/**
 * Class PhabricatorGuideInstallModule
 * @package orangins\modules\guides\module
 * @author 陈妙威
 */
final class PhabricatorGuideInstallModule extends PhabricatorGuideModule
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleKey()
    {
        return 'install';
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getModuleName()
    {
        return \Yii::t("app",'Install Phabricator');
    }

    /**
     * @return int|mixed
     * @author 陈妙威
     */
    public function getModulePosition()
    {
        return 20;
    }

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    public function getIsModuleEnabled()
    {
        if (PhabricatorEnv::getEnvConfig('cluster.instance')) {
            return false;
        }
        return true;
    }

    /**
     * @param AphrontRequest $request
     * @return array|mixed
     * @author 陈妙威
     */
    public function renderModuleStatus(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        $guide_items = new PhabricatorGuideListView();

        $title = \Yii::t("app",'Resolve Setup Issues');
        $issues_resolved = !PhabricatorSetupCheck::getOpenSetupIssueKeys();
        $href = PhabricatorEnv::getURI('/config/issue/');
        if ($issues_resolved) {
            $icon = 'fa-check';
            $icon_bg = 'bg-green';
            $description = \Yii::t("app",
                "You've resolved (or ignored) all outstanding setup issues.");
        } else {
            $icon = 'fa-warning';
            $icon_bg = 'bg-red';
            $description =
                \Yii::t("app",'You have some unresolved setup issues to take care of.');
        }

        $item = (new PhabricatorGuideItemView())
            ->setTitle($title)
            ->setHref($href)
            ->setIcon($icon)
            ->setIconBackground($icon_bg)
            ->setDescription($description);
        $guide_items->addItem($item);

        $configs = (new PhabricatorAuthProviderConfigQuery())
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->execute();

        $title = \Yii::t("app",'Login and Registration');
        $href = PhabricatorEnv::getURI('/auth/');
        $have_auth = (bool)$configs;
        if ($have_auth) {
            $icon = 'fa-check';
            $icon_bg = 'bg-green';
            $description = \Yii::t("app",
                "You've configured at least one authentication provider.");
        } else {
            $icon = 'fa-key';
            $icon_bg = 'bg-sky';
            $description = \Yii::t("app",
                'Authentication providers allow users to register accounts and ' .
                'log in to Phabricator.');
        }

        $item = (new PhabricatorGuideItemView())
            ->setTitle($title)
            ->setHref($href)
            ->setIcon($icon)
            ->setIconBackground($icon_bg)
            ->setDescription($description);
        $guide_items->addItem($item);


        $title = \Yii::t("app",'Configure Phabricator');
        $href = PhabricatorEnv::getURI('/config/');

        // Just load any config value at all; if one exists the install has figured
        // out how to configure things.
        $have_config = (bool)(new PhabricatorConfigEntry())->loadAllWhere(
            '1 = 1 LIMIT 1');

        if ($have_config) {
            $icon = 'fa-check';
            $icon_bg = 'bg-green';
            $description = \Yii::t("app",
                "You've configured at least one setting from the web interface.");
        } else {
            $icon = 'fa-sliders';
            $icon_bg = 'bg-sky';
            $description = \Yii::t("app",
                'Learn how to configure mail and other options in Phabricator.');
        }

        $item = (new PhabricatorGuideItemView())
            ->setTitle($title)
            ->setHref($href)
            ->setIcon($icon)
            ->setIconBackground($icon_bg)
            ->setDescription($description);
        $guide_items->addItem($item);


        $title = \Yii::t("app",'User Account Settings');
        $href = PhabricatorEnv::getURI('/settings/');
        $preferences = PhabricatorUserPreferences::find()
            ->setViewer($viewer)
            ->withUsers(array($viewer))
            ->executeOne();

        $have_settings = ($preferences && $preferences->getPreferences());
        if ($have_settings) {
            $icon = 'fa-check';
            $icon_bg = 'bg-green';
            $description = \Yii::t("app",
                "You've adjusted at least one setting on your account.");
        } else {
            $icon = 'fa-wrench';
            $icon_bg = 'bg-sky';
            $description = \Yii::t("app",
                'Configure account settings for all users, or just yourself');
        }

        $item = (new PhabricatorGuideItemView())
            ->setTitle($title)
            ->setHref($href)
            ->setIcon($icon)
            ->setIconBackground($icon_bg)
            ->setDescription($description);
        $guide_items->addItem($item);


        $title = \Yii::t("app",'Notification Server');
        $href = PhabricatorEnv::getURI('/config/edit/notification.servers/');
        $have_notifications = PhabricatorEnv::getEnvConfig('notification.servers');
        if ($have_notifications) {
            $icon = 'fa-check';
            $icon_bg = 'bg-green';
            $description = \Yii::t("app",
                "You've set up a real-time notification server.");
        } else {
            $icon = 'fa-bell';
            $icon_bg = 'bg-sky';
            $description = \Yii::t("app",
                'Phabricator can deliver notifications in real-time with WebSockets.');
        }

        $item = (new PhabricatorGuideItemView())
            ->setTitle($title)
            ->setHref($href)
            ->setIcon($icon)
            ->setIconBackground($icon_bg)
            ->setDescription($description);

        $guide_items->addItem($item);

        $intro = \Yii::t("app",
            'Phabricator has been successfully installed. These next guides will ' .
            'take you through configuration and new user orientation. ' .
            'These steps are optional, and you can go through them in any order. ' .
            'If you want to get back to this guide later on, you can find it in ' .
            '{icon globe} **Applications** under {icon map-o} **Guides**.');

        $intro = new PHUIRemarkupView($viewer, $intro);

        $intro = (new PHUIDocumentView())
            ->appendChild($intro);

        return array($intro, $guide_items);

    }

}
