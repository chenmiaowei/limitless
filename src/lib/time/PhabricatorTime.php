<?php

namespace orangins\lib\time;

use orangins\lib\helpers\OranginsUtil;
use orangins\modules\people\models\PhabricatorUser;
use DateTime;
use DateTimeZone;
use orangins\lib\OranginsObject;
use Exception;

/**
 * Class PhabricatorTime
 * @package orangins\lib\time
 * @author 陈妙威
 */
final class PhabricatorTime extends OranginsObject
{

    /**
     * @var array
     */
    private static $stack = array();
    /**
     * @var
     */
    private static $originalZone;

    /**
     * @param $epoch
     * @param $timezone
     * @return PhabricatorTimeGuard
     * @author 陈妙威
     * @throws Exception
     */
    public static function pushTime($epoch, $timezone)
    {
        if (empty(self::$stack)) {
            self::$originalZone = date_default_timezone_get();
        }

        $ok = date_default_timezone_set($timezone);
        if (!$ok) {
            throw new Exception(\Yii::t("app","Invalid timezone '{0}'!", [
                $timezone
            ]));
        }

        self::$stack[] = array(
            'epoch' => $epoch,
            'timezone' => $timezone,
        );

        return new PhabricatorTimeGuard(OranginsUtil::last_key(self::$stack));
    }

    /**
     * @param $key
     * @author 陈妙威
     * @throws Exception
     */
    public static function popTime($key)
    {
        if ($key !== OranginsUtil::last_key(self::$stack)) {
            throw new Exception(
                \Yii::t("app",
                    '{0} with bad key.',
                    [
                        __METHOD__
                    ]));
        }
        array_pop(self::$stack);

        if (empty(self::$stack)) {
            date_default_timezone_set(self::$originalZone);
        } else {
            $frame = end(self::$stack);
            date_default_timezone_set($frame['timezone']);
        }
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public static function getNow()
    {
        if (self::$stack) {
            $frame = end(self::$stack);
            return $frame['epoch'];
        }
        return time();
    }

    /**
     * @param $time
     * @param PhabricatorUser $user
     * @return int|null
     * @throws Exception
     * @throws \ReflectionException

     * @author 陈妙威
     */
    public static function parseLocalTime($time, PhabricatorUser $user)
    {
        $old_zone = date_default_timezone_get();

        date_default_timezone_set($user->getTimezoneIdentifier());
        $timestamp = (int)strtotime($time, self::getNow());
        if ($timestamp <= 0) {
            $timestamp = null;
        }
        date_default_timezone_set($old_zone);

        return $timestamp;
    }

    /**
     * @param $viewer
     * @return DateTime
     * @author 陈妙威
     */
    public static function getTodayMidnightDateTime($viewer)
    {
        $timezone = new DateTimeZone($viewer->getTimezoneIdentifier());
        $today = new DateTime('@' . time());
        $today->setTimezone($timezone);
        $year = $today->format('Y');
        $month = $today->format('m');
        $day = $today->format('d');
        $today = new DateTime("{$year}-{$month}-{$day}", $timezone);
        return $today;
    }

    /**
     * @param $epoch
     * @param PhabricatorUser $viewer
     * @return DateTime
     * @throws Exception
     * @throws \ReflectionException

     * @author 陈妙威
     */
    public static function getDateTimeFromEpoch($epoch, PhabricatorUser $viewer)
    {
        $datetime = new DateTime('@' . $epoch);
        $datetime->setTimezone($viewer->getTimeZone());
        return $datetime;
    }
}
