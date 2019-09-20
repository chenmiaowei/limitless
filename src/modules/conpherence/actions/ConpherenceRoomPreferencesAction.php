<?php

namespace orangins\modules\conpherence\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\control\AphrontFormRadioButtonControl;
use orangins\modules\conpherence\models\ConpherenceThread;
use orangins\modules\settings\setting\PhabricatorConpherenceNotificationsSetting;
use yii\helpers\ArrayHelper;

/**
 * Class ConpherenceRoomPreferencesAction
 * @package orangins\modules\conpherence\actions
 * @author 陈妙威
 */
final class ConpherenceRoomPreferencesAction
    extends ConpherenceAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView|Aphront404Response
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $conpherence_id = $request->getURIData('id');

        $conpherence = ConpherenceThread::find()
            ->setViewer($viewer)
            ->withIDs(array($conpherence_id))
            ->executeOne();
        if (!$conpherence) {
            return new Aphront404Response();
        }

        $view_uri = $conpherence->getURI();

        $participant = $conpherence->getParticipantIfExists($viewer->getPHID());
        if (!$participant) {
            if ($viewer->isLoggedIn()) {
                $text = \Yii::t("app",
                    'Notification settings are available after joining the room.');
            } else {
                $text = \Yii::t("app",
                    'Notification settings are available after logging in and joining ' .
                    'the room.');
            }
            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'Room Preferences'))
                ->addCancelButton($view_uri)
                ->appendParagraph($text);
        }

        // Save the data and redirect
        if ($request->isFormPost()) {
            $notifications = $request->getStr('notifications');
            $sounds = $request->getArr('sounds');
            $theme = $request->getStr('theme');

            $participant->setSettings(array(
                'notifications' => $notifications,
                'sounds' => $sounds,
                'theme' => $theme,
            ));
            $participant->save();

            return (new AphrontRedirectResponse())
                ->setURI($view_uri);
        }

        $notification_key = PhabricatorConpherenceNotificationsSetting::SETTINGKEY;
        $notification_default = $viewer->getUserSetting($notification_key);

        $sound_key = PhabricatorConpherenceSoundSetting::SETTINGKEY;
        $sound_default = $viewer->getUserSetting($sound_key);

        $settings = $participant->getSettings();
        $notifications = ArrayHelper::getValue($settings, 'notifications', $notification_default);
        $theme = ArrayHelper::getValue($settings, 'theme', ConpherenceRoomSettings::COLOR_LIGHT);

        $sounds = ArrayHelper::getValue($settings, 'sounds', array());
        $map = PhabricatorConpherenceSoundSetting::getDefaultSound($sound_default);
        $receive = ArrayHelper::getValue($sounds,
            ConpherenceRoomSettings::SOUND_RECEIVE,
            $map[ConpherenceRoomSettings::SOUND_RECEIVE]);
        $mention = ArrayHelper::getValue($sounds,
            ConpherenceRoomSettings::SOUND_MENTION,
            $map[ConpherenceRoomSettings::SOUND_MENTION]);

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->appendControl(
                (new AphrontFormRadioButtonControl())
                    ->setLabel(\Yii::t("app", 'Notify'))
                    ->addButton(
                        PhabricatorConpherenceNotificationsSetting::VALUE_CONPHERENCE_EMAIL,
                        PhabricatorConpherenceNotificationsSetting::getSettingLabel(
                            PhabricatorConpherenceNotificationsSetting::VALUE_CONPHERENCE_EMAIL),
                        '')
                    ->addButton(
                        PhabricatorConpherenceNotificationsSetting::VALUE_CONPHERENCE_NOTIFY,
                        PhabricatorConpherenceNotificationsSetting::getSettingLabel(
                            PhabricatorConpherenceNotificationsSetting::VALUE_CONPHERENCE_NOTIFY),
                        '')
                    ->setName('notifications')
                    ->setValue($notifications))
            ->appendChild(
                (new AphrontFormSelectControl())
                    ->setLabel(\Yii::t("app", 'New Message'))
                    ->setName('sounds[' . ConpherenceRoomSettings::SOUND_RECEIVE . ']')
                    ->setOptions(ConpherenceRoomSettings::getDropdownSoundMap())
                    ->setValue($receive))
            ->appendChild(
                (new AphrontFormSelectControl())
                    ->setLabel(\Yii::t("app", 'Theme'))
                    ->setName('theme')
                    ->setOptions(ConpherenceRoomSettings::getThemeMap())
                    ->setValue($theme));

        return $this->newDialog()
            ->setTitle(\Yii::t("app", 'Room Preferences'))
            ->appendForm($form)
            ->addCancelButton($view_uri)
            ->addSubmitButton(\Yii::t("app", 'Save'));
    }

}
