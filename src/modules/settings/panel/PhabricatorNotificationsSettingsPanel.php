<?php

namespace orangins\modules\settings\panel;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\PhabricatorApplication;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\modules\notification\client\PhabricatorNotificationServerRef;
use orangins\modules\settings\panelgroup\PhabricatorSettingsApplicationsPanelGroup;
use orangins\modules\settings\setting\PhabricatorNotificationsSetting;

/**
 * Class PhabricatorNotificationsSettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorNotificationsSettingsPanel
    extends PhabricatorSettingsPanel
{

    /**
     * @return bool
     * @throws \Exception
     * @author 陈妙威
     */
    public function isEnabled()
    {
        $servers = PhabricatorNotificationServerRef::getEnabledAdminServers();
        if (!$servers) {
            return false;
        }

        return PhabricatorApplication::isClassInstalled(
            'PhabricatorNotificationsApplication');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelKey()
    {
        return 'notifications';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return \Yii::t("app", 'Notifications');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelGroupKey()
    {
        return PhabricatorSettingsApplicationsPanelGroup::PANELGROUPKEY;
    }

    /**
     * @param AphrontRequest $request
     * @return array|AphrontRedirectResponse
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function processRequest(AphrontRequest $request)
    {
        $viewer = $this->getViewer();
        $preferences = $this->getPreferences();

        $notifications_key = PhabricatorNotificationsSetting::SETTINGKEY;
        $notifications_value = $preferences->getSettingValue($notifications_key);

        if ($request->isFormPost()) {

            $this->writeSetting(
                $preferences,
                $notifications_key,
                $request->getInt($notifications_key));

            return (new AphrontRedirectResponse())
                ->setURI($this->getPanelURI('?saved=true'));
        }

        $title = \Yii::t("app", 'Notifications');
        $control_id = JavelinHtml::generateUniqueNodeId();
        $status_id = JavelinHtml::generateUniqueNodeId();
        $browser_status_id = JavelinHtml::generateUniqueNodeId();
        $cancel_ask = \Yii::t("app",
            'The dialog asking for permission to send desktop notifications was ' .
            'closed without granting permission. Only application notifications ' .
            'will be sent.');
        $accept_ask = \Yii::t("app",
            'Click "Save Preference" to persist these changes.');
        $reject_ask = \Yii::t("app",
            'Permission for desktop notifications was denied. Only application ' .
            'notifications will be sent.');
        $no_support = \Yii::t("app",
            'This web browser does not support desktop notifications. Only ' .
            'application notifications will be sent for this browser regardless of ' .
            'this preference.');
        $default_status = phutil_tag(
            'span',
            array(),
            array(
                \Yii::t("app", 'This browser has not yet granted permission to send desktop ' .
                    'notifications for this Phabricator instance.'),
                phutil_tag('br'),
                phutil_tag('br'),
                JavelinHtml::phutil_tag(
                    'button',
                    array(
                        'sigil' => 'desktop-notifications-permission-button',
                        'class' => 'green',
                    ),
                    \Yii::t("app", 'Grant Permission')),
            ));
        $granted_status = phutil_tag(
            'span',
            array(),
            \Yii::t("app", 'This browser has been granted permission to send desktop ' .
                'notifications for this Phabricator instance.'));
        $denied_status = phutil_tag(
            'span',
            array(),
            \Yii::t("app", 'This browser has denied permission to send desktop notifications ' .
                'for this Phabricator instance. Consult your browser settings / ' .
                'documentation to figure out how to clear this setting, do so, ' .
                'and then re-visit this page to grant permission.'));

        $message_id = JavelinHtml::generateUniqueNodeId();

        $message_container = phutil_tag(
            'span',
            array(
                'id' => $message_id,
            ));

        $saved_box = null;
        if ($request->getBool('saved')) {
            $saved_box = (new PHUIInfoView())
                ->setSeverity(PHUIInfoView::SEVERITY_NOTICE)
                ->appendChild(\Yii::t("app", 'Changes saved.'));
        }

        $status_box = (new PHUIInfoView())
            ->setSeverity(PHUIInfoView::SEVERITY_NOTICE)
            ->setID($status_id)
            ->setIsHidden(true)
            ->appendChild($message_container);

        $status_box = (new PHUIBoxView())
            ->addClass('mll mlr')
            ->appendChild($status_box);

        $control_config = array(
            'controlID' => $control_id,
            'statusID' => $status_id,
            'messageID' => $message_id,
            'browserStatusID' => $browser_status_id,
            'defaultMode' => 0,
            'desktop' => 1,
            'desktopOnly' => 2,
            'cancelAsk' => $cancel_ask,
            'grantedAsk' => $accept_ask,
            'deniedAsk' => $reject_ask,
            'defaultStatus' => $default_status,
            'deniedStatus' => $denied_status,
            'grantedStatus' => $granted_status,
            'noSupport' => $no_support,
        );

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->appendChild(
                (new AphrontFormSelectControl())
                    ->setLabel($title)
                    ->setControlID($control_id)
                    ->setName($notifications_key)
                    ->setValue($notifications_value)
                    ->setOptions(PhabricatorNotificationsSetting::getOptionsMap())
                    ->setCaption(
                        \Yii::t("app",
                            'Phabricator can send real-time notifications to your web browser ' .
                            'or to your desktop. Select where you\'d want to receive these ' .
                            'real-time updates.'))
                    ->initBehavior(
                        'desktop-notifications-control',
                        $control_config))
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->setValue(\Yii::t("app", 'Save Preference')));

        $button = (new PHUIButtonView())
            ->setTag('a')
            ->setIcon('fa-send-o')
            ->setWorkflow(true)
            ->setText(\Yii::t("app", 'Send Test Notification'))
            ->setHref('/notification/test/')
            ->setColor(PHUIButtonView::COLOR_GREY);

        $form_content = array($saved_box, $status_box, $form);
        $form_box = $this->newBox(
            \Yii::t("app", 'Notifications'), $form_content, array($button));

        $browser_status_box = (new PHUIInfoView())
            ->setID($browser_status_id)
            ->setSeverity(PHUIInfoView::SEVERITY_NOTICE)
            ->setIsHidden(true)
            ->appendChild($default_status);

        return array(
            $form_box,
            $browser_status_box,
        );
    }

}
