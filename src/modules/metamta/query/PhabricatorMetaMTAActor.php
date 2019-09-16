<?php

namespace orangins\modules\metamta\query;

use orangins\lib\OranginsObject;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorMetaMTAActor
 * @package orangins\modules\metamta\query
 * @author 陈妙威
 */
final class PhabricatorMetaMTAActor extends OranginsObject
{

    /**
     *
     */
    const STATUS_DELIVERABLE = 'deliverable';
    /**
     *
     */
    const STATUS_UNDELIVERABLE = 'undeliverable';

    /**
     *
     */
    const REASON_NONE = 'none';
    /**
     *
     */
    const REASON_UNLOADABLE = 'unloadable';
    /**
     *
     */
    const REASON_UNMAILABLE = 'unmailable';
    /**
     *
     */
    const REASON_NO_ADDRESS = 'noaddress';
    /**
     *
     */
    const REASON_DISABLED = 'disabled';
    /**
     *
     */
    const REASON_MAIL_DISABLED = 'maildisabled';
    /**
     *
     */
    const REASON_EXTERNAL_TYPE = 'exernaltype';
    /**
     *
     */
    const REASON_RESPONSE = 'response';
    /**
     *
     */
    const REASON_SELF = 'self';
    /**
     *
     */
    const REASON_MAILTAGS = 'mailtags';
    /**
     *
     */
    const REASON_BOT = 'bot';
    /**
     *
     */
    const REASON_FORCE = 'force';
    /**
     *
     */
    const REASON_FORCE_HERALD = 'force-herald';
    /**
     *
     */
    const REASON_ROUTE_AS_NOTIFICATION = 'route-as-notification';
    /**
     *
     */
    const REASON_ROUTE_AS_MAIL = 'route-as-mail';
    /**
     *
     */
    const REASON_UNVERIFIED = 'unverified';
    /**
     *
     */
    const REASON_MUTED = 'muted';

