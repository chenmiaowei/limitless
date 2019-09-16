<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/24
 * Time: 10:54 AM
 */

namespace orangins\lib\helpers;


use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\settings\setting\PhabricatorTimeFormatSetting;
use DateTime;
use DateTimeZone;
use Yii;
use Exception;

/**
 * Class OranginsViewUtil
 * @package orangins\lib\helpers
 */
class OranginsViewUtil extends OranginsObject
{

    /**
     * @param $epoch
     * @param PhabricatorUser $user
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException

     * @author 陈妙威
     */
    public static function phabricator_date($epoch, PhabricatorUser $user)
    {
        return self::phabricator_format_local_time(
            $epoch,
            $user,
            self::phutil_date_format($epoch));
    }

    /**
     * @param $epoch
     * @param $user
     * @param bool $on
     * @return string
     * @throws Exception
     * @throws \ReflectionException

     * @author 陈妙威
     */
    public static function phabricator_relative_date($epoch, $user, $on = false)
    {
        static $today;
        static $yesterday;

        if (!$today || !$yesterday) {
            $now = time();
            $today = self::phabricator_date($now, $user);
            $yesterday = self::phabricator_date($now - 86400, $user);
        }

        $date = self::phabricator_date($epoch, $user);

        if ($date === $today) {
            return 'today';
        }

        if ($date === $yesterday) {
            return 'yesterday';
        }

        return (($on ? 'on ' : '') . $date);
    }

    /**
     * @param $epoch
     * @param PhabricatorUser $user
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException

     * @author 陈妙威
     */
    public static function phabricator_time($epoch, $user)
    {
        $time_key = PhabricatorTimeFormatSetting::SETTINGKEY;
        return self::phabricator_format_local_time(
            $epoch,
            $user,
            $user->getUserSetting($time_key));
    }

    /**
     * @param $epoch
     * @param PhabricatorUser $user
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException

     * @author 陈妙威
     */
    public static function phabricator_datetime($epoch, $user)
    {
        $time_key = PhabricatorTimeFormatSetting::SETTINGKEY;
        return self::phabricator_format_local_time(
            $epoch,
            $user,
            Yii::t('app', '{0}, {1}',
                [
                    self::phutil_date_format($epoch),
                    $user->getUserSetting($time_key)
                ]));
    }

    /**
     * @param $epoch
     * @param $user
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException

     * @author 陈妙威
     */
    public static function phabricator_datetimezone($epoch, $user)
    {
        $datetime = self::phabricator_datetime($epoch, $user);
        $timezone = self::phabricator_format_local_time($epoch, $user, 'T');

        // Some obscure timezones just render as "+03" or "-09". Make these render
        // as "UTC+3" instead.
        if (preg_match('/^[+-]/', $timezone)) {
            $timezone = (int)trim($timezone, '+');
            if ($timezone < 0) {
                $timezone = \Yii::t("app",'UTC-{0}', [$timezone]);
            } else {
                $timezone = \Yii::t("app",'UTC+{0}', [$timezone]);
            }
        }

        return \Yii::t("app",'{0} ({1})', [$datetime, $timezone]);
    }

    /**
     * This function does not usually need to be called directly. Instead, call
     * @{function:phabricator_date}, @{function:phabricator_time}, or
     * @{function:phabricator_datetime}.
     *
     * @param $epoch
     * @param PhabricatorUser $user User viewing the timestamp.
     * @param $format
     * @return string Formatted, local date/time.
     * @throws Exception
     * @throws \ReflectionException

     */
    public static function phabricator_format_local_time($epoch, $user, $format)
    {
        if (!$epoch) {
            // If we're missing date information for something, the DateTime class will
            // throw an exception when we try to construct an object. Since this is a
            // display function, just return an empty string.
            return '';
        }

        $user_zone = $user->getTimezoneIdentifier();

        static $zones = array();
        if (empty($zones[$user_zone])) {
            $zones[$user_zone] = new DateTimeZone($user_zone);
        }
        $zone = $zones[$user_zone];

        // NOTE: Although DateTime takes a second DateTimeZone parameter to its
        // constructor, it ignores it if the date string includes timezone
        // information. Further, it treats epoch timestamps ("@946684800") as having
        // a UTC timezone. Set the timezone explicitly after constructing the object.
        try {
            $date = new DateTime('@' . $epoch);
        } catch (Exception $ex) {
            // NOTE: DateTime throws an empty exception if the format is invalid,
            // just replace it with a useful one.
            throw new Exception(
                \Yii::t("app","Construction of a DateTime() with epoch '{0}' " .
                    "raised an exception.", [
                    $epoch
                ]));
        }

        $date->setTimezone($zone);

        return date($format, $date->getTimestamp());
//        return PhutilTranslator::getInstance()->translateDate($format, $date);
    }


