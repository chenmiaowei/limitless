<?php

namespace orangins\modules\auth\data;

use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\lib\OranginsObject;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\auth\query\PhabricatorAuthInviteQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserEmail;
use PhutilEmailAddress;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorAuthInviteAction
 * @package orangins\modules\auth\data
 * @author 陈妙威
 */
final class PhabricatorAuthInviteAction extends OranginsObject
{

    /**
     * @var
     */
    private $rawInput;
    /**
     * @var
     */
    private $emailAddress;
    /**
     * @var
     */
    private $userPHID;
    /**
     * @var array
     */
    private $issues = array();
    /**
     * @var
     */
    private $action;

    /**
     *
     */
    const ACTION_SEND = 'invite.send';
    /**
     *
     */
    const ACTION_ERROR = 'invite.error';
    /**
     *
     */
    const ACTION_IGNORE = 'invite.ignore';

    /**
     *
     */
    const ISSUE_PARSE = 'invite.parse';
    /**
     *
     */
    const ISSUE_DUPLICATE = 'invite.duplicate';
    /**
     *
     */
    const ISSUE_UNVERIFIED = 'invite.unverified';
    /**
     *
     */
    const ISSUE_VERIFIED = 'invite.verified';
    /**
     *
     */
    const ISSUE_INVITED = 'invite.invited';
    /**
     *
     */
    const ISSUE_ACCEPTED = 'invite.accepted';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRawInput()
    {
        return $this->rawInput;
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
     * @return mixed
     * @author 陈妙威
     */
    public function getUserPHID()
    {
        return $this->userPHID;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getIssues()
    {
        return $this->issues;
    }

    /**
     * @param $action
     * @return $this
     * @author 陈妙威
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function willSend()
    {
        return ($this->action == self::ACTION_SEND);
    }

    /**
     * @param $issue
     * @return object
     * @author 陈妙威
     */
    public function getShortNameForIssue($issue)
    {
        $map = array(
            self::ISSUE_PARSE => \Yii::t("app",'Not a Valid Email Address'),
            self::ISSUE_DUPLICATE => \Yii::t("app",'Address Duplicated in Input'),
            self::ISSUE_UNVERIFIED => \Yii::t("app",'Unverified User Email'),
            self::ISSUE_VERIFIED => \Yii::t("app",'Verified User Email'),
            self::ISSUE_INVITED => \Yii::t("app",'Previously Invited'),
            self::ISSUE_ACCEPTED => \Yii::t("app",'Already Accepted Invite'),
        );

        return ArrayHelper::getValue($map, $issue);
    }

    /**
     * @param $action
     * @return object
     * @author 陈妙威
     */
    public function getShortNameForAction($action)
    {
        $map = array(
            self::ACTION_SEND => \Yii::t("app",'Will Send Invite'),
            self::ACTION_ERROR => \Yii::t("app",'Address Error'),
            self::ACTION_IGNORE => \Yii::t("app",'Will Ignore Address'),
        );

        return ArrayHelper::getValue($map, $action);
    }

    /**
     * @param $action
     * @return mixed
     * @author 陈妙威
     */
    public function getIconForAction($action)
    {
        switch ($action) {
            case self::ACTION_SEND:
                $icon = 'fa-envelope-o';
                $color = 'green';
                break;
            case self::ACTION_IGNORE:
                $icon = 'fa-ban';
                $color = 'grey';
                break;
            case self::ACTION_ERROR:
                $icon = 'fa-exclamation-triangle';
                $color = 'red';
                break;
        }

        return (new PHUIIconView())
            ->setIcon("{$icon} {$color}");
    }

    /**
     * @param PhabricatorUser $viewer
     * @param array $addresses
     * @return array
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public static function newActionListFromAddresses(
        PhabricatorUser $viewer,
        array $addresses)
    {

        $results = array();
        foreach ($addresses as $address) {
            $result = new PhabricatorAuthInviteAction();
            $result->rawInput = $address;

            $email = new PhutilEmailAddress($address);
            $result->emailAddress = phutil_utf8_strtolower($email->getAddress());

            if (!preg_match('/^\S+@\S+\.\S+\z/', $result->emailAddress)) {
                $result->issues[] = self::ISSUE_PARSE;
            }

            $results[] = $result;
        }

        // Identify duplicates.
        $address_groups = mgroup($results, 'getEmailAddress');
        foreach ($address_groups as $address => $group) {
            if (count($group) > 1) {
                foreach ($group as $action) {
                    $action->issues[] = self::ISSUE_DUPLICATE;
                }
            }
        }

        // Identify addresses which are already in the system.
        $addresses = mpull($results, 'getEmailAddress');
        $email_objects = (new PhabricatorUserEmail())->loadAllWhere(
            'address IN (%Ls)',
            $addresses);

        $email_map = array();
        foreach ($email_objects as $email_object) {
            $address_key = phutil_utf8_strtolower($email_object->getAddress());
            $email_map[$address_key] = $email_object;
        }

        // Identify outstanding invites.
        $invites = (new PhabricatorAuthInviteQuery())
            ->setViewer($viewer)
            ->withEmailAddresses($addresses)
            ->execute();
        $invite_map = mpull($invites, null, 'getEmailAddress');

        foreach ($results as $action) {
            $email = ArrayHelper::getValue($email_map, $action->getEmailAddress());
            if ($email) {
                if ($email->getUserPHID()) {
                    $action->userPHID = $email->getUserPHID();
                    if ($email->getIsVerified()) {
                        $action->issues[] = self::ISSUE_VERIFIED;
                    } else {
                        $action->issues[] = self::ISSUE_UNVERIFIED;
                    }
                }
            }

            $invite = ArrayHelper::getValue($invite_map, $action->getEmailAddress());
            if ($invite) {
                if ($invite->getAcceptedByPHID()) {
                    $action->issues[] = self::ISSUE_ACCEPTED;
                    if (!$action->userPHID) {
                        // This could be different from the user who is currently attached
                        // to the email address if the address was removed or added to a
                        // different account later. Only show it if the address was
                        // removed, since the current status is more up-to-date otherwise.
                        $action->userPHID = $invite->getAcceptedByPHID();
                    }
                } else {
                    $action->issues[] = self::ISSUE_INVITED;
                }
            }
        }

        foreach ($results as $result) {
            foreach ($result->getIssues() as $issue) {
                switch ($issue) {
                    case self::ISSUE_PARSE:
                        $result->action = self::ACTION_ERROR;
                        break;
                    case self::ISSUE_ACCEPTED:
                    case self::ISSUE_VERIFIED:
                        $result->action = self::ACTION_IGNORE;
                        break;
                }
            }
            if (!$result->action) {
                $result->action = self::ACTION_SEND;
            }
        }

        return $results;
    }

    /**
     * @param PhabricatorUser $actor
     * @param $template
     * @throws Exception
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \AphrontQueryException
     * @author 陈妙威
     */
    public function sendInvite(PhabricatorUser $actor, $template)
    {
        if (!$this->willSend()) {
            throw new Exception(\Yii::t("app",'Invite action is not a send action!'));
        }

        if (!preg_match('/{\$INVITE_URI}/', $template)) {
            throw new Exception(\Yii::t("app",'Invite template does not include invite URI!'));
        }

        PhabricatorWorker::scheduleTask(
            'PhabricatorAuthInviteWorker',
            array(
                'address' => $this->getEmailAddress(),
                'template' => $template,
                'authorPHID' => $actor->getPHID(),
            ));
    }

}