    /**
     * @var
     */
    private $phid;
    /**
     * @var
     */
    private $emailAddress;
    /**
     * @var
     */
    private $name;
    /**
     * @var string
     */
    private $status = self::STATUS_DELIVERABLE;
    /**
     * @var array
     */
    private $reasons = array();
    /**
     * @var bool
     */
    private $isVerified = false;

    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $email_address
     * @return $this
     * @author 陈妙威
     */
    public function setEmailAddress($email_address)
    {
        $this->emailAddress = $email_address;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @param $is_verified
     * @return $this
     * @author 陈妙威
     */
    public function setIsVerified($is_verified)
    {
        $this->isVerified = $is_verified;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIsVerified()
    {
        return $this->isVerified;
    }

    /**
     * @param $phid
     * @return $this
     * @author 陈妙威
     */
    public function setPHID($phid)
    {
        $this->phid = $phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPHID()
    {
        return $this->phid;
    }

    /**
     * @param $reason
     * @return $this
     * @author 陈妙威
     */
    public function setUndeliverable($reason)
    {
        $this->reasons[] = $reason;
        $this->status = self::STATUS_UNDELIVERABLE;
        return $this;
    }

    /**
     * @param $reason
     * @return $this
     * @author 陈妙威
     */
    public function setDeliverable($reason)
    {
        $this->reasons[] = $reason;
        $this->status = self::STATUS_DELIVERABLE;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isDeliverable()
    {
        return ($this->status === self::STATUS_DELIVERABLE);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getDeliverabilityReasons()
    {
        return $this->reasons;
    }

    /**
     * @param $reason
     * @return bool
     * @author 陈妙威
     */
    public static function isDeliveryReason($reason)
    {
        switch ($reason) {
            case self::REASON_NONE:
            case self::REASON_FORCE:
            case self::REASON_FORCE_HERALD:
            case self::REASON_ROUTE_AS_MAIL:
                return true;
            default:
                // All other reasons cause the message to not be delivered.
                return false;
        }
    }

    /**
     * @param $reason
     * @return mixed
     * @author 陈妙威
     */
    public static function getReasonName($reason)
    {
        $names = array(
            self::REASON_NONE => \Yii::t("app",'None'),
            self::REASON_DISABLED => \Yii::t("app",'Disabled Recipient'),
            self::REASON_BOT => \Yii::t("app",'Bot Recipient'),
            self::REASON_NO_ADDRESS => \Yii::t("app",'No Address'),
            self::REASON_EXTERNAL_TYPE => \Yii::t("app",'External Recipient'),
            self::REASON_UNMAILABLE => \Yii::t("app",'Not Mailable'),
            self::REASON_RESPONSE => \Yii::t("app",'Similar Reply'),
            self::REASON_SELF => \Yii::t("app",'Self Mail'),
            self::REASON_MAIL_DISABLED => \Yii::t("app",'Mail Disabled'),
            self::REASON_MAILTAGS => \Yii::t("app",'Mail Tags'),
            self::REASON_UNLOADABLE => \Yii::t("app",'Bad Recipient'),
            self::REASON_FORCE => \Yii::t("app",'Forced Mail'),
            self::REASON_FORCE_HERALD => \Yii::t("app",'Forced by Herald'),
            self::REASON_ROUTE_AS_NOTIFICATION => \Yii::t("app",'Route as Notification'),
            self::REASON_ROUTE_AS_MAIL => \Yii::t("app",'Route as Mail'),
            self::REASON_UNVERIFIED => \Yii::t("app",'Address Not Verified'),
            self::REASON_MUTED => \Yii::t("app",'Muted'),
        );

        return ArrayHelper::getValue($names, $reason, \Yii::t("app",'Unknown ("%s")', $reason));
    }

    /**
     * @param $reason
     * @return mixed
     * @author 陈妙威
     */
    public static function getReasonDescription($reason)
    {
        $descriptions = array(
            self::REASON_NONE => \Yii::t("app",
                'No special rules affected this mail.'),
            self::REASON_DISABLED => \Yii::t("app",
                'This user is disabled; disabled users do not receive mail.'),
            self::REASON_BOT => \Yii::t("app",
                'This user is a bot; bot accounts do not receive mail.'),
            self::REASON_NO_ADDRESS => \Yii::t("app",
                'Unable to load an email address for this PHID.'),
            self::REASON_EXTERNAL_TYPE => \Yii::t("app",
                'Only external accounts of type "email" are deliverable; this ' .
                'account has a different type.'),
            self::REASON_UNMAILABLE => \Yii::t("app",
                'This PHID type does not correspond to a mailable object.'),
            self::REASON_RESPONSE => \Yii::t("app",
                'This message is a response to another email message, and this ' .
                'recipient received the original email message, so we are not ' .
                'sending them this substantially similar message (for example, ' .
                'the sender used "Reply All" instead of "Reply" in response to ' .
                'mail from Phabricator).'),
            self::REASON_SELF => \Yii::t("app",
                'This recipient is the user whose actions caused delivery of ' .
                'this message, but they have set preferences so they do not ' .
                'receive mail about their own actions (Settings > Email ' .
                'Preferences > Self Actions).'),
            self::REASON_MAIL_DISABLED => \Yii::t("app",
                'This recipient has disabled all email notifications ' .
                '(Settings > Email Preferences > Email Notifications).'),
            self::REASON_MAILTAGS => \Yii::t("app",
                'This mail has tags which control which users receive it, and ' .
                'this recipient has not elected to receive mail with any of ' .
                'the tags on this message (Settings > Email Preferences).'),
            self::REASON_UNLOADABLE => \Yii::t("app",
                'Unable to load user record for this PHID.'),
            self::REASON_FORCE => \Yii::t("app",
                'Delivery of this mail is forced and ignores deliver preferences. ' .
                'Mail which uses forced delivery is usually related to account ' .
                'management or authentication. For example, password reset email ' .
                'ignores mail preferences.'),
            self::REASON_FORCE_HERALD => \Yii::t("app",
                'This recipient was added by a "Send me an Email" rule in Herald, ' .
                'which overrides some delivery settings.'),
            self::REASON_ROUTE_AS_NOTIFICATION => \Yii::t("app",
                'This message was downgraded to a notification by outbound mail ' .
                'rules in Herald.'),
            self::REASON_ROUTE_AS_MAIL => \Yii::t("app",
                'This message was upgraded to email by outbound mail rules ' .
                'in Herald.'),
            self::REASON_UNVERIFIED => \Yii::t("app",
                'This recipient does not have a verified primary email address.'),
            self::REASON_MUTED => \Yii::t("app",
                'This recipient has muted notifications for this object.'),
        );

        return ArrayHelper::getValue($descriptions, $reason, \Yii::t("app",'Unknown Reason ("%s")', $reason));
    }


}
