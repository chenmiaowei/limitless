<?php

namespace orangins\modules\metamta\constants;

use orangins\lib\OranginsObject;
use yii\helpers\ArrayHelper;

final class MetaMTAReceivedMailStatus
    extends OranginsObject
{

    const STATUS_DUPLICATE = 'err:duplicate';
    const STATUS_FROM_PHABRICATOR = 'err:self';
    const STATUS_NO_RECEIVERS = 'err:no-receivers';
    const STATUS_ABUNDANT_RECEIVERS = 'err:multiple-receivers';
    const STATUS_UNKNOWN_SENDER = 'err:unknown-sender';
    const STATUS_DISABLED_SENDER = 'err:disabled-sender';
    const STATUS_NO_PUBLIC_MAIL = 'err:no-public-mail';
    const STATUS_USER_MISMATCH = 'err:bad-user';
    const STATUS_POLICY_PROBLEM = 'err:policy';
    const STATUS_NO_SUCH_OBJECT = 'err:not-found';
    const STATUS_HASH_MISMATCH = 'err:bad-hash';
    const STATUS_UNHANDLED_EXCEPTION = 'err:exception';
    const STATUS_EMPTY = 'err:empty';
    const STATUS_EMPTY_IGNORED = 'err:empty-ignored';

    public static function getHumanReadableName($status)
    {
        $map = array(
            self::STATUS_DUPLICATE => \Yii::t("app", 'Duplicate Message'),
            self::STATUS_FROM_PHABRICATOR => \Yii::t("app", 'Phabricator Mail'),
            self::STATUS_NO_RECEIVERS => \Yii::t("app", 'No Receivers'),
            self::STATUS_ABUNDANT_RECEIVERS => \Yii::t("app", 'Multiple Receivers'),
            self::STATUS_UNKNOWN_SENDER => \Yii::t("app", 'Unknown Sender'),
            self::STATUS_DISABLED_SENDER => \Yii::t("app", 'Disabled Sender'),
            self::STATUS_NO_PUBLIC_MAIL => \Yii::t("app", 'No Public Mail'),
            self::STATUS_USER_MISMATCH => \Yii::t("app", 'User Mismatch'),
            self::STATUS_POLICY_PROBLEM => \Yii::t("app", 'Policy Error'),
            self::STATUS_NO_SUCH_OBJECT => \Yii::t("app", 'No Such Object'),
            self::STATUS_HASH_MISMATCH => \Yii::t("app", 'Bad Address'),
            self::STATUS_UNHANDLED_EXCEPTION => \Yii::t("app", 'Unhandled Exception'),
            self::STATUS_EMPTY => \Yii::t("app", 'Empty Mail'),
            self::STATUS_EMPTY_IGNORED => \Yii::t("app", 'Ignored Empty Mail'),
        );

        return ArrayHelper::getValue($map, $status, \Yii::t("app", 'Processing Exception'));
    }

}
