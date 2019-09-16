<?php

namespace orangins\modules\settings\setting;

use orangins\lib\helpers\OranginsUtil;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\settings\panel\PhabricatorDateTimeSettingsPanel;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * Class PhabricatorTimezoneSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
final class PhabricatorTimezoneSetting
    extends PhabricatorOptionGroupSetting
{

    /**
     *
     */
    const SETTINGKEY = 'timezone';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getSettingName()
    {
        return \Yii::t("app", 'Timezone');
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getSettingPanelKey()
    {
        return PhabricatorDateTimeSettingsPanel::PANELKEY;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getSettingOrder()
    {
        return 100;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getControlInstructions()
    {
        return \Yii::t("app", 'Select your local timezone.');
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return date_default_timezone_get();
    }

    /**
     * @param $value
     * @author 陈妙威
     * @throws Exception
     */
    public function assertValidValue($value)
    {
        // NOTE: This isn't doing anything fancy, it's just a much faster
        // validator than doing all the timezone calculations to build the full
        // list of options.

        if (!$value) {
            return;
        }

        static $identifiers;
        if ($identifiers === null) {
            $identifiers = DateTimeZone::listIdentifiers();
            $identifiers = OranginsUtil::array_fuse($identifiers);
        }

        if (isset($identifiers[$value])) {
            return;
        }

        throw new Exception(
            \Yii::t("app",
                'Timezone "%s" is not a valid timezone identifier.',
                $value));
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getSelectOptionGroups()
    {
        $timezones = DateTimeZone::listIdentifiers();
        $now = new DateTime('@' . PhabricatorTime::getNow());

        $groups = array();
        foreach ($timezones as $timezone) {
            $zone = new DateTimeZone($timezone);
            $offset = -($zone->getOffset($now) / (60 * 60));
            $groups[$offset][] = $timezone;
        }

        krsort($groups);

        $option_groups = array(
            array(
                'label' => \Yii::t("app",'Default'),
                'options' => array(),
            ),
        );

        foreach ($groups as $offset => $group) {
            if ($offset >= 0) {
                $label = \Yii::t("app",'UTC-%d', $offset);
            } else {
                $label = \Yii::t("app",'UTC+%d', -$offset);
            }

            sort($group);
            $option_groups[] = array(
                'label' => $label,
                'options' => array_fuse($group),
            );
        }

        return $option_groups;
    }

    /**
     * @param $object
     * @param $xaction
     * @return array
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    public function expandSettingTransaction($object, $xaction)
    {
        // When the user changes their timezone, we also clear any ignored
        // timezone offset.
        return array(
            $xaction,
            $this->newSettingTransaction(
                $object,
                PhabricatorTimezoneIgnoreOffsetSetting::SETTINGKEY,
                null),
        );
    }

}
