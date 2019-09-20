<?php

namespace orangins\modules\metamta\models;

use Exception;
use orangins\lib\db\ActiveRecord;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\metamta\constants\MetaMTAReceivedMailStatus;
use orangins\modules\metamta\contentsource\PhabricatorEmailContentSource;
use orangins\modules\metamta\models\exception\PhabricatorMetaMTAReceivedMailProcessingException;
use orangins\modules\metamta\parser\PhabricatorMetaMTAEmailBodyParser;
use orangins\modules\metamta\receiver\PhabricatorMailReceiver;
use orangins\modules\metamta\util\PhabricatorMailUtil;
use orangins\modules\people\db\ActiveRecordAuthorTrait;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserEmail;
use PhutilClassMapQuery;
use PhutilEmailAddress;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "metamta_receivedmail".
 *
 * @property int $id
 * @property string $headers
 * @property string $bodies
 * @property string $attachments
 * @property string $related_phid
 * @property string $author_phid
 * @property string $message
 * @property string $message_id_hash
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorMetaMTAReceivedMail extends ActiveRecord
{
    use ActiveRecordAuthorTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'metamta_receivedmail';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['headers', 'bodies', 'attachments', 'message_id_hash', 'state'], 'required'],
            [['headers', 'bodies', 'attachments', 'message'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['related_phid', 'author_phid'], 'string', 'max' => 64],
            [['message_id_hash'], 'string', 'max' => 12],
            [['status'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'headers' => Yii::t('app', 'Headers'),
            'bodies' => Yii::t('app', 'Bodies'),
            'attachments' => Yii::t('app', 'Attachments'),
            'related_phid' => Yii::t('app', 'Related Phid'),
            'author_phid' => Yii::t('app', 'Author Phid'),
            'message' => Yii::t('app', 'Message'),
            'message_id_hash' => Yii::t('app', 'Message Id Hash'),
            'status' => Yii::t('app', 'State'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string
     */
    public function getMessageIDHash()
    {
        return $this->message_id_hash;
    }

    /**
     * @param string $message_id_hash
     * @return self
     */
    public function setMessageIDHash($message_id_hash)
    {
        $this->message_id_hash = $message_id_hash;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return self
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }


    /**
     * @param array $headers
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function setHeaders(array $headers)
    {
        // Normalize headers to lowercase.
        $normalized = array();
        foreach ($headers as $name => $value) {
            $name = $this->normalizeMailHeaderName($name);
            if ($name == 'message-id') {
                $this->setMessageIDHash(PhabricatorHash::digestForIndex($value));
            }
            $normalized[$name] = $value;
        }
        $this->headers = phutil_json_encode($normalized);
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return object
     * @author 陈妙威
     */
    public function getHeader($key, $default = null)
    {
        $key = $this->normalizeMailHeaderName($key);
        return ArrayHelper::getValue($this->headers === null ? [] : phutil_json_decode($this->headers), $key, $default);
    }


    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers === null ? [] : phutil_json_decode($this->headers);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function parseBody()
    {
        $body = $this->getRawTextBody();
        $parser = new PhabricatorMetaMTAEmailBodyParser();
        return $parser->parseBody($body);
    }

    /**
     * @return array
     */
    public function getBodies()
    {
        return $this->bodies === null ? [] : phutil_json_decode($this->bodies);
    }

    /**
     * @param string $bodies
     * @return self
     * @throws Exception
     */
    public function setBodies($bodies)
    {
        $this->bodies = phutil_json_encode($bodies);
        return $this;
    }


    /**
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachments === null ? [] : phutil_json_decode($this->attachments);
    }

    /**
     * @param string $attachments
     * @return self
     * @throws Exception
     */
    public function setAttachments($attachments)
    {
        $this->attachments = phutil_json_encode($attachments);;
        return $this;
    }


    /**
     * @param $name
     * @return string
     * @author 陈妙威
     */
    private function normalizeMailHeaderName($name)
    {
        return strtolower($name);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getMessageID()
    {
        return $this->getHeader('Message-ID');
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getSubject()
    {
        return $this->getHeader('Subject');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getCCAddresses()
    {
        return $this->getRawEmailAddresses(ArrayHelper::getValue($this->getHeaders(), 'cc'));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getToAddresses()
    {
        return $this->getRawEmailAddresses(ArrayHelper::getValue($this->getHeaders(), 'to'));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function newTargetAddresses()
    {
        $raw_addresses = array();

        foreach ($this->getToAddresses() as $raw_address) {
            $raw_addresses[] = $raw_address;
        }

        foreach ($this->getCCAddresses() as $raw_address) {
            $raw_addresses[] = $raw_address;
        }

        $raw_addresses = array_unique($raw_addresses);

        $addresses = array();
        foreach ($raw_addresses as $raw_address) {
            $addresses[] = new PhutilEmailAddress($raw_address);
        }

        return $addresses;
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function loadAllRecipientPHIDs()
    {
        $addresses = array_merge(
            $this->getToAddresses(),
            $this->getCCAddresses());

        return $this->loadPHIDsFromAddresses($addresses);
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function loadCCPHIDs()
    {
        return $this->loadPHIDsFromAddresses($this->getCCAddresses());
    }

    /**
     * @param array $addresses
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function loadPHIDsFromAddresses(array $addresses)
    {
        if (empty($addresses)) {
            return array();
        }
        $users = PhabricatorUserEmail::find()->andWhere(['IN', 'address', $addresses])->all();
        return mpull($users, 'getUserPHID');
    }

    /**
     * @return bool|PhabricatorMetaMTAReceivedMail
     * @throws Exception
     * @author 陈妙威
     */
    public function processReceivedMail()
    {
        $viewer = $this->getViewer();

        $sender = null;
        try {
            $this->dropMailFromPhabricator();
            $this->dropMailAlreadyReceived();
            $this->dropEmptyMail();

            $sender = $this->loadSender();
            if ($sender) {
                $this->setAuthorPHID($sender->getPHID());

                // If we've identified the sender, mark them as the author of any
                // attached files. We do this before we validate them (below), since
                // they still authored these files even if their account is not allowed
                // to interact via email.

                $attachments = $this->getAttachments();
                if ($attachments) {
                    /** @var PhabricatorFile[] $files */
                    $files = PhabricatorFile::find()
                        ->setViewer($viewer)
                        ->withPHIDs($attachments)
                        ->execute();
                    foreach ($files as $file) {
                        $file->setAuthorPHID($sender->getPHID())->save();
                    }
                }

                $this->validateSender($sender);
            }

            /** @var PhabricatorMailReceiver[] $receivers */
            $receivers = (new PhutilClassMapQuery())
                ->setAncestorClass(PhabricatorMailReceiver::className())
                ->setFilterMethod('isEnabled')
                ->execute();

            $reserved_recipient = null;
            $targets = $this->newTargetAddresses();
            foreach ($targets as $key => $target) {
                // Never accept any reserved address as a mail target. This prevents
                // security issues around "hostmaster@" and bad behavior with
                // "noreply@".
                if (PhabricatorMailUtil::isReservedAddress($target)) {
                    if (!$reserved_recipient) {
                        $reserved_recipient = $target;
                    }
                    unset($targets[$key]);
                    continue;
                }

                // See T13234. Don't process mail if a user has attached this address
                // to their account.
                if (PhabricatorMailUtil::isUserAddress($target)) {
                    unset($targets[$key]);
                    continue;
                }
            }

            $any_accepted = false;
            $receiver_exception = null;
            foreach ($receivers as $receiver) {
                $receiver1 = clone $receiver;
                $receiver = $receiver1
                    ->setViewer($viewer);

                if ($sender) {
                    $receiver->setSender($sender);
                }

                foreach ($targets as $target) {
                    try {
                        if (!$receiver->canAcceptMail($this, $target)) {
                            continue;
                        }

                        $any_accepted = true;

                        $receiver->receiveMail($this, $target);
                    } catch (Exception $ex) {
                        // If receivers raise exceptions, we'll keep the first one in hope
                        // that it points at a root cause.
                        if (!$receiver_exception) {
                            $receiver_exception = $ex;
                        }
                    }
                }
            }

            if ($receiver_exception) {
                throw $receiver_exception;
            }


            if (!$any_accepted) {
                if ($reserved_recipient) {
                    // If nothing accepted the mail, we normally raise an error to help
                    // users who mistakenly send mail to "barges@" instead of "bugs@".

                    // However, if the recipient list included a reserved recipient, we
                    // don't bounce the mail with an error.

                    // The intent here is that if a user does a "Reply All" and includes
                    // "From: noreply@phabricator" in the receipient list, we just want
                    // to drop the mail rather than send them an unhelpful bounce message.

                    throw new PhabricatorMetaMTAReceivedMailProcessingException(
                        MetaMTAReceivedMailStatus::STATUS_RESERVED,
                        pht(
                            'No application handled this mail. This mail was sent to a ' .
                            'reserved recipient ("%s") so bounces are suppressed.',
                            (string)$reserved_recipient));
                } else if (!$sender) {
                    // NOTE: Currently, we'll always drop this mail (since it's headed to
                    // an unverified recipient). See T12237. These details are still
                    // useful because they'll appear in the mail logs and Mail web UI.

                    throw new PhabricatorMetaMTAReceivedMailProcessingException(
                        MetaMTAReceivedMailStatus::STATUS_UNKNOWN_SENDER,
                        pht(
                            'This email was sent from an email address ("%s") that is not ' .
                            'associated with a Phabricator account. To interact with ' .
                            'Phabricator via email, add this address to your account.',
                            (string)$this->newFromAddress()));
                } else {
                    throw new PhabricatorMetaMTAReceivedMailProcessingException(
                        MetaMTAReceivedMailStatus::STATUS_NO_RECEIVERS,
                        pht(
                            'Phabricator can not process this mail because no application ' .
                            'knows how to handle it. Check that the address you sent it to ' .
                            'is correct.' .
                            "\n\n" .
                            '(No concrete, enabled subclass of PhabricatorMailReceiver can ' .
                            'accept this mail.)'));
                }
            }
        } catch (PhabricatorMetaMTAReceivedMailProcessingException $ex) {
            switch ($ex->getStatusCode()) {
                case MetaMTAReceivedMailStatus::STATUS_DUPLICATE:
                case MetaMTAReceivedMailStatus::STATUS_FROM_PHABRICATOR:
                    // Don't send an error email back in these cases, since they're
                    // very unlikely to be the sender's fault.
                    break;
                case MetaMTAReceivedMailStatus::STATUS_RESERVED:
                    // This probably is the sender's fault, but it's likely an accident
                    // that we received the mail at all.
                    break;
                case MetaMTAReceivedMailStatus::STATUS_EMPTY_IGNORED:
                    // This error is explicitly ignored.
                    break;
                default:
                    $this->sendExceptionMail($ex, $sender);
                    break;
            }

            $this
                ->setStatus($ex->getStatusCode())
                ->setMessage($ex->getMessage())
                ->save();
            return $this;
        } catch (Exception $ex) {
            $this->sendExceptionMail($ex, $sender);

            $this
                ->setStatus(MetaMTAReceivedMailStatus::STATUS_UNHANDLED_EXCEPTION)
                ->setMessage(pht('Unhandled Exception: %s', $ex->getMessage()))
                ->save();

            throw $ex;
        }

        return $this->setMessage('OK')->save();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCleanTextBody()
    {
        $body = $this->getRawTextBody();
        $parser = new PhabricatorMetaMTAEmailBodyParser();
        return $parser->stripTextBody($body);
    }


    /**
     * @return object
     * @author 陈妙威
     */
    public function getRawTextBody()
    {
        return ArrayHelper::getValue($this->getBodies(), 'text');
    }

    /**
     * Strip an email address down to the actual user@domain.tld part if
     * necessary, since sometimes it will have formatting like
     * '"Abraham Lincoln" <alincoln@logcab.in>'.
     * @param $address
     * @return
     */
    private function getRawEmailAddress($address)
    {
        $matches = null;
        $ok = preg_match('/<(.*)>/', $address, $matches);
        if ($ok) {
            $address = $matches[1];
        }
        return $address;
    }

    /**
     * @param $addresses
     * @return array
     * @author 陈妙威
     */
    private function getRawEmailAddresses($addresses)
    {
        $raw_addresses = array();
        foreach (explode(',', $addresses) as $address) {
            $raw_addresses[] = $this->getRawEmailAddress($address);
        }
        return array_filter($raw_addresses);
    }

    /**
     * If Phabricator sent the mail, always drop it immediately. This prevents
     * loops where, e.g., the public bug address is also a user email address
     * and creating a bug sends them an email, which loops.
     * @throws PhabricatorMetaMTAReceivedMailProcessingException
     */
    private function dropMailFromPhabricator()
    {
        if (!$this->getHeader('x-phabricator-sent-this-message')) {
            return;
        }

        throw new PhabricatorMetaMTAReceivedMailProcessingException(
            MetaMTAReceivedMailStatus::STATUS_FROM_PHABRICATOR,
            pht(
                "Ignoring email with '%s' header to avoid loops.",
                'X-Phabricator-Sent-This-Message'));
    }

    /**
     * If this mail has the same message ID as some other mail, and isn't the
     * first mail we we received with that message ID, we drop it as a duplicate.
     * @throws PhabricatorMetaMTAReceivedMailProcessingException
     * @throws \yii\base\InvalidConfigException
     */
    private function dropMailAlreadyReceived()
    {
        $message_id_hash = $this->getMessageIDHash();
        if (!$message_id_hash) {
            // No message ID hash, so we can't detect duplicates. This should only
            // happen with very old messages.
            return;
        }

        $messages = self::find()->andWhere(['message_id_hash' => $message_id_hash])->orderBy("id asc")->limit(2)->all();
        $messages_count = count($messages);
        if ($messages_count <= 1) {
            // If we only have one copy of this message, we're good to process it.
            return;
        }

        $first_message = reset($messages);
        if ($first_message->getID() == $this->getID()) {
            // If this is the first copy of the message, it is okay to process it.
            // We may not have been able to to process it immediately when we received
            // it, and could may have received several copies without processing any
            // yet.
            return;
        }

        $message = pht(
            'Ignoring email with "Message-ID" hash "%s" that has been seen %d ' .
            'times, including this message.',
            $message_id_hash,
            $messages_count);

        throw new PhabricatorMetaMTAReceivedMailProcessingException(
            MetaMTAReceivedMailStatus::STATUS_DUPLICATE,
            $message);
    }

    /**
     * @throws PhabricatorMetaMTAReceivedMailProcessingException
     * @author 陈妙威
     */
    private function dropEmptyMail()
    {
        $body = $this->getCleanTextBody();
        $attachments = $this->getAttachments();

        if (strlen($body) || $attachments) {
            return;
        }

        // Only send an error email if the user is talking to just Phabricator.
        // We can assume if there is only one "To" address it is a Phabricator
        // address since this code is running and everything.
        $is_direct_mail = (count($this->getToAddresses()) == 1) &&
            (count($this->getCCAddresses()) == 0);

        if ($is_direct_mail) {
            $status_code = MetaMTAReceivedMailStatus::STATUS_EMPTY;
        } else {
            $status_code = MetaMTAReceivedMailStatus::STATUS_EMPTY_IGNORED;
        }

        throw new PhabricatorMetaMTAReceivedMailProcessingException(
            $status_code,
            pht(
                'Your message does not contain any body text or attachments, so ' .
                'Phabricator can not do anything useful with it. Make sure comment ' .
                'text appears at the top of your message: quoted replies, inline ' .
                'text, and signatures are discarded and ignored.'));
    }

    /**
     * @param Exception $ex
     * @param PhabricatorUser|null $viewer
     * @author 陈妙威
     * @throws Exception
     */
    private function sendExceptionMail(
        Exception $ex,
        PhabricatorUser $viewer = null)
    {

        // If we've failed to identify a legitimate sender, we don't send them
        // an error message back. We want to avoid sending mail to unverified
        // addresses. See T12491.
        if (!$viewer) {
            return;
        }

        if ($ex instanceof PhabricatorMetaMTAReceivedMailProcessingException) {
            $status_code = $ex->getStatusCode();
            $status_name = MetaMTAReceivedMailStatus::getHumanReadableName(
                $status_code);

            $title = pht('Error Processing Mail (%s)', $status_name);
            $description = $ex->getMessage();
        } else {
            $title = pht('Error Processing Mail (%s)', get_class($ex));
            $description = pht('%s: %s', get_class($ex), $ex->getMessage());
        }

        // TODO: Since headers don't necessarily have unique names, this may not
        // really be all the headers. It would be nice to pass the raw headers
        // through from the upper layers where possible.

        // On the MimeMailParser pathway, we arrive here with a list value for
        // headers that appeared multiple times in the original mail. Be
        // accommodating until header handling gets straightened out.

        $headers = array();
        foreach ($this->getHeaders() as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            foreach ($values as $value) {
                $headers[] = pht('%s: %s', $key, $value);
            }
        }
        $headers = implode("\n", $headers);

        $body = pht(<<<EOBODY
Your email to Phabricator was not processed, because an error occurred while
trying to handle it:

%s

-- Original Message Body -----------------------------------------------------

%s

-- Original Message Headers --------------------------------------------------

%s

EOBODY
            ,
            wordwrap($description, 78),
            $this->getRawTextBody(),
            $headers);

        (new PhabricatorMetaMTAMail())
            ->setIsErrorEmail(true)
            ->setSubject($title)
            ->addTos(array($viewer->getPHID()))
            ->setBody($body)
            ->saveAndSend();
    }

    /**
     * @return PhabricatorContentSource
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function newContentSource()
    {
        return PhabricatorContentSource::newForSource(
            PhabricatorEmailContentSource::SOURCECONST,
            array(
                'id' => $this->getID(),
            ));
    }

    /**
     * @return null|PhutilEmailAddress
     * @author 陈妙威
     */
    public function newFromAddress()
    {
        $raw_from = $this->getHeader('From');

        if (strlen($raw_from)) {
            return new PhutilEmailAddress($raw_from);
        }

        return null;
    }

    /**
     * @return PhabricatorUser
     * @author 陈妙威
     */
    private function getViewer()
    {
        return PhabricatorUser::getOmnipotentUser();
    }

    /**
     * Identify the sender's user account for a piece of received mail.
     *
     * Note that this method does not validate that the sender is who they say
     * they are, just that they've presented some credential which corresponds
     * to a recognizable user.
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     */
    private function loadSender()
    {
        $viewer = $this->getViewer();

        // Try to identify the user based on their "From" address.
        $from_address = $this->newFromAddress();
        if ($from_address) {
            $user = PhabricatorUser::find()
                ->setViewer($viewer)
                ->withEmails(array($from_address->getAddress()))
                ->executeOne();
            if ($user) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @param PhabricatorUser $sender
     * @throws PhabricatorMetaMTAReceivedMailProcessingException
     * @throws \yii\base\Exception
     * @throws Exception
     * @author 陈妙威
     */
    private function validateSender(PhabricatorUser $sender)
    {
        $failure_reason = null;
        if ($sender->getIsDisabled()) {
            $failure_reason = pht(
                'Your account ("%s") is disabled, so you can not interact with ' .
                'Phabricator over email.',
                $sender->getUsername());
        } else if ($sender->getIsStandardUser()) {
            if (!$sender->getIsApproved()) {
                $failure_reason = pht(
                    'Your account ("%s") has not been approved yet. You can not ' .
                    'interact with Phabricator over email until your account is ' .
                    'approved.',
                    $sender->getUsername());
            } else if (PhabricatorUserEmail::isEmailVerificationRequired() &&
                !$sender->getIsEmailVerified()) {
                $failure_reason = pht(
                    'You have not verified the email address for your account ("%s"). ' .
                    'You must verify your email address before you can interact ' .
                    'with Phabricator over email.',
                    $sender->getUsername());
            }
        }

        if ($failure_reason) {
            throw new PhabricatorMetaMTAReceivedMailProcessingException(
                MetaMTAReceivedMailStatus::STATUS_DISABLED_SENDER,
                $failure_reason);
        }
    }
}
