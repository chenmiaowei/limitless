<?php

namespace orangins\modules\people\models;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\people\query\PhabricatorPeopleLogQuery;
use Yii;

/**
 * This is the model class for table "user_log".
 *
 * @property int $id
 * @property string $actor_phid
 * @property string $user_phid
 * @property string $action
 * @property string $old_value
 * @property string $new_value
 * @property string $details
 * @property string $remote_addr
 * @property string $session
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorUserLog extends \yii\db\ActiveRecord
{
    /**
     *
     */
    const ACTION_LOGIN = 'login';
    /**
     *
     */
    const ACTION_LOGIN_PARTIAL = 'login-partial';
    /**
     *
     */
    const ACTION_LOGIN_FULL = 'login-full';
    /**
     *
     */
    const ACTION_LOGOUT = 'logout';
    /**
     *
     */
    const ACTION_LOGIN_FAILURE = 'login-fail';
    /**
     *
     */
    const ACTION_LOGIN_LEGALPAD = 'login-legalpad';
    /**
     *
     */
    const ACTION_RESET_PASSWORD = 'reset-pass';

    /**
     *
     */
    const ACTION_CREATE = 'create';
    /**
     *
     */
    const ACTION_EDIT = 'edit';

    /**
     *
     */
    const ACTION_ADMIN = 'admin';
    /**
     *
     */
    const ACTION_SYSTEM_AGENT = 'system-agent';
    /**
     *
     */
    const ACTION_MAILING_LIST = 'mailing-list';
    /**
     *
     */
    const ACTION_DISABLE = 'disable';
    /**
     *
     */
    const ACTION_APPROVE = 'approve';
    /**
     *
     */
    const ACTION_DELETE = 'delete';

    /**
     *
     */
    const ACTION_CONDUIT_CERTIFICATE = 'conduit-cert';
    /**
     *
     */
    const ACTION_CONDUIT_CERTIFICATE_FAILURE = 'conduit-cert-fail';

    /**
     *
     */
    const ACTION_EMAIL_PRIMARY = 'email-primary';
    /**
     *
     */
    const ACTION_EMAIL_REMOVE = 'email-remove';
    /**
     *
     */
    const ACTION_EMAIL_ADD = 'email-add';
    /**
     *
     */
    const ACTION_EMAIL_VERIFY = 'email-verify';
    /**
     *
     */
    const ACTION_EMAIL_REASSIGN = 'email-reassign';

    /**
     *
     */
    const ACTION_CHANGE_PASSWORD = 'change-password';
    /**
     *
     */
    const ACTION_CHANGE_USERNAME = 'change-username';

    /**
     *
     */
    const ACTION_ENTER_HISEC = 'hisec-enter';
    /**
     *
     */
    const ACTION_EXIT_HISEC = 'hisec-exit';
    /**
     *
     */
    const ACTION_FAIL_HISEC = 'hisec-fail';

    /**
     *
     */
    const ACTION_MULTI_ADD = 'multi-add';
    /**
     *
     */
    const ACTION_MULTI_REMOVE = 'multi-remove';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_phid', 'action', 'old_value', 'new_value', 'details', 'remote_addr'], 'required'],
            [['new_value'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['actor_phid', 'user_phid', 'old_value', 'details'], 'string', 'max' => 64],
            [['action'], 'string', 'max' => 16],
            [['remote_addr'], 'string', 'max' => 255],
            [['session'], 'string', 'max' => 40],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'actor_phid' => Yii::t('app', 'Actor PHID'),
            'user_phid' => Yii::t('app', 'User PHID'),
            'action' => Yii::t('app', 'Action'),
            'old_value' => Yii::t('app', 'Old Value'),
            'new_value' => Yii::t('app', 'New Value'),
            'details' => Yii::t('app', 'Details'),
            'remote_addr' => Yii::t('app', 'Remote Addr'),
            'session' => Yii::t('app', 'Session'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param $action
     * @param $timespan
     * @return array
     * @author 陈妙威
     */
    public static function loadRecentEventsFromThisIP($action, $timespan)
    {
        $address = PhabricatorEnv::getRemoteAddress();
        if (!$address) {
            return array();
        }

        return PhabricatorUserLog::find()
            ->andWhere([
                'action' => $action,
                'remote_addr' => $address->getAddress()
            ])
            ->andWhere("created_at>:created_at", [
                ":created_at" => PhabricatorTime::getNow() - $timespan
            ])
            ->orderBy("created_at desc")
            ->all();
    }

    /**
     * @return string
     */
    public function getActorPHID()
    {
        return $this->actor_phid;
    }

    /**
     * @param string $actor_phid
     * @return self
     */
    public function setActorPHID($actor_phid)
    {
        $this->actor_phid = $actor_phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getUserPHID()
    {
        return $this->user_phid;
    }

    /**
     * @param string $user_phid
     * @return self
     */
    public function setUserPHID($user_phid)
    {
        $this->user_phid = $user_phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return self
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return array
     */
    public function getOldValue()
    {
        return $this->old_value === null ? array() : phutil_json_decode($this->old_value);
    }

    /**
     * @param string $old_value
     * @return self
     * @throws \Exception
     */
    public function setOldValue($old_value)
    {
        $this->old_value = phutil_json_encode($old_value);
        return $this;
    }

    /**
     * @return array
     */
    public function getNewValue()
    {
        return $this->new_value === null ? array() : phutil_json_decode($this->new_value);
    }

    /**
     * @param string $new_value
     * @return self
     * @throws \Exception
     */
    public function setNewValue($new_value)
    {
        $this->new_value = phutil_json_encode($new_value);
        return $this;
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->details === null ? array() : phutil_json_decode($this->details);
    }

    /**
     * @param array $details
     * @return self
     * @throws \Exception
     */
    public function setDetails($details)
    {
        $this->details = phutil_json_encode($details);
        return $this;
    }

    /**
     * @return string
     */
    public function getRemoteAddr()
    {
        return $this->remote_addr;
    }

    /**
     * @param string $remote_addr
     * @return self
     */
    public function setRemoteAddr($remote_addr)
    {
        $this->remote_addr = $remote_addr;
        return $this;
    }

    /**
     * @return string
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param string $session
     * @return self
     */
    public function setSession($session)
    {
        $this->session = $session;
        return $this;
    }


    /**
     * @param PhabricatorUser|null $actor
     * @param null $object_phid
     * @param null $action
     * @return PhabricatorUserLog
     * @author 陈妙威
     */
    public static function initializeNewLog(
        PhabricatorUser $actor = null,
        $object_phid = null,
        $action = null)
    {

        $log = new PhabricatorUserLog();

        if ($actor) {
            $log->actor_phid = $actor->getPHID();


//            if ($actor->hasSession()) {
//                $session = $actor->getSession();

            // NOTE: This is a hash of the real session value, so it's safe to
            // store it directly in the logs.
            $log->session = Yii::$app->session->getId();
//            }
        }

        $log->user_phid = (string)$object_phid;
        $log->action = $action;

        $address = PhabricatorEnv::getRemoteAddress();
        if ($address) {
            $log->remote_addr = $address->getAddress();
        } else {
            $log->remote_addr = '';
        }

        return $log;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getActionTypeMap()
    {
        return array(
            self::ACTION_LOGIN => \Yii::t("app", 'Login'),
            self::ACTION_LOGIN_PARTIAL => \Yii::t("app", 'Login: Partial Login'),
            self::ACTION_LOGIN_FULL => \Yii::t("app", 'Login: Upgrade to Full'),
            self::ACTION_LOGIN_FAILURE => \Yii::t("app", 'Login: Failure'),
            self::ACTION_LOGIN_LEGALPAD =>
                \Yii::t("app", 'Login: Signed Required Legalpad Documents'),
            self::ACTION_LOGOUT => \Yii::t("app", 'Logout'),
            self::ACTION_RESET_PASSWORD => \Yii::t("app", 'Reset Password'),
            self::ACTION_CREATE => \Yii::t("app", 'Create Account'),
            self::ACTION_EDIT => \Yii::t("app", 'Edit Account'),
            self::ACTION_ADMIN => \Yii::t("app", 'Add/Remove Administrator'),
            self::ACTION_SYSTEM_AGENT => \Yii::t("app", 'Add/Remove System Agent'),
            self::ACTION_MAILING_LIST => \Yii::t("app", 'Add/Remove Mailing List'),
            self::ACTION_DISABLE => \Yii::t("app", 'Enable/Disable'),
            self::ACTION_APPROVE => \Yii::t("app", 'Approve Registration'),
            self::ACTION_DELETE => \Yii::t("app", 'Delete User'),
            self::ACTION_CONDUIT_CERTIFICATE
            => \Yii::t("app", 'Conduit: Read Certificate'),
            self::ACTION_CONDUIT_CERTIFICATE_FAILURE
            => \Yii::t("app", 'Conduit: Read Certificate Failure'),
            self::ACTION_EMAIL_PRIMARY => \Yii::t("app", 'Email: Change Primary'),
            self::ACTION_EMAIL_ADD => \Yii::t("app", 'Email: Add Address'),
            self::ACTION_EMAIL_REMOVE => \Yii::t("app", 'Email: Remove Address'),
            self::ACTION_EMAIL_VERIFY => \Yii::t("app", 'Email: Verify'),
            self::ACTION_EMAIL_REASSIGN => \Yii::t("app", 'Email: Reassign'),
            self::ACTION_CHANGE_PASSWORD => \Yii::t("app", 'Change Password'),
            self::ACTION_CHANGE_USERNAME => \Yii::t("app", 'Change Username'),
            self::ACTION_ENTER_HISEC => \Yii::t("app", 'Hisec: Enter'),
            self::ACTION_EXIT_HISEC => \Yii::t("app", 'Hisec: Exit'),
            self::ACTION_FAIL_HISEC => \Yii::t("app", 'Hisec: Failed Attempt'),
            self::ACTION_MULTI_ADD => \Yii::t("app", 'Multi-Factor: Add Factor'),
            self::ACTION_MULTI_REMOVE => \Yii::t("app", 'Multi-Factor: Remove Factor'),
        );
    }


    /**
     * @return PhabricatorPeopleLogQuery|\yii\db\ActiveQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorPeopleLogQuery(get_called_class());
    }
}
