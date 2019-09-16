<?php

namespace orangins\modules\metamta\models;

use Filesystem;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\lib\infrastructure\edges\editor\PhabricatorEdgeEditor;
use orangins\lib\infrastructure\edges\interfaces\PhabricatorEdgeInterface;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\metamta\adapters\PhabricatorMailImplementationAdapter;
use orangins\modules\metamta\edge\PhabricatorMetaMTAMailHasRecipientEdgeType;
use orangins\modules\metamta\models\exception\PhabricatorMetaMTAPermanentFailureException;
use orangins\modules\metamta\query\PhabricatorMetaMTAMailQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\settings\setting\PhabricatorEmailFormatSetting;
use orangins\modules\settings\setting\PhabricatorEmailNotificationsSetting;
use orangins\modules\settings\setting\PhabricatorEmailRePrefixSetting;
use orangins\modules\settings\setting\PhabricatorEmailSelfActionsSetting;
use orangins\modules\settings\setting\PhabricatorEmailStampsSetting;
use orangins\modules\settings\setting\PhabricatorEmailTagsSetting;
use orangins\modules\settings\setting\PhabricatorEmailVarySubjectsSetting;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\system\engine\PhabricatorSystemActionEngine;
use orangins\modules\system\exception\PhabricatorSystemActionRateLimitException;
use orangins\modules\metamta\constants\PhabricatorMailOutboundStatus;
use orangins\modules\metamta\constants\PhabricatorMailRoutingRule;
use orangins\modules\metamta\phid\PhabricatorMetaMTAMailPHIDType;
use orangins\modules\metamta\query\PhabricatorMetaMTAActor;
use orangins\modules\metamta\query\PhabricatorMetaMTAActorQuery;
use orangins\modules\metamta\query\PhabricatorMetaMTAMemberQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use PhutilAggregateException;
use PhutilClassMapQuery;
use PhutilTypeSpec;
use PhutilUTF8StringTruncator;
use PhutilEmailAddress;
use Yii;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "metamta_mail".
 *
 * @property int $id
 * @property string $phid
 * @property string $actor_phid
 * @property array $parameters
 * @property string $message
 * @property string $related_phid
 * @property string $created_at
 * @property string $updated_at
 * @property string status
 */
