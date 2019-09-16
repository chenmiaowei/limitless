<?php

namespace orangins\modules\settings\actions;

use DateTime;
use DateTimeZone;
use orangins\lib\actions\PhabricatorAction;
use orangins\lib\time\PhabricatorTime;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormMarkupControl;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\modules\settings\editors\PhabricatorUserPreferencesEditor;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use orangins\modules\settings\setting\PhabricatorTimezoneIgnoreOffsetSetting;
use orangins\modules\settings\setting\PhabricatorTimezoneSetting;

/**
 * Class PhabricatorSettingsTimezoneAction
 * @package orangins\modules\settings\actions
 * @author 陈妙威
 */
final class PhabricatorSettingsTimezoneAction
    extends PhabricatorAction
{

    /**
     * @return \orangins\lib\view\AphrontDialogView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $client_offset = $request->getURIData('offset');
        $client_offset = (int)$client_offset;

        $timezones = DateTimeZone::listIdentifiers();
        $now = new DateTime('@' . PhabricatorTime::getNow());

        $options = array(
            'ignore' => \Yii::t("app", 'Ignore Conflict'),
        );

        foreach ($timezones as $identifier) {
            $zone = new DateTimeZone($identifier);
            $offset = -($zone->getOffset($now) / 60);
            if ($offset == $client_offset) {
                $options[$identifier] = $identifier;
            }
        }

        $settings_help = \Yii::t("app",
            'You can change your date and time preferences in Settings.');

        $did_calibrate = false;
        if ($request->isFormPost()) {
            $timezone = $request->getStr('timezone');

            $pref_ignore = PhabricatorTimezoneIgnoreOffsetSetting::SETTINGKEY;
            $pref_timezone = PhabricatorTimezoneSetting::SETTINGKEY;

            if ($timezone == 'ignore') {
                $this->writeSettings(
                    array(
                        $pref_ignore => $client_offset,
                    ));

                return $this->newDialog()
                    ->setTitle(\Yii::t("app", 'Conflict Ignored'))
                    ->appendParagraph(
                        \Yii::t("app",
                            'The conflict between your browser and profile timezone ' .
                            'settings will be ignored.'))
                    ->appendParagraph($settings_help)
                    ->addCancelButton('/', \Yii::t("app", 'Done'));
            }

            if (isset($options[$timezone])) {
                $this->writeSettings(
                    array(
                        $pref_ignore => null,
                        $pref_timezone => $timezone,
                    ));

                $did_calibrate = true;
            }
        }

        $server_offset = $viewer->getTimeZoneOffset();

        if ($client_offset == $server_offset || $did_calibrate) {
            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'Timezone Calibrated'))
                ->appendParagraph(
                    \Yii::t("app",
                        'Your browser timezone and profile timezone are now ' .
                        'in agreement (%s).',
                        $this->formatOffset($client_offset)))
                ->appendParagraph($settings_help)
                ->addCancelButton('/', \Yii::t("app", 'Done'));
        }

        // If we have a guess at the timezone from the client, select it as the
        // default.
        $guess = $request->getStr('guess');
        if (empty($options[$guess])) {
            $guess = 'ignore';
        }

        $current_zone = $viewer->getTimezoneIdentifier();
        $current_zone = phutil_tag('strong', array(), $current_zone);

        $form = (new AphrontFormView())
            ->appendChild(
                (new AphrontFormMarkupControl())
                    ->setLabel(\Yii::t("app", 'Current Setting'))
                    ->setValue($current_zone))
            ->appendChild(
                (new AphrontFormSelectControl())
                    ->setName('timezone')
                    ->setLabel(\Yii::t("app", 'New Setting'))
                    ->setOptions($options)
                    ->setValue($guess));

        return $this->newDialog()
            ->setTitle(\Yii::t("app", 'Adjust Timezone'))
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->appendParagraph(
                \Yii::t("app",
                    'Your browser timezone (%s) differs from your profile timezone ' .
                    '(%s). You can ignore this conflict or adjust your profile setting ' .
                    'to match your client.',
                    $this->formatOffset($client_offset),
                    $this->formatOffset($server_offset)))
            ->appendForm($form)
            ->addCancelButton(\Yii::t("app", 'Cancel'))
            ->addSubmitButton(\Yii::t("app", 'Change Timezone'));
    }

    /**
     * @param $offset
     * @return string
     * @author 陈妙威
     */
    private function formatOffset($offset)
    {
        $offset = $offset / 60;

        if ($offset >= 0) {
            return \Yii::t("app", 'UTC-%d', $offset);
        } else {
            return \Yii::t("app", 'UTC+%d', -$offset);
        }
    }

    /**
     * @param array $map
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function writeSettings(array $map)
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $preferences = PhabricatorUserPreferences::loadUserPreferences($viewer);

        $editor = (new PhabricatorUserPreferencesEditor())
            ->setActor($viewer)
            ->setContentSourceFromRequest($request)
            ->setContinueOnNoEffect(true)
            ->setContinueOnMissingFields(true);

        $xactions = array();
        foreach ($map as $key => $value) {
            $xactions[] = $preferences->newTransaction($key, $value);
        }

        $editor->applyTransactions($preferences, $xactions);
    }

}