    /**
     * @param $epoch
     * @return string
     */
    public static function phutil_date_format($epoch)
    {
        $now = time();
        $shift = 30 * 24 * 60 * 60;
        if ($epoch < $now + $shift && $epoch > $now - $shift) {
            $format = \Yii::t("app", 'D, M j');
        } else {
            $format = \Yii::t("app", 'M j Y');
        }
        return $format;
    }

    /**
     * @param $duration
     * @return int|string
     */
    public static function phutil_format_relative_time($duration)
    {
        return self::phutil_format_units_generic(
            $duration,
            array(60, 60, 24, 7),
            array('s', 'm', 'h', 'd', 'w'),
            $precision = 0);
    }


    /**
     * Format a relative time (duration) into weeks, days, hours, minutes,
     * seconds, but unlike phabricator_format_relative_time, does so for more than
     * just the largest unit.
     *
     * @param int Duration in seconds.
     * @param int Levels to render - will render the three highest levels, ie:
     *            5 h, 37 m, 1 s
     * @return string Human-readable description.
     */
    public static function phutil_format_relative_time_detailed($duration, $levels = 2)
    {
        if ($duration == 0) {
            return 'now';
        }
        $levels = max(1, min($levels, 5));
        $remainder = 0;

        $is_negative = false;
        if ($duration < 0) {
            $is_negative = true;
            $duration = abs($duration);
        }

        $this_level = 1;
        $detailed_relative_time = self::phutil_format_units_generic(
            $duration,
            array(60, 60, 24, 7),
            array('s', 'm', 'h', 'd', 'w'),
            $precision = 0,
            $remainder);
        $duration = $remainder;

        while ($remainder > 0 && $this_level < $levels) {
            $detailed_relative_time .= ', ' . self::phutil_format_units_generic(
                    $duration,
                    array(60, 60, 24, 7),
                    array('s', 'm', 'h', 'd', 'w'),
                    $precision = 0,
                    $remainder);
            $duration = $remainder;
            $this_level++;
        }

        if ($is_negative) {
            $detailed_relative_time .= ' ago';
        }

        return $detailed_relative_time;
    }

    /**
     * @param $n
     * @param array $scales
     * @param array $labels
     * @param int $precision
     * @param null $remainder
     * @return int|string
     */
    public static function phutil_format_units_generic(
        $n,
        array $scales,
        array $labels,
        $precision = 0,
        &$remainder = null)
    {

        $is_negative = false;
        if ($n < 0) {
            $is_negative = true;
            $n = abs($n);
        }

        $remainder = 0;
        $accum = 1;

        $scale = array_shift($scales);
        $label = array_shift($labels);
        while ($n >= $scale && count($labels)) {
            $remainder += ($n % $scale) * $accum;
            $n /= $scale;
            $accum *= $scale;
            $label = array_shift($labels);
            if (!count($scales)) {
                break;
            }
            $scale = array_shift($scales);
        }

        if ($is_negative) {
            $n = -$n;
            $remainder = -$remainder;
        }

        if ($precision) {
            $num_string = number_format($n, $precision);
        } else {
            $num_string = (int)floor($n);
        }

        if ($label) {
            $num_string .= ' ' . $label;
        }

        return $num_string;
    }

    /**
     * Parse a human-readable byte description (like "6MB") into an integer.
     *
     * @param string  Human-readable description.
     * @return int    Number of represented bytes.
     * @throws Exception
     */
    public static function phutil_parse_bytes($input)
    {
        $bytes = trim($input);
        if (!strlen($bytes)) {
            return null;
        }

        // NOTE: Assumes US-centric numeral notation.
        $bytes = preg_replace('/[ ,]/', '', $bytes);

        $matches = null;
        if (!preg_match('/^(?:\d+(?:[.]\d+)?)([kmgtp]?)b?$/i', $bytes, $matches)) {
            throw new Exception(\Yii::t("app", "Unable to parse byte size '{0}'!", [
                $input
            ]));
        }

        $scale = array(
            'k' => 1024,
            'm' => 1024 * 1024,
            'g' => 1024 * 1024 * 1024,
            't' => 1024 * 1024 * 1024 * 1024,
            'p' => 1024 * 1024 * 1024 * 1024 * 1024,
        );

        $bytes = (float)$bytes;
        if ($matches[1]) {
            $bytes *= $scale[strtolower($matches[1])];
        }

        return (int)$bytes;
    }

    /**
     * Format a byte count for human consumption, e.g. "10MB" instead of
     * "10485760".
     *
     * @param int Number of bytes.
     * @return string Human-readable description.
     */
    public static function phutil_format_bytes($bytes)
    {
        return self::phutil_format_units_generic(
            $bytes,
            array(1024, 1024, 1024, 1024, 1024),
            array('B', 'KB', 'MB', 'GB', 'TB', 'PB'),
            $precision = 0);
    }
}