class PhabricatorMetaMTAMail extends ActiveRecordPHID
    implements PhabricatorPolicyInterface,
    PhabricatorEdgeInterface
{
    /**
     * @var
     */
    public $recipientExpansionMap = null;

    /**
     * @var
     */
    public $routingMap;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'metamta_mail';
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parameters'], 'required'],
            [['message', 'status'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'actor_phid', 'related_phid'], 'string', 'max' => 64],
            [['status'], 'string', 'max' => 32],
            [['status'], 'default', 'value' => PhabricatorMailOutboundStatus::STATUS_QUEUE],
            [['phid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'phid' => Yii::t('app', 'PHID'),
            'actor_phid' => Yii::t('app', 'Actor PHID'),
            'parameters' => Yii::t('app', 'Parameters'),
            'status' => Yii::t('app', 'State'),
            'message' => Yii::t('app', 'Message'),
            'related_phid' => Yii::t('app', 'Related PHID'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }


    /**
     * @param $param
     * @param $value
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    protected function setParam($param, $value)
    {
        $params = $this->parameters === null ? [] : phutil_json_decode($this->parameters);
        $params[$param] = $value;
        $this->parameters = phutil_json_encode($params);
        return $this;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    protected function getParams()
    {
        $params = $this->parameters === null ? [] : phutil_json_decode($this->parameters);
        return $params;
    }


    /**
     * @param $param
     * @param null $default
     * @return object
     * @author 陈妙威
     */
    protected function getParam($param, $default = null)
    {
        $params = $this->parameters === null ? [] : phutil_json_decode($this->parameters);
        return ArrayHelper::getValue($params, $param, $default);
    }

    /**
     * These tags are used to allow users to opt out of receiving certain types
     * of mail, like updates when a task's projects change.
     *
     * @param array<const>
     * @return static
     * @throws \Exception
     */
    public function setMailTags(array $tags)
    {
        $this->setParam('mailtags', array_unique($tags));
        return $this;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getMailTags()
    {
        return $this->getParam('mailtags', array());
    }

    /**
     * In Gmail, conversations will be broken if you reply to a thread and the
     * server sends back a response without referencing your Message-ID, even if
     * it references a Message-ID earlier in the thread. To avoid this, use the
     * parent email's message ID explicitly if it's available. This overwrites the
     * "In-Reply-To" and "References" headers we would otherwise generate. This
     * needs to be set whenever an action is triggered by an email message. See
     * T251 for more details.
     *
     * @param   string The "Message-ID" of the email which precedes this one.
     * @return  static
     * @throws \Exception
     */
    public function setParentMessageID($id)
    {
        $this->setParam('parent-message-id', $id);
        return $this;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getParentMessageID()
    {
        return $this->getParam('parent-message-id');
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getSubject()
    {
        return $this->getParam('subject');
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function addTos(array $phids)
    {
        $phids = array_unique($phids);
        $this->setParam('to', $phids);
        return $this;
    }

    /**
     * @param array $raw_email
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function addRawTos(array $raw_email)
    {

        // Strip addresses down to bare emails, since the MailAdapter API currently
        // requires we pass it just the address (like `alincoln@logcabin.org`), not
        // a full string like `"Abraham Lincoln" <alincoln@logcabin.org>`.
        foreach ($raw_email as $key => $email) {
            $object = new PhutilEmailAddress($email);
            $raw_email[$key] = $object->getAddress();
        }

        $this->setParam('raw-to', $raw_email);
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function addCCs(array $phids)
    {
        $phids = array_unique($phids);
        $this->setParam('cc', $phids);
        return $this;
    }

    /**
     * @param array $exclude
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function setExcludeMailRecipientPHIDs(array $exclude)
    {
        $this->setParam('exclude', $exclude);
        return $this;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    private function getExcludeMailRecipientPHIDs()
    {
        return $this->getParam('exclude', array());
    }

    /**
     * @param array $muted
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function setMutedPHIDs(array $muted)
    {
        $this->setParam('muted', $muted);
        return $this;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    private function getMutedPHIDs()
    {
        return $this->getParam('muted', array());
    }

    /**
     * @param array $force
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function setForceHeraldMailRecipientPHIDs(array $force)
    {
        $this->setParam('herald-force-recipients', $force);
        return $this;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    private function getForceHeraldMailRecipientPHIDs()
    {
        return $this->getParam('herald-force-recipients', array());
    }

    /**
     * @param $name
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function addPHIDHeaders($name, array $phids)
    {
        $phids = array_unique($phids);
        foreach ($phids as $phid) {
            $this->addHeader($name, '<' . $phid . '>');
        }
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function addHeader($name, $value)
    {
        $this->parameters['headers'][] = array($name, $value);
        return $this;
    }

    /**
     * @param PhabricatorMetaMTAAttachment $attachment
     * @return $this
     * @author 陈妙威
     */
    public function addAttachment(PhabricatorMetaMTAAttachment $attachment)
    {
        $this->parameters['attachments'][] = $attachment->toDictionary();
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAttachments()
    {
        $dicts = $this->getParam('attachments');

        $result = array();
        foreach ($dicts as $dict) {
            $result[] = PhabricatorMetaMTAAttachment::newFromDictionary($dict);
        }
        return $result;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAttachmentFilePHIDs()
    {
        $file_phids = array();

        $dictionaries = $this->getParam('attachments');
        if ($dictionaries) {
            foreach ($dictionaries as $dictionary) {
                $file_phid = ArrayHelper::getValue($dictionary, 'filePHID');
                if ($file_phid) {
                    $file_phids[] = $file_phid;
                }
            }
        }

        return $file_phids;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return array
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function loadAttachedFiles(PhabricatorUser $viewer)
    {
        $file_phids = $this->getAttachmentFilePHIDs();

        if (!$file_phids) {
            return array();
        }

        return PhabricatorFile::find()
            ->setViewer($viewer)
            ->withPHIDs($file_phids)
            ->execute();
    }

    /**
     * @param array $attachments
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function setAttachments(array $attachments)
    {
        assert_instances_of($attachments, PhabricatorMetaMTAAttachment::className());
        $this->setParam('attachments', mpull($attachments, 'toDictionary'));
        return $this;
    }

    /**
     * @param $from
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function setFrom($from)
    {
        $this->setParam('from', $from);
        $this->setActorPHID($from);
        return $this;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getFrom()
    {
        return $this->getParam('from');
    }

    /**
     * @param $raw_email
     * @param $raw_name
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function setRawFrom($raw_email, $raw_name)
    {
        $this->setParam('raw-from', array($raw_email, $raw_name));
        return $this;
    }

    /**
     * @param $reply_to
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function setReplyTo($reply_to)
    {
        $this->setParam('reply-to', $reply_to);
        return $this;
    }

    /**
     * @param $subject
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function setSubject($subject)
    {
        $this->setParam('subject', $subject);
        return $this;
    }

    /**
     * @param $prefix
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function setSubjectPrefix($prefix)
    {
        $this->setParam('subject-prefix', $prefix);
        return $this;
    }

    /**
     * @param $prefix
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function setVarySubjectPrefix($prefix)
    {
        $this->setParam('vary-subject-prefix', $prefix);
        return $this;
    }

    /**
     * @param $body
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function setBody($body)
    {
        $this->setParam('body', $body);
        return $this;
    }

    /**
     * @param $bool
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function setSensitiveContent($bool)
    {
        $this->setParam('sensitive', $bool);
        return $this;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function hasSensitiveContent()
    {
        return $this->getParam('sensitive', true);
    }

    /**
     * @param $bool
     * @return PhabricatorMetaMTAMail
     * @author 陈妙威
     * @throws \Exception
     */
    public function setMustEncrypt($bool)
    {
        return $this->setParam('mustEncrypt', $bool);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getMustEncrypt()
    {
        return $this->getParam('mustEncrypt', false);
    }

    /**
     * @param $uri
     * @return PhabricatorMetaMTAMail
     * @author 陈妙威
     * @throws \Exception
     */
    public function setMustEncryptURI($uri)
    {
        return $this->setParam('mustEncrypt.uri', $uri);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getMustEncryptURI()
    {
        return $this->getParam('mustEncrypt.uri');
    }

    /**
     * @param $subject
     * @return PhabricatorMetaMTAMail
     * @author 陈妙威
     * @throws \Exception
     */
    public function setMustEncryptSubject($subject)
    {
        return $this->setParam('mustEncrypt.subject', $subject);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getMustEncryptSubject()
    {
        return $this->getParam('mustEncrypt.subject');
    }

    /**
     * @param array $reasons
     * @return PhabricatorMetaMTAMail
     * @author 陈妙威
     * @throws \Exception
     */
    public function setMustEncryptReasons(array $reasons)
    {
        return $this->setParam('mustEncryptReasons', $reasons);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getMustEncryptReasons()
    {
        return $this->getParam('mustEncryptReasons', array());
    }

    /**
     * @param array $stamps
     * @return PhabricatorMetaMTAMail
     * @author 陈妙威
     * @throws \Exception
     */
    public function setMailStamps(array $stamps)
    {
        return $this->setParam('stamps', $stamps);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getMailStamps()
    {
        return $this->getParam('stamps', array());
    }

    /**
     * @param $metadata
     * @return PhabricatorMetaMTAMail
     * @author 陈妙威
     * @throws \Exception
     */
    public function setMailStampMetadata($metadata)
    {
        return $this->setParam('stampMetadata', $metadata);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getMailStampMetadata()
    {
        return $this->getParam('stampMetadata', array());
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getMailerKey()
    {
        return $this->getParam('mailer.key');
    }

    /**
     * @param array $mailers
     * @return PhabricatorMetaMTAMail
     * @author 陈妙威
     * @throws \Exception
     */
    public function setTryMailers(array $mailers)
    {
        return $this->setParam('mailers.try', $mailers);
    }

    /**
     * @param $html
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function setHTMLBody($html)
    {
        $this->setParam('html-body', $html);
        return $this;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getBody()
    {
        return $this->getParam('body');
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getHTMLBody()
    {
        return $this->getParam('html-body');
    }

    /**
     * @param $is_error
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function setIsErrorEmail($is_error)
    {
        $this->setParam('is-error', $is_error);
        return $this;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getIsErrorEmail()
    {
        return $this->getParam('is-error', false);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getToPHIDs()
    {
        return $this->getParam('to', array());
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getRawToAddresses()
    {
        return $this->getParam('raw-to', array());
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getCcPHIDs()
    {
        return $this->getParam('cc', array());
    }

    /**
     * Force delivery of a message, even if recipients have preferences which
     * would otherwise drop the message.
     *
     * This is primarily intended to let users who don't want any email still
     * receive things like password resets.
     *
     * @param bool  True to force delivery despite user preferences.
     * @return static
     * @throws \Exception
     */
    public function setForceDelivery($force)
    {
        $this->setParam('force', $force);
        return $this;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getForceDelivery()
    {
        return $this->getParam('force', false);
    }

    /**
     * Flag that this is an auto-generated bulk message and should have bulk
     * headers added to it if appropriate. Broadly, this means some flavor of
     * "Precedence: bulk" or similar, but is implementation and configuration
     * dependent.
     *
     * @param bool  True if the mail is automated bulk mail.
     * @return static
     * @throws \Exception
     */
    public function setIsBulk($is_bulk)
    {
        $this->setParam('is-bulk', $is_bulk);
        return $this;
    }

    /**
     * Use this method to set an ID used for message threading. MetaMTA will
     * set appropriate headers (Message-ID, In-Reply-To, References and
     * Thread-Index) based on the capabilities of the underlying mailer.
     *
     * @param string  Unique identifier, appropriate for use in a Message-ID,
     *                In-Reply-To or References headers.
     * @param bool    If true, indicates this is the first message in the thread.
     * @return static
     * @throws \Exception
     */
    public function setThreadID($thread_id, $is_first_message = false)
    {
        $this->setParam('thread-id', $thread_id);
        $this->setParam('is-first-message', $is_first_message);
        return $this;
    }

    /**
     * Save a newly created mail to the database. The mail will eventually be
     * delivered by the MetaMTA daemon.
     *
     * @return bool
     * @throws Exception
     * @throws \AphrontQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     */
    public function saveAndSend()
    {
        return $this->save();
    }

    /**
     * @param bool $runValidation
     * @param null $attributeNames
     * @return bool
     * @throws Exception
     * @throws \AphrontQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if ($this->getID()) {
            return parent::save($runValidation, $attributeNames);
        }

        // NOTE: When mail is sent from CLI scripts that run tasks in-process, we
        // may re-enter this method from within scheduleTask(). The implementation
        // is intended to avoid anything awkward if we end up reentering this
        // method.

        $this->openTransaction();
        // Save to generate a mail ID and PHID.
        $result = parent::save();

        // Write the recipient edges.
        $editor = new PhabricatorEdgeEditor();
        $edge_type = PhabricatorMetaMTAMailHasRecipientEdgeType::EDGECONST;
        $recipient_phids = array_merge(
            $this->getToPHIDs(),
            $this->getCcPHIDs());
        $expanded_phids = $this->expandRecipients($recipient_phids);
        $all_phids = array_unique(array_merge(
            $recipient_phids,
            $expanded_phids));
        foreach ($all_phids as $curr_phid) {
            $editor->addEdge($this->getPHID(), $edge_type, $curr_phid);
        }
        $editor->save();

        $this->saveTransaction();

        // Queue a task to send this mail.
        $mailer_task = PhabricatorWorker::scheduleTask(
            'PhabricatorMetaMTAWorker',
            $this->getID(),
            array(
                'priority' => PhabricatorWorker::PRIORITY_ALERTS,
            ));

        return $result;
    }

    /**
     * Attempt to deliver an email immediately, in this process.
     *
     * @return mixed
     * @throws PhabricatorMetaMTAPermanentFailureException
     * @throws PhutilAggregateException
     * @throws \AphrontQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws \yii\base\Exception
     * @throws Exception
     */
    public function sendNow()
    {
        if ($this->getStatus() != PhabricatorMailOutboundStatus::STATUS_QUEUE) {
            throw new Exception(\Yii::t("app",'Trying to send an already-sent mail!'));
        }

        $mailers = self::newMailers(
            array(
                'outbound' => true,
            ));

        $try_mailers = $this->getParam('mailers.try');
        if ($try_mailers) {
            $mailers = mpull($mailers, null, 'getKey');
            $mailers = array_select_keys($mailers, $try_mailers);
        }

        return $this->sendWithMailers($mailers);
    }

    /**
     * @param array $constraints
     * @return array
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws Exception
     * @author 陈妙威
     */
    public static function newMailers(array $constraints)
    {
        PhutilTypeSpec::checkMap(
            $constraints,
            array(
                'types' => 'optional list<string>',
                'inbound' => 'optional bool',
                'outbound' => 'optional bool',
            ));

        $mailers = array();

        $config = PhabricatorEnv::getEnvConfig('cluster.mailers');
        if ($config === null) {

            $execute = (new PhutilClassMapQuery())
                ->setUniqueMethod('getClassShortName')
                ->setAncestorClass(PhabricatorMailImplementationAdapter::className())
                ->execute();
            /** @var PhabricatorMailImplementationAdapter $mailer */
            $mailer = $execute[PhabricatorEnv::getEnvConfig('metamta.mail-adapter')];

            $defaults = $mailer->newDefaultOptions();
            $options = $mailer->newLegacyOptions();

            $options = $options + $defaults;

            $mailer
                ->setKey('default')
                ->setPriority(-1)
                ->setOptions($options);

            $mailers[] = $mailer;
        } else {
            $adapters = PhabricatorMailImplementationAdapter::getAllAdapters();
            $next_priority = -1;

            foreach ($config as $spec) {
                $type = $spec['type'];
                if (!isset($adapters[$type])) {
                    throw new Exception(
                        \Yii::t("app",
                            'Unknown mailer ("%s")!',
                            $type));
                }

                $key = $spec['key'];
                /** @var PhabricatorMailImplementationAdapter $adapter */
                $adapter = clone $adapters[$type];
                $mailer = $adapter
                    ->setKey($key);

                $priority = ArrayHelper::getValue($spec, 'priority');
                if (!$priority) {
                    $priority = $next_priority;
                    $next_priority--;
                }
                $mailer->setPriority($priority);

                $defaults = $mailer->newDefaultOptions();
                $options = ArrayHelper::getValue($spec, 'options', array()) + $defaults;
                $mailer->setOptions($options);

                $mailer->setSupportsInbound(ArrayHelper::getValue($spec, 'inbound', true));
                $mailer->setSupportsOutbound(ArrayHelper::getValue($spec, 'outbound', true));

                $mailers[] = $mailer;
            }
        }

        // Remove mailers with the wrong types.
        if (isset($constraints['types'])) {
            $types = $constraints['types'];
            $types = array_fuse($types);
            foreach ($mailers as $key => $mailer) {
                $mailer_type = $mailer->getAdapterType();
                if (!isset($types[$mailer_type])) {
                    unset($mailers[$key]);
                }
            }
        }

        // If we're only looking for inbound mailers, remove mailers with inbound
        // support disabled.
        if (!empty($constraints['inbound'])) {
            foreach ($mailers as $key => $mailer) {
                if (!$mailer->getSupportsInbound()) {
                    unset($mailers[$key]);
                }
            }
        }

        // If we're only looking for outbound mailers, remove mailers with outbound
        // support disabled.
        if (!empty($constraints['outbound'])) {
            foreach ($mailers as $key => $mailer) {
                if (!$mailer->getSupportsOutbound()) {
                    unset($mailers[$key]);
                }
            }
        }

        $sorted = array();
        $groups = mgroup($mailers, 'getPriority');
        krsort($groups);
        foreach ($groups as $group) {
            // Reorder services within the same priority group randomly.
            shuffle($group);
            foreach ($group as $mailer) {
                $sorted[] = $mailer;
            }
        }

        foreach ($sorted as $mailer) {
            $mailer->prepareForSend();
        }

        return $sorted;
    }

    /**
     * @param array $mailers
     * @return mixed
     * @throws PhabricatorMetaMTAPermanentFailureException
     * @throws PhutilAggregateException
     * @throws \AphrontQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function sendWithMailers(array $mailers)
    {
        if (!$mailers) {
            $any_mailers = self::newMailers(array());

            // NOTE: We can end up here with some custom list of "$mailers", like
            // from a unit test. In that case, this message could be misleading. We
            // can't really tell if the caller made up the list, so just assume they
            // aren't tricking us.

            if ($any_mailers) {
                $void_message = \Yii::t("app",
                    'No configured mailers support sending outbound mail.');
            } else {
                $void_message = \Yii::t("app",
                    'No mailers are configured.');
            }

            return $this
                ->setStatus(PhabricatorMailOutboundStatus::STATUS_VOID)
                ->setMessage($void_message)
                ->save();
        }

        $exceptions = array();
        foreach ($mailers as $template_mailer) {
            $mailer = null;

            try {
                $mailer = $this->buildMailer($template_mailer);
            } catch (Exception $ex) {
                $exceptions[] = $ex;
                continue;
            }

            if (!$mailer) {
                // If we don't get a mailer back, that means the mail doesn't
                // actually need to be sent (for example, because recipients have
                // declined to receive the mail). Void it and return.
                return $this
                    ->setStatus(PhabricatorMailOutboundStatus::STATUS_VOID)
                    ->save();
            }

            try {
                $ok = $mailer->send();
                if (!$ok) {
                    // TODO: At some point, we should clean this up and make all mailers
                    // throw.
                    throw new Exception(
                        \Yii::t("app",
                            'Mail adapter encountered an unexpected, unspecified ' .
                            'failure.'));
                }
            } catch (PhabricatorMetaMTAPermanentFailureException $ex) {
                // If any mailer raises a permanent failure, stop trying to send the
                // mail with other mailers.
                $this
                    ->setStatus(PhabricatorMailOutboundStatus::STATUS_FAIL)
                    ->setMessage($ex->getMessage())
                    ->save();

                throw $ex;
            } catch (Exception $ex) {
                $exceptions[] = $ex;
                continue;
            }

            // Keep track of which mailer actually ended up accepting the message.
            $mailer_key = $mailer->getKey();
            if ($mailer_key !== null) {
                $this->setParam('mailer.key', $mailer_key);
            }

            $save = $this
                ->setStatus(PhabricatorMailOutboundStatus::STATUS_SENT)
                ->save();
            return $save;
        }

        // If we make it here, no mailer could send the mail but no mailer failed
        // permanently either. We update the error message for the mail, but leave
        // it in the current status (usually, STATUS_QUEUE) and try again later.

        $messages = array();
        foreach ($exceptions as $ex) {
            $messages[] = $ex->getMessage();
        }
        $messages = implode("\n\n", $messages);

        $this
            ->setMessage($messages)
            ->save();

        if (count($exceptions) === 1) {
            throw head($exceptions);
        }

        throw new PhutilAggregateException(
            \Yii::t("app",'Encountered multiple exceptions while transmitting mail.'),
            $exceptions);
    }

    /**
     * @param PhabricatorMailImplementationAdapter $mailer
     * @return PhabricatorMailImplementationAdapter
     * @throws Exception
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException

     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildMailer(PhabricatorMailImplementationAdapter $mailer)
    {
        $headers = $this->generateHeaders();

        $params = $this->getParams();

        $actors = $this->loadAllActors();
        $deliverable_actors = $this->filterDeliverableActors($actors);

        $default_from = PhabricatorEnv::getEnvConfig('metamta.default-address');
        if (empty($params['from'])) {
            $mailer->setFrom($default_from);
        }

        $is_first = ArrayHelper::getValue($params, 'is-first-message');
        unset($params['is-first-message']);

        $is_threaded = (bool)ArrayHelper::getValue($params, 'thread-id');
        $must_encrypt = $this->getMustEncrypt();

        $reply_to_name = ArrayHelper::getValue($params, 'reply-to-name', '');
        unset($params['reply-to-name']);

        $add_cc = array();
        $add_to = array();

        // If we're sending one mail to everyone, some recipients will be in
        // "Cc" rather than "To". We'll move them to "To" later (or supply a
        // dummy "To") but need to look for the recipient in either the
        // "To" or "Cc" fields here.
        $target_phid = head(ArrayHelper::getValue($params, 'to', array()));
        if (!$target_phid) {
            $target_phid = head(ArrayHelper::getValue($params, 'cc', array()));
        }

        $preferences = $this->loadPreferences($target_phid);

        foreach ($params as $key => $value) {
            switch ($key) {
                case 'raw-from':
                    list($from_email, $from_name) = $value;
                    $mailer->setFrom($from_email, $from_name);
                    break;
                case 'from':
                    // If the mail content must be encrypted, disguise the sender.
                    if ($must_encrypt) {
                        $mailer->setFrom($default_from, \Yii::t("app",'Phabricator'));
                        break;
                    }

                    $from = $value;
                    $actor_email = null;
                    $actor_name = null;

                    /** @var PhabricatorMetaMTAActor $actor */
                    $actor = ArrayHelper::getValue($actors, $from);
                    if ($actor) {
                        $actor_email = $actor->getEmailAddress();
                        $actor_name = $actor->getName();
                    }
                    $can_send_as_user = $actor_email &&
                        PhabricatorEnv::getEnvConfig('metamta.can-send-as-user');

                    if ($can_send_as_user) {
                        $mailer->setFrom($actor_email, $actor_name);
                    } else {
                        $from_email = coalesce($actor_email, $default_from);
                        $from_name = coalesce($actor_name, \Yii::t("app",'Phabricator'));

                        if (empty($params['reply-to'])) {
                            $params['reply-to'] = $from_email;
                            $params['reply-to-name'] = $from_name;
                        }

                        $mailer->setFrom($default_from, $from_name);
                    }
                    break;
                case 'reply-to':
                    $mailer->addReplyTo($value, $reply_to_name);
                    break;
                case 'to':
                    $to_phids = $this->expandRecipients($value);
                    $to_actors = array_select_keys($deliverable_actors, $to_phids);
                    $add_to = array_merge(
                        $add_to,
                        mpull($to_actors, 'getEmailAddress'));
                    break;
                case 'raw-to':
                    $add_to = array_merge($add_to, $value);
                    break;
                case 'cc':
                    $cc_phids = $this->expandRecipients($value);
                    $cc_actors = array_select_keys($deliverable_actors, $cc_phids);
                    $add_cc = array_merge(
                        $add_cc,
                        mpull($cc_actors, 'getEmailAddress'));
                    break;
                case 'attachments':
                    $attached_viewer = PhabricatorUser::getOmnipotentUser();
                    $files = $this->loadAttachedFiles($attached_viewer);
                    foreach ($files as $file) {
                        $file->attachToObject($this->getPHID());
                    }

                    // If the mail content must be encrypted, don't add attachments.
                    if ($must_encrypt) {
                        break;
                    }

                    $value = $this->getAttachments();
                    foreach ($value as $attachment) {
                        $mailer->addAttachment(
                            $attachment->getData(),
                            $attachment->getFilename(),
                            $attachment->getMimeType());
                    }
                    break;
                case 'subject':
                    $subject = array();

                    if ($is_threaded) {
                        if ($this->shouldAddRePrefix($preferences)) {
                            $subject[] = 'Re:';
                        }
                    }

                    $subject[] = trim(ArrayHelper::getValue($params, 'subject-prefix'));

                    // If mail content must be encrypted, we replace the subject with
                    // a generic one.
                    if ($must_encrypt) {
                        $encrypt_subject = $this->getMustEncryptSubject();
                        if (!strlen($encrypt_subject)) {
                            $encrypt_subject = \Yii::t("app",'Object Updated');
                        }
                        $subject[] = $encrypt_subject;
                    } else {
                        $vary_prefix = ArrayHelper::getValue($params, 'vary-subject-prefix');
                        if ($vary_prefix != '') {
                            if ($this->shouldVarySubject($preferences)) {
                                $subject[] = $vary_prefix;
                            }
                        }

                        $subject[] = $value;
                    }

                    $mailer->setSubject(implode(' ', array_filter($subject)));
                    break;
                case 'thread-id':

                    // NOTE: Gmail freaks out about In-Reply-To and References which
                    // aren't in the form "<string@domain.tld>"; this is also required
                    // by RFC 2822, although some clients are more liberal in what they
                    // accept.
                    $domain = PhabricatorEnv::getEnvConfig('metamta.domain');
                    $value = '<' . $value . '@' . $domain . '>';

                    if ($is_first && $mailer->supportsMessageIDHeader()) {
                        $headers[] = array('Message-ID', $value);
                    } else {
                        $in_reply_to = $value;
                        $references = array($value);
                        $parent_id = $this->getParentMessageID();
                        if ($parent_id) {
                            $in_reply_to = $parent_id;
                            // By RFC 2822, the most immediate parent should appear last
                            // in the "References" header, so this order is intentional.
                            $references[] = $parent_id;
                        }
                        $references = implode(' ', $references);
                        $headers[] = array('In-Reply-To', $in_reply_to);
                        $headers[] = array('References', $references);
                    }
                    $thread_index = $this->generateThreadIndex($value, $is_first);
                    $headers[] = array('Thread-Index', $thread_index);
                    break;
                default:
                    // Other parameters are handled elsewhere or are not relevant to
                    // constructing the message.
                    break;
            }
        }

        $stamps = $this->getMailStamps();
        if ($stamps) {
            $headers[] = array('X-Phabricator-Stamps', implode(' ', $stamps));
        }

        $raw_body = ArrayHelper::getValue($params, 'body', '');
        $body = $raw_body;
        if ($must_encrypt) {
            $parts = array();

            $encrypt_uri = $this->getMustEncryptURI();
            if (!strlen($encrypt_uri)) {
                $encrypt_phid = $this->getRelatedPHID();
                if ($encrypt_phid) {
                    $encrypt_uri = urisprintf(
                        '/object/%s/',
                        $encrypt_phid);
                }
            }

            if (strlen($encrypt_uri)) {
                $parts[] = \Yii::t("app",
                    'This secure message is notifying you of a change to this object:');
                $parts[] = PhabricatorEnv::getProductionURI($encrypt_uri);
            }

            $parts[] = \Yii::t("app",
                'The content for this message can only be transmitted over a ' .
                'secure channel. To view the message content, follow this ' .
                'link:');

            $parts[] = PhabricatorEnv::getProductionURI($this->getURI());

            $body = implode("\n\n", $parts);
        } else {
            $body = $raw_body;
        }

        $body_limit = PhabricatorEnv::getEnvConfig('metamta.email-body-limit');
        if (strlen($body) > $body_limit) {
            $body = (new PhutilUTF8StringTruncator())
                ->setMaximumBytes($body_limit)
                ->truncateString($body);
            $body .= "\n";
            $body .= \Yii::t("app",'(This email was truncated at %d bytes.)', $body_limit);
        }
        $mailer->setBody($body);
        $body_limit -= strlen($body);

        // If we sent a different message body than we were asked to, record
        // what we actually sent to make debugging and diagnostics easier.
        if ($body !== $raw_body) {
            $this->setParam('body.sent', $body);
        }

        if ($must_encrypt) {
            $send_html = false;
        } else {
            $send_html = $this->shouldSendHTML($preferences);
        }

        if ($send_html) {
            $html_body = ArrayHelper::getValue($params, 'html-body');
            if (strlen($html_body)) {
                // NOTE: We just drop the entire HTML body if it won't fit. Safely
                // truncating HTML is hard, and we already have the text body to fall
                // back to.
                if (strlen($html_body) <= $body_limit) {
                    $mailer->setHTMLBody($html_body);
                    $body_limit -= strlen($html_body);
                }
            }
        }

        // Pass the headers to the mailer, then save the state so we can show
        // them in the web UI. If the mail must be encrypted, we remove headers
        // which are not on a strict whitelist to avoid disclosing information.
        $filtered_headers = $this->filterHeaders($headers, $must_encrypt);
        foreach ($filtered_headers as $header) {
            list($header_key, $header_value) = $header;
            $mailer->addHeader($header_key, $header_value);
        }
        $this->setParam('headers.unfiltered', $headers);
        $this->setParam('headers.sent', $filtered_headers);

        // Save the final deliverability outcomes and reasoning so we can
        // explain why things happened the way they did.
        $actor_list = array();
        foreach ($actors as $actor) {
            $actor_list[$actor->getPHID()] = array(
                'deliverable' => $actor->isDeliverable(),
                'reasons' => $actor->getDeliverabilityReasons(),
            );
        }
        $this->setParam('actors.sent', $actor_list);

        $this->setParam('routing.sent', $this->getParam('routing'));
        $this->setParam('routingmap.sent', $this->getRoutingRuleMap());

        if (!$add_to && !$add_cc) {
            $this->setMessage(
                \Yii::t("app",
                    'Message has no valid recipients: all To/Cc are disabled, ' .
                    'invalid, or configured not to receive this mail.'));

            return null;
        }

        if ($this->getIsErrorEmail()) {
            $all_recipients = array_merge($add_to, $add_cc);
            if ($this->shouldRateLimitMail($all_recipients)) {
                $this->setMessage(
                    \Yii::t("app",
                        'This is an error email, but one or more recipients have ' .
                        'exceeded the error email rate limit. Declining to deliver ' .
                        'message.'));

                return null;
            }
        }

        if (PhabricatorEnv::getEnvConfig('orangins.silent')) {
            $this->setMessage(
                \Yii::t("app",
                    'Phabricator is running in silent mode. See `%s` ' .
                    'in the configuration to change this setting.',
                    'phabricator.silent'));

            return null;
        }

        // Some mailers require a valid "To:" in order to deliver mail. If we
        // don't have any "To:", try to fill it in with a placeholder "To:".
        // If that also fails, move the "Cc:" line to "To:".
        if (!$add_to) {
            $placeholder_key = 'metamta.placeholder-to-recipient';
            $placeholder = PhabricatorEnv::getEnvConfig($placeholder_key);
            if ($placeholder !== null) {
                $add_to = array($placeholder);
            } else {
                $add_to = $add_cc;
                $add_cc = array();
            }
        }

        $add_to = array_unique($add_to);
        $add_cc = array_diff(array_unique($add_cc), $add_to);

        $mailer->addTos($add_to);
        if ($add_cc) {
            $mailer->addCCs($add_cc);
        }

        return $mailer;
    }

    /**
     * @param $seed
     * @param $is_first_mail
     * @return string
     * @author 陈妙威
     */
    private function generateThreadIndex($seed, $is_first_mail)
    {
        // When threading, Outlook ignores the 'References' and 'In-Reply-To'
        // headers that most clients use. Instead, it uses a custom 'Thread-Index'
        // header. The format of this header is something like this (from
        // camel-exchange-folder.c in Evolution Exchange):

        /* A new post to a folder gets a 27-byte-long thread index. (The value
         * is apparently unique but meaningless.) Each reply to a post gets a
         * 32-byte-long thread index whose first 27 bytes are the same as the
         * parent's thread index. Each reply to any of those gets a
         * 37-byte-long thread index, etc. The Thread-Index header contains a
         * base64 representation of this value.
         */

        // The specific implementation uses a 27-byte header for the first email
        // a recipient receives, and a random 5-byte suffix (32 bytes total)
        // thereafter. This means that all the replies are (incorrectly) siblings,
        // but it would be very difficult to keep track of the entire tree and this
        // gets us reasonable client behavior.

        $base = substr(md5($seed), 0, 27);
        if (!$is_first_mail) {
            // Not totally sure, but it seems like outlook orders replies by
            // thread-index rather than timestamp, so to get these to show up in the
            // right order we use the time as the last 4 bytes.
            $base .= ' ' . pack('N', time());
        }

        return base64_encode($base);
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public static function shouldMailEachRecipient()
    {
        return PhabricatorEnv::getEnvConfig('metamta.one-mail-per-recipient');
    }


    /* -(  Managing Recipients  )------------------------------------------------ */


    /**
     * Get all of the recipients for this mail, after preference filters are
     * applied. This list has all objects to whom delivery will be attempted.
     *
     * Note that this expands recipients into their members, because delivery
     * is never directly attempted to aggregate actors like projects.
     *
     * @return array<phid>  A list of all recipients to whom delivery will be
     *                      attempted.
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException

     * @throws \yii\base\InvalidConfigException
     * @task recipients
     */
    public function buildRecipientList()
    {
        $actors = $this->loadAllActors();
        $actors = $this->filterDeliverableActors($actors);
        return mpull($actors, 'getPHID');
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException

     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function loadAllActors()
    {
        $actor_phids = $this->getExpandedRecipientPHIDs();
        return $this->loadActors($actor_phids);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getExpandedRecipientPHIDs()
    {
        $actor_phids = $this->getAllActorPHIDs();
        return $this->expandRecipients($actor_phids);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    private function getAllActorPHIDs()
    {
        return array_merge(
            array($this->getParam('from')),
            $this->getToPHIDs(),
            $this->getCcPHIDs());
    }

    /**
     * Expand a list of recipient PHIDs (possibly including aggregate recipients
     * like projects) into a deaggregated list of individual recipient PHIDs.
     * For example, this will expand project PHIDs into a list of the project's
     * members.
     *
     * @param array<phid>  List of recipient PHIDs, possibly including aggregate
     *                    recipients.
     * @return array<phid> Deaggregated list of mailable recipients.
     */
    private function expandRecipients(array $phids)
    {
        if ($this->recipientExpansionMap === null) {
            $all_phids = $this->getAllActorPHIDs();
            $this->recipientExpansionMap = (new PhabricatorMetaMTAMemberQuery())
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withPHIDs($all_phids)
                ->execute();
        }

        $results = array();
        foreach ($phids as $phid) {
            foreach ($this->recipientExpansionMap[$phid] as $recipient_phid) {
                $results[$recipient_phid] = $recipient_phid;
            }
        }

        return array_keys($results);
    }

    /**
     * @param array $actors
     * @return array
     * @author 陈妙威
     */
    private function filterDeliverableActors(array $actors)
    {
        assert_instances_of($actors, PhabricatorMetaMTAActor::className());
        $deliverable_actors = array();
        foreach ($actors as $phid => $actor) {
            if ($actor->isDeliverable()) {
                $deliverable_actors[$phid] = $actor;
            }
        }
        return $deliverable_actors;
    }

    /**
     * @param array $actor_phids
     * @return mixed
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException

     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function loadActors(array $actor_phids)
    {
        $actor_phids = array_filter($actor_phids);
        $viewer = PhabricatorUser::getOmnipotentUser();


        /** @var PhabricatorMetaMTAActor[] $actors */
        $actors = (new PhabricatorMetaMTAActorQuery())
            ->setViewer($viewer)
            ->withPHIDs($actor_phids)
            ->execute();

        if (!$actors) {
            return array();
        }

        if ($this->getForceDelivery()) {
            // If we're forcing delivery, skip all the opt-out checks. We don't
            // bother annotating reasoning on the mail in this case because it should
            // always be obvious why the mail hit this rule (e.g., it is a password
            // reset mail).
            foreach ($actors as $actor) {
                $actor->setDeliverable(PhabricatorMetaMTAActor::REASON_FORCE);
            }
            return $actors;
        }

        // Exclude explicit recipients.
        foreach ($this->getExcludeMailRecipientPHIDs() as $phid) {
            /** @var PhabricatorMetaMTAActor $actor */
            $actor = ArrayHelper::getValue($actors, $phid);
            if (!$actor) {
                continue;
            }
            $actor->setUndeliverable(PhabricatorMetaMTAActor::REASON_RESPONSE);
        }

        // Before running more rules, save a list of the actors who were
        // deliverable before we started running preference-based rules. This stops
        // us from trying to send mail to disabled users just because a Herald rule
        // added them, for example.
        $deliverable = array();
        foreach ($actors as $phid => $actor) {
            if ($actor->isDeliverable()) {
                $deliverable[] = $phid;
            }
        }

        // Exclude muted recipients. We're doing this after saving deliverability
        // so that Herald "Send me an email" actions can still punch through a
        // mute.

        foreach ($this->getMutedPHIDs() as $muted_phid) {
            /** @var PhabricatorMetaMTAActor $muted_actor */
            $muted_actor = ArrayHelper::getValue($actors, $muted_phid);
            if (!$muted_actor) {
                continue;
            }
            $muted_actor->setUndeliverable(PhabricatorMetaMTAActor::REASON_MUTED);
        }

        // For the rest of the rules, order matters. We're going to run all the
        // possible rules in order from weakest to strongest, and let the strongest
        // matching rule win. The weaker rules leave annotations behind which help
        // users understand why the mail was routed the way it was.

        // Exclude the actor if their preferences are set.
        $from_phid = $this->getParam('from');

        /** @var PhabricatorMetaMTAActor $from_actor */
        $from_actor = ArrayHelper::getValue($actors, $from_phid);
        if ($from_actor) {
            $from_user = PhabricatorUser::find()
                ->setViewer($viewer)
                ->withPHIDs(array($from_phid))
                ->needUserSettings(true)
                ->execute();

            /** @var PhabricatorUser $from_user */
            $from_user = head($from_user);
            if ($from_user) {
                $pref_key = PhabricatorEmailSelfActionsSetting::SETTINGKEY;
                $exclude_self = $from_user->getUserSetting($pref_key);
                if ($exclude_self) {
                    $from_actor->setUndeliverable(PhabricatorMetaMTAActor::REASON_SELF);
                }
            }
        }

        /** @var PhabricatorUserPreferences[] $all_prefs */
        $all_prefs = PhabricatorUserPreferences::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withUserPHIDs($actor_phids)
            ->needSyntheticPreferences(true)
            ->execute();
        $all_prefs = mpull($all_prefs, null, 'getUserPHID');

        $value_email = PhabricatorEmailTagsSetting::VALUE_EMAIL;

        // Exclude all recipients who have set preferences to not receive this type
        // of email (for example, a user who says they don't want emails about task
        // CC changes).
        $tags = $this->getParam('mailtags');
        if ($tags) {
            foreach ($all_prefs as $phid => $prefs) {
                $user_mailtags = $prefs->getSettingValue(
                    PhabricatorEmailTagsSetting::SETTINGKEY);

                // The user must have elected to receive mail for at least one
                // of the mailtags.
                $send = false;
                foreach ($tags as $tag) {
                    if (((int)ArrayHelper::getValue($user_mailtags, $tag, $value_email)) == $value_email) {
                        $send = true;
                        break;
                    }
                }

                if (!$send) {
                    /** @var PhabricatorMetaMTAActor $actor */
                    $actor = $actors[$phid];
                    $actor->setUndeliverable(PhabricatorMetaMTAActor::REASON_MAILTAGS);
                }
            }
        }

        foreach ($deliverable as $phid) {
            /** @var PhabricatorMetaMTAActor $actor */
            $actor = $actors[$phid];
            switch ($this->getRoutingRule($phid)) {
                case PhabricatorMailRoutingRule::ROUTE_AS_NOTIFICATION:
                    $actor->setUndeliverable(
                        PhabricatorMetaMTAActor::REASON_ROUTE_AS_NOTIFICATION);
                    break;
                case PhabricatorMailRoutingRule::ROUTE_AS_MAIL:
                    $actor->setDeliverable(
                        PhabricatorMetaMTAActor::REASON_ROUTE_AS_MAIL);
                    break;
                default:
                    // No change.
                    break;
            }
        }

        // If recipients were initially deliverable and were added by "Send me an
        // email" Herald rules, annotate them as such and make them deliverable
        // again, overriding any changes made by the "self mail" and "mail tags"
        // settings.
        $force_recipients = $this->getForceHeraldMailRecipientPHIDs();
        $force_recipients = array_fuse($force_recipients);
        if ($force_recipients) {
            foreach ($deliverable as $phid) {
                if (isset($force_recipients[$phid])) {
                    /** @var PhabricatorMetaMTAActor $actor */
                    $actor = $actors[$phid];
                    $actor->setDeliverable(
                        PhabricatorMetaMTAActor::REASON_FORCE_HERALD);
                }
            }
        }

        // Exclude recipients who don't want any mail. This rule is very strong
        // and runs last.
        foreach ($all_prefs as $phid => $prefs) {
            /** @var PhabricatorMetaMTAActor $actor */
            $actor = $actors[$phid];
            $exclude = $prefs->getSettingValue(
                PhabricatorEmailNotificationsSetting::SETTINGKEY);
            if ($exclude) {
                $actor->setUndeliverable(PhabricatorMetaMTAActor::REASON_MAIL_DISABLED);
            }
        }

        // Unless delivery was forced earlier (password resets, confirmation mail),
        // never send mail to unverified addresses.
        foreach ($actors as $phid => $actor) {
            if ($actor->getIsVerified()) {
                continue;
            }

            $actor->setUndeliverable(PhabricatorMetaMTAActor::REASON_UNVERIFIED);
        }

        return $actors;
    }

    /**
     * @param array $all_recipients
     * @return bool
     * @author 陈妙威
     */
    private function shouldRateLimitMail(array $all_recipients)
    {
        try {
            PhabricatorSystemActionEngine::willTakeAction(
                $all_recipients,
                new PhabricatorMetaMTAErrorMailAction(),
                1);
            return false;
        } catch (PhabricatorSystemActionRateLimitException $ex) {
            return true;
        }
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function generateHeaders()
    {
        $headers = array();

        $headers[] = array('X-Phabricator-Sent-This-Message', 'Yes');
        $headers[] = array('X-Mail-Transport-Agent', 'MetaMTA');

        // Some clients respect this to suppress OOF and other auto-responses.
        $headers[] = array('X-Auto-Response-Suppress', 'All');

        $mailtags = $this->getParam('mailtags');
        if ($mailtags) {
            $tag_header = array();
            foreach ($mailtags as $mailtag) {
                $tag_header[] = '<' . $mailtag . '>';
            }
            $tag_header = implode(', ', $tag_header);
            $headers[] = array('X-Phabricator-Mail-Tags', $tag_header);
        }

        $value = $this->getParam('headers', array());
        foreach ($value as $pair) {
            list($header_key, $header_value) = $pair;

            // NOTE: If we have \n in a header, SES rejects the email.
            $header_value = str_replace("\n", ' ', $header_value);
            $headers[] = array($header_key, $header_value);
        }

        $is_bulk = $this->getParam('is-bulk');
        if ($is_bulk) {
            $headers[] = array('Precedence', 'bulk');
        }

        if ($this->getMustEncrypt()) {
            $headers[] = array('X-Phabricator-Must-Encrypt', 'Yes');
        }

        $related_phid = $this->getRelatedPHID();
        if ($related_phid) {
            $headers[] = array('Thread-Topic', $related_phid);
        }

        $headers[] = array('X-Phabricator-Mail-ID', $this->getID());

        $unique = Filesystem::readRandomCharacters(16);
        $headers[] = array('X-Phabricator-Send-Attempt', $unique);

        return $headers;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getDeliveredHeaders()
    {
        return $this->getParam('headers.sent');
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getUnfilteredHeaders()
    {
        $unfiltered = $this->getParam('headers.unfiltered');

        if ($unfiltered === null) {
            // Older versions of Phabricator did not filter headers, and thus did
            // not record unfiltered headers. If we don't have unfiltered header
            // data just return the delivered headers for compatibility.
            return $this->getDeliveredHeaders();
        }

        return $unfiltered;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getDeliveredActors()
    {
        return $this->getParam('actors.sent');
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getDeliveredRoutingRules()
    {
        return $this->getParam('routing.sent');
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getDeliveredRoutingMap()
    {
        return $this->getParam('routingmap.sent');
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getDeliveredBody()
    {
        return $this->getParam('body.sent');
    }

    /**
     * @param array $headers
     * @param $must_encrypt
     * @return array
     * @author 陈妙威
     */
    private function filterHeaders(array $headers, $must_encrypt)
    {
        if (!$must_encrypt) {
            return $headers;
        }

        $whitelist = array(
            'In-Reply-To',
            'Message-ID',
            'Precedence',
            'References',
            'Thread-Index',
            'Thread-Topic',

            'X-Mail-Transport-Agent',
            'X-Auto-Response-Suppress',

            'X-Phabricator-Sent-This-Message',
            'X-Phabricator-Must-Encrypt',
            'X-Phabricator-Mail-ID',
            'X-Phabricator-Send-Attempt',
        );

        // NOTE: The major header we want to drop is "X-Phabricator-Mail-Tags".
        // This header contains a significant amount of meaningful information
        // about the object.

        $whitelist_map = array();
        foreach ($whitelist as $term) {
            $whitelist_map[phutil_utf8_strtolower($term)] = true;
        }

        foreach ($headers as $key => $header) {
            list($name, $value) = $header;
            $name = phutil_utf8_strtolower($name);

            if (!isset($whitelist_map[$name])) {
                unset($headers[$key]);
            }
        }

        return $headers;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getURI()
    {
        return '/mail/detail/' . $this->getID() . '/';
    }


    /* -(  Routing  )------------------------------------------------------------ */


    /**
     * @param $routing_rule
     * @param $phids
     * @param $reason_phid
     * @return $this
     * @author 陈妙威
     * @throws \Exception
     */
    public function addRoutingRule($routing_rule, $phids, $reason_phid)
    {
        $routing = $this->getParam('routing', array());
        $routing[] = array(
            'routingRule' => $routing_rule,
            'phids' => $phids,
            'reasonPHID' => $reason_phid,
        );
        $this->setParam('routing', $routing);

        // Throw the routing map away so we rebuild it.
        $this->routingMap = null;

        return $this;
    }

    /**
     * @param $phid
     * @return null|object
     * @author 陈妙威
     */
    private function getRoutingRule($phid)
    {
        $map = $this->getRoutingRuleMap();

        $info = ArrayHelper::getValue($map, $phid, ArrayHelper::getValue($map, 'default'));
        if ($info) {
            return ArrayHelper::getValue($info, 'rule');
        }

        return null;
    }

    /**
     * @return array|null
     * @author 陈妙威
     */
    private function getRoutingRuleMap()
    {
        if ($this->routingMap === null) {
            $map = array();

            $routing = $this->getParam('routing', array());
            foreach ($routing as $route) {
                $phids = $route['phids'];
                if ($phids === null) {
                    $phids = array('default');
                }

                foreach ($phids as $phid) {
                    $new_rule = $route['routingRule'];

                    $current_rule = ArrayHelper::getValue($map, $phid);
                    if ($current_rule === null) {
                        $is_stronger = true;
                    } else {
                        $is_stronger = PhabricatorMailRoutingRule::isStrongerThan(
                            $new_rule,
                            $current_rule);
                    }

                    if ($is_stronger) {
                        $map[$phid] = array(
                            'rule' => $new_rule,
                            'reason' => $route['reasonPHID'],
                        );
                    }
                }
            }

            $this->routingMap = $map;
        }

        return $this->routingMap;
    }

    /* -(  Preferences  )-------------------------------------------------------- */


    /**
     * @param $target_phid
     * @return PhabricatorUserPreferences
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    private function loadPreferences($target_phid)
    {
        $viewer = PhabricatorUser::getOmnipotentUser();

        if (self::shouldMailEachRecipient()) {
            $preferences = PhabricatorUserPreferences::find()
                ->setViewer($viewer)
                ->withUserPHIDs(array($target_phid))
                ->needSyntheticPreferences(true)
                ->executeOne();
            if ($preferences) {
                return $preferences;
            }
        }

        return PhabricatorUserPreferences::loadGlobalPreferences($viewer);
    }

    /**
     * @param PhabricatorUserPreferences $preferences
     * @return bool
     * @throws Exception
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException

     * @author 陈妙威
     */
    private function shouldAddRePrefix(PhabricatorUserPreferences $preferences)
    {
        $value = $preferences->getSettingValue(
            PhabricatorEmailRePrefixSetting::SETTINGKEY);

        return ($value == PhabricatorEmailRePrefixSetting::VALUE_RE_PREFIX);
    }

    /**
     * @param PhabricatorUserPreferences $preferences
     * @return bool
     * @throws Exception
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException

     * @author 陈妙威
     */
    private function shouldVarySubject(PhabricatorUserPreferences $preferences)
    {
        $value = $preferences->getSettingValue(
            PhabricatorEmailVarySubjectsSetting::SETTINGKEY);

        return ($value == PhabricatorEmailVarySubjectsSetting::VALUE_VARY_SUBJECTS);
    }

    /**
     * @param PhabricatorUserPreferences $preferences
     * @return bool
     * @throws Exception
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException

     * @author 陈妙威
     */
    private function shouldSendHTML(PhabricatorUserPreferences $preferences)
    {
        $value = $preferences->getSettingValue(
            PhabricatorEmailFormatSetting::SETTINGKEY);

        return ($value == PhabricatorEmailFormatSetting::VALUE_HTML_EMAIL);
    }

    /**
     * @param $viewer
     * @return bool
     * @throws Exception
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException

     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function shouldRenderMailStampsInBody($viewer)
    {
        $preferences = $this->loadPreferences($viewer->getPHID());
        $value = $preferences->getSettingValue(
            PhabricatorEmailStampsSetting::SETTINGKEY);

        return ($value == PhabricatorEmailStampsSetting::VALUE_BODY_STAMPS);
    }


    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */


    /**
     * @return array
     * @author 陈妙威
     */
    public function getCapabilities()
    {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
        );
    }

    /**
     * @param $capability
     * @return string
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        return PhabricatorPolicies::POLICY_NOONE;
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        $actor_phids = $this->getExpandedRecipientPHIDs();
        return in_array($viewer->getPHID(), $actor_phids);
    }

    /**
     * @param $capability
     * @return string
     * @author 陈妙威
     */
    public function describeAutomaticCapability($capability)
    {
        return \Yii::t("app",
            'The mail sender and message recipients can always see the mail.');
    }


    /* -(  PhabricatorDestructibleInterface  )----------------------------------- */


    /**
     * @param PhabricatorDestructionEngine $engine
     * @throws Throwable
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function destroyObjectPermanently(
        PhabricatorDestructionEngine $engine)
    {

        $files = $this->loadAttachedFiles($engine->getViewer());
        foreach ($files as $file) {
            $engine->destroyObject($file);
        }

        $this->delete();
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorMetaMTAMailPHIDType::className();
    }


    /**
     * @return \orangins\lib\infrastructure\query\PhabricatorQuery|PhabricatorMetaMTAMailQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorMetaMTAMailQuery(get_called_class());
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
     * @return string
     */
    public function getRelatedPHID()
    {
        return $this->related_phid;
    }

    /**
     * @param string $related_phid
     * @return self
     */
    public function setRelatedPHID($related_phid)
    {
        $this->related_phid = $related_phid;
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
     * @author 陈妙威
     */
    public function edgeBaseTableName()
    {
        return 'metamta';
    }
}
