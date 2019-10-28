<?php

namespace orangins\modules\metamta\replyhandler;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\metamta\models\PhabricatorMetaMTAApplicationEmail;
use orangins\modules\metamta\models\PhabricatorMetaMTAMailProperties;
use orangins\modules\metamta\models\PhabricatorMetaMTAReceivedMail;
use orangins\modules\metamta\query\PhabricatorMetaMTAMemberQuery;
use orangins\modules\metamta\receiver\PhabricatorObjectMailReceiver;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use Phobject;
use PhutilInvalidStateException;
use ReflectionException;
use yii\base\InvalidConfigException;

/**
 * Class PhabricatorMailReplyHandler
 * @package orangins\modules\metamta\replyhandler
 * @author 陈妙威
 */
abstract class PhabricatorMailReplyHandler extends Phobject
{

    /**
     * @var
     */
    private $mailReceiver;
    /**
     * @var
     */
    private $applicationEmail;
    /**
     * @var
     */
    private $actor;
    /**
     * @var array
     */
    private $excludePHIDs = array();
    /**
     * @var array
     */
    private $unexpandablePHIDs = array();

    /**
     * @param $mail_receiver
     * @return $this
     * @author 陈妙威
     */
    final public function setMailReceiver($mail_receiver)
    {
        $this->validateMailReceiver($mail_receiver);
        $this->mailReceiver = $mail_receiver;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getMailReceiver()
    {
        return $this->mailReceiver;
    }

    /**
     * @param PhabricatorMetaMTAApplicationEmail $email
     * @return $this
     * @author 陈妙威
     */
    public function setApplicationEmail(
        PhabricatorMetaMTAApplicationEmail $email)
    {
        $this->applicationEmail = $email;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationEmail()
    {
        return $this->applicationEmail;
    }

    /**
     * @param PhabricatorUser $actor
     * @return $this
     * @author 陈妙威
     */
    final public function setActor(PhabricatorUser $actor)
    {
        $this->actor = $actor;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getActor()
    {
        return $this->actor;
    }

    /**
     * @param array $exclude
     * @return $this
     * @author 陈妙威
     */
    final public function setExcludeMailRecipientPHIDs(array $exclude)
    {
        $this->excludePHIDs = $exclude;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    final public function getExcludeMailRecipientPHIDs()
    {
        return $this->excludePHIDs;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function setUnexpandablePHIDs(array $phids)
    {
        $this->unexpandablePHIDs = $phids;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getUnexpandablePHIDs()
    {
        return $this->unexpandablePHIDs;
    }

    /**
     * @param $mail_receiver
     * @return mixed
     * @author 陈妙威
     */
    abstract public function validateMailReceiver($mail_receiver);

    /**
     * @param PhabricatorUser $user
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getPrivateReplyHandlerEmailAddress(
        PhabricatorUser $user);

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getReplyHandlerDomain()
    {
        return PhabricatorEnv::getEnvConfig('metamta.reply-handler-domain');
    }

    /**
     * @param PhabricatorMetaMTAReceivedMail $mail
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function receiveEmail(
        PhabricatorMetaMTAReceivedMail $mail);

    /**
     * @param PhabricatorMetaMTAReceivedMail $mail
     * @return mixed
     * @author 陈妙威
     */
    public function processEmail(PhabricatorMetaMTAReceivedMail $mail)
    {
        return $this->receiveEmail($mail);
    }

    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function supportsPrivateReplies()
    {
        return (bool)$this->getReplyHandlerDomain() &&
            !$this->supportsPublicReplies();
    }

    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function supportsPublicReplies()
    {
        if (!PhabricatorEnv::getEnvConfig('metamta.public-replies')) {
            return false;
        }

        if (!$this->getReplyHandlerDomain()) {
            return false;
        }

        return (bool)$this->getPublicReplyHandlerEmailAddress();
    }

    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    final public function supportsReplies()
    {
        return $this->supportsPrivateReplies() ||
            $this->supportsPublicReplies();
    }

    /**
     * @return |null
     * @author 陈妙威
     */
    public function getPublicReplyHandlerEmailAddress()
    {
        return null;
    }

    /**
     * @param $prefix
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    protected function getDefaultPublicReplyHandlerEmailAddress($prefix)
    {

        $receiver = $this->getMailReceiver();
        $receiver_id = $receiver->getID();
        $domain = $this->getReplyHandlerDomain();

        // We compute a hash using the object's own PHID to prevent an attacker
        // from blindly interacting with objects that they haven't ever received
        // mail about by just sending to D1@, D2@, etc...

        $mail_key = PhabricatorMetaMTAMailProperties::loadMailKey($receiver);

        $hash = PhabricatorObjectMailReceiver::computeMailHash(
            $mail_key,
            $receiver->getPHID());

        $address = "{$prefix}{$receiver_id}+public+{$hash}@{$domain}";
        return $this->getSingleReplyHandlerPrefix($address);
    }

    /**
     * @param $address
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    protected function getSingleReplyHandlerPrefix($address)
    {
        $single_handle_prefix = PhabricatorEnv::getEnvConfig(
            'metamta.single-reply-handler-prefix');
        return ($single_handle_prefix)
            ? $single_handle_prefix . '+' . $address
            : $address;
    }

    /**
     * @param PhabricatorUser $user
     * @param $prefix
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    protected function getDefaultPrivateReplyHandlerEmailAddress(
        PhabricatorUser $user,
        $prefix)
    {

        $receiver = $this->getMailReceiver();
        $receiver_id = $receiver->getID();
        $user_id = $user->getID();

        $mail_key = PhabricatorMetaMTAMailProperties::loadMailKey($receiver);

        $hash = PhabricatorObjectMailReceiver::computeMailHash(
            $mail_key,
            $user->getPHID());
        $domain = $this->getReplyHandlerDomain();

        $address = "{$prefix}{$receiver_id}+{$user_id}+{$hash}@{$domain}";
        return $this->getSingleReplyHandlerPrefix($address);
    }

    /**
     * @param $body
     * @param array $attachments
     * @return string
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    final protected function enhanceBodyWithAttachments(
        $body,
        array $attachments)
    {

        if (!$attachments) {
            return $body;
        }

        $files = PhabricatorFile::find()
            ->setViewer($this->getActor())
            ->withPHIDs($attachments)
            ->execute();

        $output = array();
        $output[] = $body;

        // We're going to put all the non-images first in a list, then embed
        // the images.
        $head = array();
        $tail = array();
        foreach ($files as $file) {
            if ($file->isViewableImage()) {
                $tail[] = $file;
            } else {
                $head[] = $file;
            }
        }

        if ($head) {
            $list = array();
            foreach ($head as $file) {
                $list[] = '  - {' . $file->getMonogram() . ', layout=link}';
            }
            $output[] = implode("\n", $list);
        }

        if ($tail) {
            $list = array();
            foreach ($tail as $file) {
                $list[] = '{' . $file->getMonogram() . '}';
            }
            $output[] = implode("\n\n", $list);
        }

        $output = implode("\n\n", $output);

        return rtrim($output);
    }


    /**
     * Produce a list of mail targets for a given to/cc list.
     *
     * Each target should be sent a separate email, and contains the information
     * required to generate it with appropriate permissions and configuration.
     *
     * @param array $raw_to
     * @param array $raw_cc
     * @return array List of targets.
     * @throws Exception
     */
    final public function getMailTargets(array $raw_to, array $raw_cc)
    {
        list($to, $cc) = $this->expandRecipientPHIDs($raw_to, $raw_cc);
        list($to, $cc) = $this->loadRecipientUsers($to, $cc);
        list($to, $cc) = $this->filterRecipientUsers($to, $cc);

        if (!$to && !$cc) {
            return array();
        }

        $template = (new PhabricatorMailTarget())
            ->setRawToPHIDs($raw_to)
            ->setRawCCPHIDs($raw_cc);

        // Set the public reply address as the default, if one exists. We
        // might replace this with a private address later.
        if ($this->supportsPublicReplies()) {
            $reply_to = $this->getPublicReplyHandlerEmailAddress();
            if ($reply_to) {
                $template->setReplyTo($reply_to);
            }
        }

        $supports_private_replies = $this->supportsPrivateReplies();
        $mail_all = !PhabricatorEnv::getEnvConfig('metamta.one-mail-per-recipient');
        $targets = array();
        if ($mail_all) {
            $target = id(clone $template)
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->setToMap($to)
                ->setCCMap($cc);

            $targets[] = $target;
        } else {
            $map = $to + $cc;

            foreach ($map as $phid => $user) {
                // Preserve the original To/Cc information on the target.
                if (isset($to[$phid])) {
                    $target_to = array($phid => $user);
                    $target_cc = array();
                } else {
                    $target_to = array();
                    $target_cc = array($phid => $user);
                }

                $target = id(clone $template)
                    ->setViewer($user)
                    ->setToMap($target_to)
                    ->setCCMap($target_cc);

                if ($supports_private_replies) {
                    $reply_to = $this->getPrivateReplyHandlerEmailAddress($user);
                    if ($reply_to) {
                        $target->setReplyTo($reply_to);
                    }
                }

                $targets[] = $target;
            }
        }

        return $targets;
    }


    /**
     * Expand lists of recipient PHIDs.
     *
     * This takes any compound recipients (like projects) and looks up all their
     * members.
     *
     * @param array $to
     * @param array $cc
     * @return array Expanded PHID lists.
     */
    private function expandRecipientPHIDs(array $to, array $cc)
    {
        $to_result = array();
        $cc_result = array();

        // "Unexpandable" users have disengaged from an object (for example,
        // by resigning from a revision).

        // If such a user is still a direct recipient (for example, they're still
        // on the Subscribers list) they're fair game, but group targets (like
        // projects) will no longer include them when expanded.

        $unexpandable = $this->getUnexpandablePHIDs();
        $unexpandable = array_fuse($unexpandable);

        $all_phids = array_merge($to, $cc);
        if ($all_phids) {
            $map = (new PhabricatorMetaMTAMemberQuery())
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withPHIDs($all_phids)
                ->execute();
            foreach ($to as $phid) {
                foreach ($map[$phid] as $expanded) {
                    if ($expanded !== $phid) {
                        if (isset($unexpandable[$expanded])) {
                            continue;
                        }
                    }
                    $to_result[$expanded] = $expanded;
                }
            }
            foreach ($cc as $phid) {
                foreach ($map[$phid] as $expanded) {
                    if ($expanded !== $phid) {
                        if (isset($unexpandable[$expanded])) {
                            continue;
                        }
                    }
                    $cc_result[$expanded] = $expanded;
                }
            }
        }

        // Remove recipients from "CC" if they're also present in "To".
        $cc_result = array_diff_key($cc_result, $to_result);

        return array(array_values($to_result), array_values($cc_result));
    }


    /**
     * Load @{class:PhabricatorUser} objects for each recipient.
     *
     * Invalid recipients are dropped from the results.
     *
     * @param array $to
     * @param array $cc
     * @return array Maps from PHIDs to users.
     * @throws InvalidConfigException
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     */
    private function loadRecipientUsers(array $to, array $cc)
    {
        $to_result = array();
        $cc_result = array();

        $all_phids = array_merge($to, $cc);
        if ($all_phids) {
            // We need user settings here because we'll check translations later
            // when generating mail.
            $users = PhabricatorUser::find()
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withPHIDs($all_phids)
                ->needUserSettings(true)
                ->execute();
            $users = mpull($users, null, 'getPHID');

            foreach ($to as $phid) {
                if (isset($users[$phid])) {
                    $to_result[$phid] = $users[$phid];
                }
            }
            foreach ($cc as $phid) {
                if (isset($users[$phid])) {
                    $cc_result[$phid] = $users[$phid];
                }
            }
        }

        return array($to_result, $cc_result);
    }


    /**
     * Remove recipients who do not have permission to view the mail receiver.
     *
     * @param array $to
     * @param array $cc
     * @return array Filtered user maps.
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     */
    private function filterRecipientUsers(array $to, array $cc)
    {
        $to_result = array();
        $cc_result = array();

        $all_users = $to + $cc;
        if ($all_users) {
            $can_see = array();
            $object = $this->getMailReceiver();
            foreach ($all_users as $phid => $user) {
                $visible = PhabricatorPolicyFilter::hasCapability(
                    $user,
                    $object,
                    PhabricatorPolicyCapability::CAN_VIEW);
                if ($visible) {
                    $can_see[$phid] = true;
                }
            }

            foreach ($to as $phid => $user) {
                if (!empty($can_see[$phid])) {
                    $to_result[$phid] = $all_users[$phid];
                }
            }

            foreach ($cc as $phid => $user) {
                if (!empty($can_see[$phid])) {
                    $cc_result[$phid] = $all_users[$phid];
                }
            }
        }

        return array($to_result, $cc_result);
    }

}
