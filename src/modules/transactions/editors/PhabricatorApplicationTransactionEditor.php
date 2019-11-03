<?php

namespace orangins\modules\transactions\editors;

use AphrontDuplicateKeyQueryException;
use AphrontObjectMissingQueryException;
use AphrontQueryException;
use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\exception\ActiveRecordException;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\lib\infrastructure\edges\editor\PhabricatorEdgeEditor;
use orangins\lib\infrastructure\edges\query\PhabricatorEdgeQuery;
use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;
use orangins\lib\editor\PhabricatorEditor;
use orangins\lib\markup\PhabricatorMarkupEngine;
use orangins\lib\markup\rule\PhabricatorObjectRemarkupRule;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\infrastructure\customfield\interfaces\PhabricatorCustomFieldInterface;
use orangins\lib\infrastructure\edges\util\PhabricatorEdgeChangeRecord;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\request\AphrontRequest;
use orangins\modules\feed\PhabricatorFeedStoryPublisher;
use orangins\modules\file\edge\PhabricatorObjectHasFileEdgeType;
use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\application\PhabricatorHeraldApplication;
use orangins\modules\herald\contentsource\PhabricatorHeraldContentSource;
use orangins\modules\herald\engine\HeraldEngine;
use orangins\modules\herald\models\HeraldTranscript;
use orangins\modules\herald\models\HeraldWebhook;
use orangins\modules\herald\models\HeraldWebhookRequest;
use orangins\modules\herald\query\HeraldWebhookQuery;
use orangins\modules\herald\state\HeraldCoreStateReasons;
use orangins\modules\herald\state\HeraldMailableState;
use orangins\modules\metamta\engine\PhabricatorMailEngineExtension;
use orangins\modules\metamta\herald\PhabricatorMailOutboundMailHeraldAdapter;
use orangins\modules\metamta\replyhandler\PhabricatorMailTarget;
use orangins\modules\metamta\view\PhabricatorMetaMTAMailBody;
use orangins\modules\search\worker\PhabricatorSearchWorker;
use orangins\modules\transactions\edges\PhabricatorObjectHasUnsubscriberEdgeType;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionNoEffectException;
use orangins\modules\transactions\models\PhabricatorModularTransactionType;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;
use orangins\modules\spaces\interfaces\PhabricatorSpacesInterface;
use orangins\modules\spaces\query\PhabricatorSpacesNamespaceQuery;
use orangins\modules\transactions\edges\PhabricatorObjectMentionsObjectEdgeType;
use orangins\modules\transactions\error\PhabricatorApplicationTransactionValidationError;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\phid\PhabricatorPeopleUserPHIDType;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorPHIDConstants;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\exception\PhabricatorPolicyException;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\policy\rule\PhabricatorPolicyRule;
use orangins\modules\transactions\edges\PhabricatorMutedByEdgeType;
use orangins\modules\subscriptions\editor\PhabricatorSubscriptionsEditor;
use orangins\modules\subscriptions\engineextension\PhabricatorSubscriptionsEditEngineExtension;
use orangins\modules\subscriptions\interfaces\PhabricatorSubscribableInterface;
use orangins\modules\subscriptions\policyrule\PhabricatorSubscriptionsSubscribersPolicyRule;
use orangins\modules\subscriptions\query\PhabricatorSubscribersQuery;
use orangins\modules\system\engine\PhabricatorCacheEngine;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editengine\PhabricatorEditEngineSubtypeInterface;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\models\PhabricatorModularTransaction;
use PhutilInvalidStateException;
use PhutilJSONParserException;
use PhutilMarkupEngine;
use PhutilMethodNotImplementedException;
use Exception;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\db\IntegrityException;
use yii\helpers\ArrayHelper;

/**
 *
 * Publishing and Managing State
 * ======
 *
 * After applying changes, the Editor queues a worker to publish mail, feed,
 * and notifications, and to perform other background work like updating search
 * indexes. This allows it to do this work without impacting performance for
 * users.
 *
 * When work is moved to the daemons, the Editor state is serialized by
 * @{method:getWorkerState}, then reloaded in a daemon process by
 * @{method:loadWorkerState}. **This is fragile.**
 *
 * State is not persisted into the daemons by default, because we can not send
 * arbitrary objects into the queue. This means the default behavior of any
 * state properties is to reset to their defaults without warning prior to
 * publishing.
 *
 * The easiest way to avoid this is to keep Editors stateless: the overwhelming
 * majority of Editors can be written statelessly. If you need to maintain
 * state, you can either:
 *
 *   - not require state to exist during publishing; or
 *   - pass state to the daemons by implementing @{method:getCustomWorkerState}
 *     and @{method:loadCustomWorkerState}.
 *
 * This architecture isn't ideal, and we may eventually split this class into
 * "Editor" and "Publisher" parts to make it more robust. See T6367 for some
 * discussion and context.
 *
 * @property bool editEngine
 * @task mail Sending Mail
 * @task feed Publishing Feed Stories
 * @task search Search Index
 * @task files Integration with Files
 * @task workers Managing Workers
 */
abstract class PhabricatorApplicationTransactionEditor extends PhabricatorEditor
{
    /**
     * @var array
     */
    public $attributeLabels = [];

    /**
     * @var
     */
    private $contentSource;
    /**
     * @var PhabricatorApplicationTransactionInterface|ActiveRecord
     */
    private $object;
    /**
     * @var
     */
    private $xactions;

    /**
     * @var
     */
    private $isNewObject;
    /**
     * @var
     */
    private $mentionedPHIDs;
    /**
     * @var
     */
    private $continueOnNoEffect;
    /**
     * @var
     */
    private $continueOnMissingFields;
    /**
     * @var
     */
    private $raiseWarnings;
    /**
     * @var
     */
    private $parentMessageID;
    /**
     * @var
     */
    private $heraldAdapter;
    /**
     * @var
     */
    private $heraldTranscript;
    /**
     * @var array
     */
    private $unmentionablePHIDMap = array();
    /**
     * @var
     */
    private $applicationEmail;

    /**
     * @var
     */
    private $isPreview;
    /**
     * @var
     */
    private $isHeraldEditor;
    /**
     * @var
     */
    private $isInverseEdgeEditor;
    /**
     * @var
     */
    private $actingAsPHID;

    /**
     * @var array
     */
    private $heraldEmailPHIDs = array();
    /**
     * @var array
     */
    private $heraldForcedEmailPHIDs = array();
    /**
     * @var
     */
    private $heraldHeader;
    /**
     * @var array
     */
    private $mailToPHIDs = array();
    /**
     * @var array
     */
    private $mailCCPHIDs = array();
    /**
     * @var array
     */
    private $feedNotifyPHIDs = array();
    /**
     * @var array
     */
    private $feedRelatedPHIDs = array();
    /**
     * @var bool
     */
    private $feedShouldPublish = false;
    /**
     * @var bool
     */
    private $mailShouldSend = false;
    /**
     * @var
     */
    private $modularTypes;
    /**
     * @var
     */
    private $silent;
    /**
     * @var
     */
    private $mustEncrypt;
    /**
     * @var array
     */
    private $stampTemplates = array();
    /**
     * @var array
     */
    private $mailStamps = array();
    /**
     * @var array
     */
    private $oldTo = array();
    /**
     * @var array
     */
    private $oldCC = array();
    /**
     * @var array
     */
    private $mailRemovedPHIDs = array();
    /**
     * @var array
     */
    private $mailUnexpandablePHIDs = array();
    /**
     * @var array
     */
    private $mailMutedPHIDs = array();
    /**
     * @var array
     */
    private $webhookMap = array();

    /**
     * @var array
     */
    private $transactionQueue = array();
    /**
     * @var bool
     */
    private $sendHistory = false;

    /**
     *
     */
    const STORAGE_ENCODING_BINARY = 'binary';

    /**
     * Get the class name for the application this editor is a part of.
     *
     * Uninstalling the application will disable the editor.
     *
     * @return string Editor's application class name.
     */
    abstract public function getEditorApplicationClass();


    /**
     * Get a description of the objects this editor edits, like "Differential
     * Revisions".
     *
     * @return string Human readable description of edited objects.
     */
    abstract public function getEditorObjectsDescription();


    /**
     * @return PhabricatorApplicationTransactionInterface
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param $acting_as_phid
     * @return $this
     * @author 陈妙威
     */
    public function setActingAsPHID($acting_as_phid)
    {
        $this->actingAsPHID = $acting_as_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getActingAsPHID()
    {
        if ($this->actingAsPHID) {
            return $this->actingAsPHID;
        }
        return $this->getActor()->getPHID();
    }


    /**
     * When the editor tries to apply transactions that have no effect, should
     * it raise an exception (default) or drop them and continue?
     *
     * Generally, you will set this flag for edits coming from "Edit" interfaces,
     * and leave it cleared for edits coming from "Comment" interfaces, so the
     * user will get a useful error if they try to submit a comment that does
     * nothing (e.g., empty comment with a status change that has already been
     * performed by another user).
     *
     * @param bool  True to drop transactions without effect and continue.
     * @return static
     */
    public function setContinueOnNoEffect($continue)
    {
        $this->continueOnNoEffect = $continue;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getContinueOnNoEffect()
    {
        return $this->continueOnNoEffect;
    }


    /**
     * When the editor tries to apply transactions which don't populate all of
     * an object's required fields, should it raise an exception (default) or
     * drop them and continue?
     *
     * For example, if a user adds a new required custom field (like "Severity")
     * to a task, all existing tasks won't have it populated. When users
     * manually edit existing tasks, it's usually desirable to have them provide
     * a severity. However, other operations (like batch editing just the
     * owner of a task) will fail by default.
     *
     * By setting this flag for edit operations which apply to specific fields
     * (like the priority, batch, and merge editors in Maniphest), these
     * operations can continue to function even if an object is outdated.
     *
     * @param bool  True to continue when transactions don't completely satisfy
     *              all required fields.
     * @return static
     */
    public function setContinueOnMissingFields($continue_on_missing_fields)
    {
        $this->continueOnMissingFields = $continue_on_missing_fields;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getContinueOnMissingFields()
    {
        return $this->continueOnMissingFields;
    }


    /**
     * Not strictly necessary, but reply handlers ideally set this value to
     * make email threading work better.
     * @param $parent_message_id
     * @return PhabricatorApplicationTransactionEditor
     */
    public function setParentMessageID($parent_message_id)
    {
        $this->parentMessageID = $parent_message_id;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getParentMessageID()
    {
        return $this->parentMessageID;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsNewObject()
    {
        return $this->isNewObject;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMentionedPHIDs()
    {
        return $this->mentionedPHIDs;
    }

    /**
     * @param $is_preview
     * @return $this
     * @author 陈妙威
     */
    public function setIsPreview($is_preview)
    {
        $this->isPreview = $is_preview;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsPreview()
    {
        return $this->isPreview;
    }

    /**
     * @param $silent
     * @return $this
     * @author 陈妙威
     */
    public function setIsSilent($silent)
    {
        $this->silent = $silent;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsSilent()
    {
        return $this->silent;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMustEncrypt()
    {
        return $this->mustEncrypt;
    }

    /**
     * @return array[]|false|string[]
     * @author 陈妙威
     */
    public function getHeraldRuleMonograms()
    {
        // Convert the stored "<123>, <456>" string into a list: "H123", "H456".
        $list = $this->heraldHeader;
        $list = preg_split('/[, ]+/', $list);

        foreach ($list as $key => $item) {
            $item = trim($item, '<>');

            if (!is_numeric($item)) {
                unset($list[$key]);
                continue;
            }

            $list[$key] = 'H' . $item;
        }

        return $list;
    }

    /**
     * @param $is_inverse_edge_editor
     * @return $this
     * @author 陈妙威
     */
    public function setIsInverseEdgeEditor($is_inverse_edge_editor)
    {
        $this->isInverseEdgeEditor = $is_inverse_edge_editor;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsInverseEdgeEditor()
    {
        return $this->isInverseEdgeEditor;
    }

    /**
     * @param $is_herald_editor
     * @return $this
     * @author 陈妙威
     */
    public function setIsHeraldEditor($is_herald_editor)
    {
        $this->isHeraldEditor = $is_herald_editor;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsHeraldEditor()
    {
        return $this->isHeraldEditor;
    }

    /**
     * @param array $map
     * @return $this
     * @author 陈妙威
     */
    public function setUnmentionablePHIDMap(array $map)
    {
        $this->unmentionablePHIDMap = $map;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getUnmentionablePHIDMap()
    {
        return $this->unmentionablePHIDMap;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return bool
     * @author 陈妙威
     */
    protected function shouldEnableMentions(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return true;
    }

    /**
     * @param PhabricatorMetaMTAMail $email
     * @return $this
     * @author 陈妙威
     */
    public function setApplicationEmail(
        PhabricatorMetaMTAMail $email)
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
     * @param $raise_warnings
     * @return $this
     * @author 陈妙威
     */
    public function setRaiseWarnings($raise_warnings)
    {
        $this->raiseWarnings = $raise_warnings;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRaiseWarnings()
    {
        return $this->raiseWarnings;
    }

    /**
     * @param $object
     * @return array
     * @throws Exception
     * @throws ReflectionException
     * @author 陈妙威
     */
    public function getTransactionTypesForObject($object)
    {
        $old = $this->object;
        try {
            $this->object = $object;
            $result = $this->getTransactionTypes();
            $this->object = $old;
        } catch (Exception $ex) {
            $this->object = $old;
            throw $ex;
        }
        return $result;
    }

    /**
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    public function getTransactionTypes()
    {
        $types = array();

        $types[] = PhabricatorTransactions::TYPE_CREATE;
        $types[] = PhabricatorTransactions::TYPE_HISTORY;

        if ($this->object instanceof PhabricatorEditEngineSubtypeInterface) {
            $types[] = PhabricatorTransactions::TYPE_SUBTYPE;
        }

        if ($this->object instanceof PhabricatorSubscribableInterface) {
            $types[] = PhabricatorTransactions::TYPE_SUBSCRIBERS;
        }

        if ($this->object instanceof PhabricatorCustomFieldInterface) {
            $types[] = PhabricatorTransactions::TYPE_CUSTOMFIELD;
        }

//        if ($this->object instanceof PhabricatorTokenReceiverInterface) {
//            $types[] = PhabricatorTransactions::TYPE_TOKEN;
//        }

        if ($this->object instanceof PhabricatorSpacesInterface) {
            $types[] = PhabricatorTransactions::TYPE_SPACE;
        }

        $template = $this->object->getApplicationTransactionTemplate();
        if ($template instanceof PhabricatorModularTransaction) {
            $xtypes = $template->newModularTransactionTypes();
            foreach ($xtypes as $xtype) {
                $types[] = $xtype->getTransactionTypeConstant();
            }
        }

        if ($template) {
            try {
                $comment = $template->getApplicationTransactionCommentObject();
            } catch (PhutilMethodNotImplementedException $ex) {
                $comment = null;
            }

            if ($comment) {
                $types[] = PhabricatorTransactions::TYPE_COMMENT;
            }
        }

        return $types;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws Exception
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilJSONParserException
     * @throws ReflectionException
     * @author 陈妙威
     */
    private function adjustTransactionValues(ActiveRecordPHID $object, PhabricatorApplicationTransaction $xaction)
    {

        if ($xaction->shouldGenerateOldValue()) {
            $old = $this->getTransactionOldValue($object, $xaction);
            $xaction->setOldValue($old);
        }

        $new = $this->getTransactionNewValue($object, $xaction);
        $xaction->setNewValue($new);
    }

    /**
     * @param PhabricatorEditEngineSubtypeInterface|ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return array|null
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilJSONParserException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function getTransactionOldValue(
        $object,
        PhabricatorApplicationTransaction $xaction)
    {

        $type = $xaction->getTransactionType();

        $xtype = $this->getModularTransactionType($type);
        if ($xtype) {
            $xtype = clone $xtype;
            $xtype->setStorage($xaction);
            return $xtype->generateOldValue($object);
        }

        switch ($type) {
            case PhabricatorTransactions::TYPE_CREATE:
            case PhabricatorTransactions::TYPE_HISTORY:
                return null;
            case PhabricatorTransactions::TYPE_SUBTYPE:
                return $object->getEditEngineSubtype();
            case PhabricatorTransactions::TYPE_SUBSCRIBERS:
                return array_values($this->getAttribute(PhabricatorSubscriptionsEditEngineExtension::FIELDKEY));
            case PhabricatorTransactions::TYPE_VIEW_POLICY:
                if ($this->getIsNewObject()) {
                    return null;
                }
                return $object->getAttribute('view_policy');
            case PhabricatorTransactions::TYPE_EDIT_POLICY:
                if ($this->getIsNewObject()) {
                    return null;
                }
                return $object->getAttribute('edit_policy');
            case PhabricatorTransactions::TYPE_JOIN_POLICY:
                if ($this->getIsNewObject()) {
                    return null;
                }
                return $object->getAttribute('join_policy');
            case PhabricatorTransactions::TYPE_SPACE:
                if ($this->getIsNewObject()) {
                    return null;
                }
                /** @var PhabricatorSpacesInterface $object */
                $space_phid = $object->getSpacePHID();
                if ($space_phid === null) {
                    /** @var PhabricatorSpacesInterface $default_space */
                    $default_space = PhabricatorSpacesNamespaceQuery::getDefaultSpace();
                    if ($default_space) {
                        $space_phid = $default_space->getPHID();
                    }
                }

                return $space_phid;
            case PhabricatorTransactions::TYPE_EDGE:
                $edge_type = $xaction->getMetadataValue('edge:type');
                if (!$edge_type) {
                    throw new Exception(
                        Yii::t("app",
                            "Edge transaction has no '{0}'!",
                            [
                                'edge:type'
                            ]));
                }

                $old_edges = array();
                if ($object->getPHID()) {
                    $edge_src = $object->getPHID();

                    $old_edges = (new PhabricatorEdgeQuery())
                        ->withSourcePHIDs(array($edge_src))
                        ->withEdgeTypes(array($edge_type))
                        ->needEdgeData(true)
                        ->execute();

                    $old_edges = $old_edges[$edge_src][$edge_type];
                }
                return $old_edges;
            case PhabricatorTransactions::TYPE_CUSTOMFIELD:
                // NOTE: Custom fields have their old value pre-populated when they are
                // built by PhabricatorCustomFieldList.
                return $xaction->getOldValue();
            case PhabricatorTransactions::TYPE_COMMENT:
                return null;
            default:
                return $this->getCustomTransactionOldValue($object, $xaction);
        }
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return mixed
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    private function getTransactionNewValue(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        $type = $xaction->getTransactionType();

        $xtype = $this->getModularTransactionType($type);
        if ($xtype) {
            $xtype = clone $xtype;
            $xtype->setStorage($xaction);
            return $xtype->generateNewValue($object, $xaction->getNewValue());
        }

        switch ($type) {
            case PhabricatorTransactions::TYPE_CREATE:
                return null;
            case PhabricatorTransactions::TYPE_SUBSCRIBERS:
                return $this->getPHIDTransactionNewValue($xaction);
            case PhabricatorTransactions::TYPE_VIEW_POLICY:
            case PhabricatorTransactions::TYPE_EDIT_POLICY:
            case PhabricatorTransactions::TYPE_JOIN_POLICY:
            case PhabricatorTransactions::TYPE_TOKEN:
            case PhabricatorTransactions::TYPE_INLINESTATE:
            case PhabricatorTransactions::TYPE_SUBTYPE:
            case PhabricatorTransactions::TYPE_HISTORY:
                return $xaction->getNewValue();
            case PhabricatorTransactions::TYPE_SPACE:
                $space_phid = $xaction->getNewValue();
                if (!strlen($space_phid)) {
                    // If an install has no Spaces or the Spaces controls are not visible
                    // to the viewer, we might end up with the empty string here instead
                    // of a strict `null`, because some controller just used `getStr()`
                    // to read the space PHID from the request.
                    // Just make this work like callers might reasonably expect so we
                    // don't need to handle this specially in every EditController.
                    return $this->getActor()->getDefaultSpacePHID();
                } else {
                    return $space_phid;
                }
            case PhabricatorTransactions::TYPE_EDGE:
                $new_value = $this->getEdgeTransactionNewValue($xaction);
                $edge_type = $xaction->getMetadataValue('edge:type');
//                $type_project = PhabricatorProjectObjectHasProjectEdgeType::EDGECONST;
//                if ($edge_type == $type_project) {
//                    $new_value = $this->applyProjectConflictRules($new_value);
//                }
                return $new_value;
            case PhabricatorTransactions::TYPE_CUSTOMFIELD:
                $field = $this->getCustomFieldForTransaction($object, $xaction);
                return $field->getNewValueFromApplicationTransactions($xaction);
            case PhabricatorTransactions::TYPE_COMMENT:
                return null;
            default:
                return $this->getCustomTransactionNewValue($object, $xaction);
        }
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws Exception
     * @author 陈妙威
     */
    protected function getCustomTransactionOldValue(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {
        throw new Exception(Yii::t("app", 'Capability not supported!'));
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws Exception
     * @author 陈妙威
     */
    protected function getCustomTransactionNewValue(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {
        throw new Exception(Yii::t("app", 'Capability not supported!'));
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return bool
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    protected function transactionHasEffect(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorTransactions::TYPE_CREATE:
            case PhabricatorTransactions::TYPE_HISTORY:
                return true;
            case PhabricatorTransactions::TYPE_CUSTOMFIELD:
                $field = $this->getCustomFieldForTransaction($object, $xaction);
                return $field->getApplicationTransactionHasEffect($xaction);
            case PhabricatorTransactions::TYPE_EDGE:
                // A straight value comparison here doesn't always get the right
                // result, because newly added edges aren't fully populated. Instead,
                // compare the changes in a more granular way.
                $old = $xaction->getOldValue();
                $new = $xaction->getNewValue();

                $old_dst = array_keys($old);
                $new_dst = array_keys($new);

                // NOTE: For now, we don't consider edge reordering to be a change.
                // We have very few order-dependent edges and effectively no order
                // oriented UI. This might change in the future.
                sort($old_dst);
                sort($new_dst);

                if ($old_dst !== $new_dst) {
                    // We've added or removed edges, so this transaction definitely
                    // has an effect.
                    return true;
                }

                // We haven't added or removed edges, but we might have changed
                // edge data.
                foreach ($old as $key => $old_value) {
                    $new_value = $new[$key];
                    if ($old_value['data'] !== $new_value['data']) {
                        return true;
                    }
                }

                return false;
        }

        $type = $xaction->getTransactionType();
        $xtype = $this->getModularTransactionType($type);
        if ($xtype) {
            return $xtype->getTransactionHasEffect(
                $object,
                $xaction->getOldValue(),
                $xaction->getNewValue());
        }

        if ($xaction->hasComment()) {
            return true;
        }

        return ($xaction->getOldValue() !== $xaction->getNewValue());
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return bool
     * @author 陈妙威
     */
    protected function shouldApplyInitialEffects(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return false;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function applyInitialEffects(
        ActiveRecordPHID $object,
        array $xactions)
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    private function applyInternalEffects(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        $type = $xaction->getTransactionType();

        $xtype = $this->getModularTransactionType($type);
        if ($xtype) {
            $xtype = clone $xtype;
            $xtype->setStorage($xaction);
            return $xtype->applyInternalEffects($object, $xaction->getNewValue());
        }

        switch ($type) {
            case PhabricatorTransactions::TYPE_CUSTOMFIELD:
                $field = $this->getCustomFieldForTransaction($object, $xaction);
                return $field->applyApplicationTransactionInternalEffects($xaction);
            case PhabricatorTransactions::TYPE_CREATE:
            case PhabricatorTransactions::TYPE_HISTORY:
            case PhabricatorTransactions::TYPE_SUBTYPE:
            case PhabricatorTransactions::TYPE_TOKEN:
            case PhabricatorTransactions::TYPE_VIEW_POLICY:
            case PhabricatorTransactions::TYPE_EDIT_POLICY:
            case PhabricatorTransactions::TYPE_JOIN_POLICY:
            case PhabricatorTransactions::TYPE_SUBSCRIBERS:
            case PhabricatorTransactions::TYPE_INLINESTATE:
            case PhabricatorTransactions::TYPE_EDGE:
            case PhabricatorTransactions::TYPE_SPACE:
            case PhabricatorTransactions::TYPE_COMMENT:
                return $this->applyBuiltinInternalTransaction($object, $xaction);
        }

        return $this->applyCustomInternalTransaction($object, $xaction);
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws Exception
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionValidationException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws InvalidConfigException
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    private function applyExternalEffects(ActiveRecordPHID $object, PhabricatorApplicationTransaction $xaction)
    {

        $type = $xaction->getTransactionType();
        $xtype = $this->getModularTransactionType($type);
        if ($xtype) {
            $xtype = clone $xtype;
            $xtype->setStorage($xaction);
            return $xtype->applyExternalEffects($object, $xaction->getNewValue());
        }

        switch ($type) {
            case PhabricatorTransactions::TYPE_SUBSCRIBERS:
                $subeditor = (new PhabricatorSubscriptionsEditor())
                    ->setObject($object)
                    ->setActor($this->requireActor());

                $old_map = array_fuse($xaction->getOldValue());
                $new_map = array_fuse($xaction->getNewValue());

                $subeditor->unsubscribe(
                    array_keys(
                        array_diff_key($old_map, $new_map)));

                $subeditor->subscribeExplicit(
                    array_keys(
                        array_diff_key($new_map, $old_map)));

                $subeditor->save();

                // for the rest of these edits, subscribers should include those just
                // added as well as those just removed.
                $subscribers = array_unique(array_merge(
                    $this->subscribers,
                    $xaction->getOldValue(),
                    $xaction->getNewValue()));
                $this->subscribers = $subscribers;
                return $this->applyBuiltinExternalTransaction($object, $xaction);

            case PhabricatorTransactions::TYPE_CUSTOMFIELD:
                $field = $this->getCustomFieldForTransaction($object, $xaction);
                return $field->applyApplicationTransactionExternalEffects($xaction);
            case PhabricatorTransactions::TYPE_CREATE:
            case PhabricatorTransactions::TYPE_HISTORY:
            case PhabricatorTransactions::TYPE_SUBTYPE:
            case PhabricatorTransactions::TYPE_EDGE:
            case PhabricatorTransactions::TYPE_TOKEN:
            case PhabricatorTransactions::TYPE_VIEW_POLICY:
            case PhabricatorTransactions::TYPE_EDIT_POLICY:
            case PhabricatorTransactions::TYPE_JOIN_POLICY:
            case PhabricatorTransactions::TYPE_INLINESTATE:
            case PhabricatorTransactions::TYPE_SPACE:
            case PhabricatorTransactions::TYPE_COMMENT:
                return $this->applyBuiltinExternalTransaction($object, $xaction);
        }

        return $this->applyCustomExternalTransaction($object, $xaction);
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws Exception
     * @author 陈妙威
     */
    protected function applyCustomInternalTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {
        $type = $xaction->getTransactionType();
        throw new Exception(
            Yii::t("app",
                "Transaction type '{0}' is missing an internal apply implementation!",
                [
                    $type
                ]));
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws Exception
     * @author 陈妙威
     */
    protected function applyCustomExternalTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {
        $type = $xaction->getTransactionType();
        throw new Exception(
            Yii::t("app",
                "Transaction type '{0}' is missing an external apply implementation!",
                [
                    $type
                ]));
    }

    /**
     * @{class:PhabricatorTransactions} provides many built-in transactions
     * which should not require much - if any - code in specific applications.
     *
     * This method is a hook for the exceedingly-rare cases where you may need
     * to do **additional** work for built-in transactions. Developers should
     * extend this method, making sure to return the parent implementation
     * regardless of handling any transactions.
     *
     * See also @{method:applyBuiltinExternalTransaction}.
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws PhutilJSONParserException
     */
    protected function applyBuiltinInternalTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorTransactions::TYPE_VIEW_POLICY:
                $object->setAttribute("view_policy", $xaction->getNewValue());
                break;
            case PhabricatorTransactions::TYPE_EDIT_POLICY:
                $object->setAttribute("edit_policy", $xaction->getNewValue());
                break;
            case PhabricatorTransactions::TYPE_JOIN_POLICY:
                $object->setAttribute("join_policy", $xaction->getNewValue());
                break;
            case PhabricatorTransactions::TYPE_SPACE:
                $object->setSpacePHID($xaction->getNewValue());
                break;
            case PhabricatorTransactions::TYPE_SUBTYPE:
                /** @var PhabricatorEditEngineSubtypeInterface $object */
                $object->setEditEngineSubtype($xaction->getNewValue());
                break;
        }
    }

    /**
     * See @{method::applyBuiltinInternalTransaction}.
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws Exception
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionValidationException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @throws Exception
     */
    protected function applyBuiltinExternalTransaction(ActiveRecordPHID $object, PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorTransactions::TYPE_EDGE:
                if ($this->getIsInverseEdgeEditor()) {
                    // If we're writing an inverse edge transaction, don't actually
                    // do anything. The initiating editor on the other side of the
                    // transaction will take care of the edge writes.
                    break;
                }

                $old = $xaction->getOldValue();
                $new = $xaction->getNewValue();
                $src = $object->getPHID();
                $const = $xaction->getMetadataValue('edge:type');

                $type = PhabricatorEdgeType::getByConstant($const);
                if ($type->shouldWriteInverseTransactions()) {
                    $this->applyInverseEdgeTransactions(
                        $object,
                        $xaction,
                        $type->getInverseEdgeConstant());
                }

                foreach ($new as $dst_phid => $edge) {
                    $new[$dst_phid]['src'] = $src;
                }

                $editor = new PhabricatorEdgeEditor();

                foreach ($old as $dst_phid => $edge) {
                    if (!empty($new[$dst_phid])) {
                        if ($old[$dst_phid]['data'] === $new[$dst_phid]['data']) {
                            continue;
                        }
                    }
                    $editor->removeEdge($src, $const, $dst_phid);
                }

                foreach ($new as $dst_phid => $edge) {
                    if (!empty($old[$dst_phid])) {
                        if ($old[$dst_phid]['data'] === $new[$dst_phid]['data']) {
                            continue;
                        }
                    }

                    $data = array(
                        'data' => $edge['data'],
                    );

                    $editor->addEdge($src, $const, $dst_phid, $data);
                }

                $editor->save();
                $this->updateWorkboardColumns($object, $const, $old, $new);
                break;
            case PhabricatorTransactions::TYPE_VIEW_POLICY:
            case PhabricatorTransactions::TYPE_SPACE:
                $this->scrambleFileSecrets($object);
                break;
            case PhabricatorTransactions::TYPE_HISTORY:
                $this->sendHistory = true;
                break;
        }
    }

    /**
     * Fill in a transaction's common values, like author and content source.
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return PhabricatorApplicationTransaction
     * @throws Exception
     * @throws PhutilJSONParserException
     */
    protected function populateTransaction(ActiveRecordPHID $object, PhabricatorApplicationTransaction $xaction)
    {
        $actor = $this->getActor();

        // TODO: This needs to be more sophisticated once we have meta-policies.
        $xaction->setViewPolicy(PhabricatorPolicies::POLICY_PUBLIC);

        if ($actor->isOmnipotent()) {
            $xaction->setEditPolicy(PhabricatorPolicies::POLICY_NOONE);
        } else {
            $xaction->setEditPolicy($this->getActingAsPHID());
        }

        // If the transaction already has an explicit author PHID, allow it to
        // stand. This is used by applications like Owners that hook into the
        // post-apply change pipeline.
        if (!$xaction->getAuthorPHID()) {
            $xaction->setAuthorPHID($this->getActingAsPHID());
        }

        $xaction->setContentSource($this->getContentSource());
        $xaction->attachViewer($actor);
        $xaction->attachObject($object);

        if ($object->getPHID()) {
            $xaction->setObjectPHID($object->getPHID());
        }

        if ($this->getIsSilent()) {
            $xaction->setIsSilentTransaction(true);
        }

//        if ($actor->hasHighSecuritySession()) {
//            $xaction->setIsMFATransaction(true);
//        }

        return $xaction;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction[] $xactions
     * @return array
     * @author 陈妙威
     */
    protected function didApplyInternalEffects(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return $xactions;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return array
     * @author 陈妙威
     */
    protected function applyFinalEffects(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return $xactions;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction[] $xactions
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    final protected function didCommitTransactions(ActiveRecordPHID $object, array $xactions)
    {

        foreach ($xactions as $xaction) {
            $type = $xaction->getTransactionType();

            $xtype = $this->getModularTransactionType($type);
            if (!$xtype) {
                continue;
            }

            $xtype = clone $xtype;
            $xtype->setStorage($xaction);
            $xtype->didCommitTransaction($object, $xaction->getNewValue());
        }
    }

    /**
     * @param PhabricatorContentSource $content_source
     * @return $this
     * @author 陈妙威
     */
    public function setContentSource(PhabricatorContentSource $content_source)
    {
        $this->contentSource = $content_source;
        return $this;
    }

    /**
     * @param AphrontRequest $request
     * @return PhabricatorApplicationTransactionEditor
     * @throws Exception
     * @throws ReflectionException
     * @author 陈妙威
     */
    public function setContentSourceFromRequest(AphrontRequest $request)
    {
        return $this->setContentSource(PhabricatorContentSource::newFromRequest($request));
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getContentSource()
    {
        return $this->contentSource;
    }

    /**
     * @param ActiveRecord|ActiveRecordPHID|PhabricatorApplicationTransactionInterface $object
     * @param PhabricatorApplicationTransaction[] $xactions
     * @return array
     * @throws AphrontQueryException
     * @throws IntegrityException
     * @throws InvalidConfigException
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionValidationException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws AphrontObjectMissingQueryException
     * @throws Throwable
     * @author 陈妙威
     */
    final public function applyTransactions(ActiveRecordPHID $object, $xactions)
    {
        assert_instances_of([$object], PhabricatorApplicationTransactionInterface::class);

        $this->object = $object;
        $this->xactions = $xactions;
        $this->isNewObject = ($object->getPHID() === null);

        $this->validateEditParameters($object, $xactions);

        $actor = $this->requireActor();

        // NOTE: Some transaction expansion requires that the edited object be
        // attached.
        foreach ($xactions as $xaction) {
            $xaction->attachObject($object);
            $xaction->attachViewer($actor);
        }

        $xactions = $this->expandTransactions($object, $xactions);
        $xactions = $this->expandSupportTransactions($object, $xactions);
        $xactions = $this->combineTransactions($xactions);

        foreach ($xactions as $xaction) {
            $xaction = $this->populateTransaction($object, $xaction);
        }

        $is_preview = $this->getIsPreview();
        $read_locking = false;
        $transaction_open = false;

        if (!$is_preview) {
            /** @var PhabricatorApplicationTransactionValidationError[] $errors */
            $errors = array();
            $type_map = mgroup($xactions, 'getTransactionType');
            foreach ($this->getTransactionTypes() as $type) {
                $type_xactions = ArrayHelper::getValue($type_map, $type, array());
                $errors[] = $this->validateTransaction($object, $type, $type_xactions);
            }

            $errors[] = $this->validateAllTransactions($object, $xactions);
            $errors = array_mergev($errors);

            $continue_on_missing = $this->getContinueOnMissingFields();
            foreach ($errors as $key => $error) {
                if ($continue_on_missing && $error->getIsMissingFieldError()) {
                    unset($errors[$key]);
                }
            }

            if ($errors) {
                throw new PhabricatorApplicationTransactionValidationException($errors);
            }

            if ($this->raiseWarnings) {
                $warnings = array();
                foreach ($xactions as $xaction) {
                    if ($this->hasWarnings($object, $xaction)) {
                        $warnings[] = $xaction;
                    }
                }
                if ($warnings) {
                    throw new PhabricatorApplicationTransactionWarningException(
                        $warnings);
                }
            }

            $this->willApplyTransactions($object, $xactions);

            if ($object->getID()) {
                $this->buildOldRecipientLists($object, $xactions);

                $object->openTransaction();
                $transaction_open = true;

//                $object->beginReadLocking();
//                $read_locking = true;

                $object->reload();
            }

            if ($this->shouldApplyInitialEffects($object, $xactions)) {
                if (!$transaction_open) {
                    $object->openTransaction();
                    $transaction_open = true;
                }
            }
        }

        try {
            if ($this->shouldApplyInitialEffects($object, $xactions)) {
                $this->applyInitialEffects($object, $xactions);
            }

            foreach ($xactions as $xaction) {
                $this->adjustTransactionValues($object, $xaction);
            }

            // Now that we've merged and combined transactions, check for required
            // capabilities. Note that we're doing this before filtering
            // transactions: if you try to apply an edit which you do not have
            // permission to apply, we want to give you a permissions error even
            // if the edit would have no effect.
            $this->applyCapabilityChecks($object, $xactions);

            // See T13186. Fatal hard if this object has an older
            // "requireCapabilities()" method. The code may rely on this method being
            // called to apply policy checks, so err on the side of safety and fatal.
            // TODO: Remove this check after some time has passed.
            if (method_exists($this, 'requireCapabilities')) {
                throw new Exception(
                    Yii::t("app",
                        'Editor (of class "{0}") implements obsolete policy method ' .
                        'requireCapabilities(). The implementation for this Editor ' .
                        'MUST be updated. See <{1}> for discussion.',
                        [
                            get_class($this),
                            'https://secure.phabricator.com/T13186'
                        ]));
            }

            $xactions = $this->filterTransactions($object, $xactions);

            // TODO: Once everything is on EditEngine, just use getIsNewObject() to
            // figure this out instead.
            $mark_as_create = false;
            $create_type = PhabricatorTransactions::TYPE_CREATE;
            foreach ($xactions as $xaction) {
                if ($xaction->getTransactionType() == $create_type) {
                    $mark_as_create = true;
                }
            }

            if ($mark_as_create) {
                foreach ($xactions as $xaction) {
                    $xaction->setIsCreateTransaction(true);
                }
            }

            $xactions = $this->sortTransactions($xactions);
            $file_phids = $this->extractFilePHIDs($object, $xactions);

            if ($is_preview) {
                $this->loadHandles($xactions);
                return $xactions;
            }

//            $comment_editor = (new PhabricatorApplicationTransactionCommentEditor())
//                ->setActor($actor)
//                ->setActingAsPHID($this->getActingAsPHID())
//                ->setContentSource($this->getContentSource());

            if (!$transaction_open) {
                $object->openTransaction();
                $transaction_open = true;
            }

            foreach ($xactions as $xaction) {
                $this->applyInternalEffects($object, $xaction);
            }

            $xactions = $this->didApplyInternalEffects($object, $xactions);

            try {
                if (!$object->save()) {
                    throw new ActiveRecordException(Yii::t("app", "{0} create error. ", [get_class($object)]), $object->getErrorSummary(true));
                }
            } catch (AphrontDuplicateKeyQueryException $ex) {
                // This callback has an opportunity to throw a better exception,
                // so execution may end here.
                $this->didCatchDuplicateKeyException($object, $xactions, $ex);
                throw $ex;
            }

            foreach ($xactions as $xaction) {
                $xaction->setObjectPHID($object->getPHID());
                if ($xaction->getComment()) {
                    $xaction->setPHID($xaction->generatePHID());
//                    $comment_editor->applyEdit($xaction, $xaction->getComment());
                } else {

                    // TODO: This is a transitional hack to let us migrate edge
                    // transactions to a more efficient storage format. For now, we're
                    // going to write a new slim format to the database but keep the old
                    // bulky format on the objects so we don't have to upgrade all the
                    // edit logic to the new format yet. See T13051.

                    $edge_type = PhabricatorTransactions::TYPE_EDGE;
                    if ($xaction->getTransactionType() == $edge_type) {
                        $bulky_old = $xaction->getOldValue();
                        $bulky_new = $xaction->getNewValue();

                        $record = PhabricatorEdgeChangeRecord::newFromTransaction($xaction);
                        $slim_old = $record->getModernOldEdgeTransactionData();
                        $slim_new = $record->getModernNewEdgeTransactionData();

                        $xaction->setOldValue($slim_old);
                        $xaction->setNewValue($slim_new);
                        $xaction->save();

                        $xaction->setOldValue($bulky_old);
                        $xaction->setNewValue($bulky_new);
                    } else {
                        if (!$xaction->save()) {
                            throw new ActiveRecordException(Yii::t("app", "{0} create error. ", [get_class($xaction)]), $xaction->getErrorSummary(true));
                        }
                    }
                }
            }

            if ($file_phids) {
                $this->attachFiles($object, $file_phids);
            }

            foreach ($xactions as $xaction) {
                $this->applyExternalEffects($object, $xaction);
            }

            $xactions = $this->applyFinalEffects($object, $xactions);

            if ($read_locking) {
                $object->endReadLocking();
                $read_locking = false;
            }

            if ($transaction_open) {
                $object->saveTransaction();
                $transaction_open = false;
            }

            $this->didCommitTransactions($object, $xactions);

        } catch (Exception $ex) {
            if ($read_locking) {
                $object->endReadLocking();
                $read_locking = false;
            }

            if ($transaction_open) {
                $object->killTransaction();
                $transaction_open = false;
            }

            throw $ex;
        }

        // If we need to perform cache engine updates, execute them now.
        (new PhabricatorCacheEngine())
            ->updateObject($object);

        // Now that we've completely applied the core transaction set, try to apply
        // Herald rules. Herald rules are allowed to either take direct actions on
        // the database (like writing flags), or take indirect actions (like saving
        // some targets for CC when we generate mail a little later), or return
        // transactions which we'll apply normally using another Editor.

        // First, check if *this* is a sub-editor which is itself applying Herald
        // rules: if it is, stop working and return so we don't descend into
        // madness.

        // Otherwise, we're not a Herald editor, so process Herald rules (possibly
        // using a Herald editor to apply resulting transactions) and then send out
        // mail, notifications, and feed updates about everything.

        if ($this->getIsHeraldEditor()) {
            // We are the Herald editor, so stop work here and return the updated
            // transactions.
            return $xactions;
        } else if ($this->getIsInverseEdgeEditor()) {
            // Do not run Herald if we're just recording that this object was
            // mentioned elsewhere. This tends to create Herald side effects which
            // feel arbitrary, and can really slow down edits which mention a large
            // number of other objects. See T13114.
        } else if ($this->shouldApplyHeraldRules($object, $xactions)) {
            // We are not the Herald editor, so try to apply Herald rules.
            $herald_xactions = $this->applyHeraldRules($object, $xactions);

            if ($herald_xactions) {
                $xscript_id = $this->getHeraldTranscript()->getID();
                foreach ($herald_xactions as $herald_xaction) {
                    // Don't set a transcript ID if this is a transaction from another
                    // application or source, like Owners.
                    if ($herald_xaction->getAuthorPHID()) {
                        continue;
                    }

                    $herald_xaction->setMetadataValue('herald:transcriptID', $xscript_id);
                }

                // NOTE: We're acting as the omnipotent user because rules deal with
                // their own policy issues. We use a synthetic author PHID (the
                // Herald application) as the author of record, so that transactions
                // will render in a reasonable way ("Herald assigned this task ...").
                $herald_actor = PhabricatorUser::getOmnipotentUser();
                $herald_phid = (new PhabricatorHeraldApplication())->getPHID();

                // TODO: It would be nice to give transactions a more specific source
                // which points at the rule which generated them. You can figure this
                // out from transcripts, but it would be cleaner if you didn't have to.

                $herald_source = PhabricatorContentSource::newForSource(
                    PhabricatorHeraldContentSource::SOURCECONST);

                /** @var PhabricatorApplicationTransactionEditor $newv */
                $newv = newv(get_class($this), array());
                $herald_editor = $newv
                    ->setContinueOnNoEffect(true)
                    ->setContinueOnMissingFields(true)
                    ->setParentMessageID($this->getParentMessageID())
                    ->setIsHeraldEditor(true)
                    ->setActor($herald_actor)
                    ->setActingAsPHID($herald_phid)
                    ->setContentSource($herald_source);

                $herald_xactions = $herald_editor->applyTransactions(
                    $object,
                    $herald_xactions);

                // Merge the new transactions into the transaction list: we want to
                // send email and publish feed stories about them, too.
                $xactions = array_merge($xactions, $herald_xactions);
            }

            // If Herald did not generate transactions, we may still need to handle
            // "Send an Email" rules.
            $adapter = $this->getHeraldAdapter();
            $this->heraldEmailPHIDs = $adapter->getEmailPHIDs();
            $this->heraldForcedEmailPHIDs = $adapter->getForcedEmailPHIDs();
            $this->webhookMap = $adapter->getWebhookMap();
        }

        $xactions = $this->didApplyTransactions($object, $xactions);

        if ($object instanceof PhabricatorCustomFieldInterface) {
            // Maybe this makes more sense to move into the search index itself? For
            // now I'm putting it here since I think we might end up with things that
            // need it to be up to date once the next page loads, but if we don't go
            // there we could move it into search once search moves to the daemons.

            // It now happens in the search indexer as well, but the search indexer is
            // always daemonized, so the logic above still potentially holds. We could
            // possibly get rid of this. The major motivation for putting it in the
            // indexer was to enable reindexing to work.

            $fields = PhabricatorCustomField::getObjectFields(
                $object,
                PhabricatorCustomField::ROLE_APPLICATIONSEARCH);
            $fields->readFieldsFromStorage($object);
            $fields->rebuildIndexes($object);
        }

//        $herald_xscript = $this->getHeraldTranscript();
//        if ($herald_xscript) {
//            $herald_header = $herald_xscript->getXHeraldRulesHeader();
//            $herald_header = HeraldTranscript::saveXHeraldRulesHeader(
//                $object->getPHID(),
//                $herald_header);
//        } else {
//            $herald_header = HeraldTranscript::loadXHeraldRulesHeader(
//                $object->getPHID());
//        }
//        $this->heraldHeader = $herald_header;

        // We're going to compute some of the data we'll use to publish these
        // transactions here, before queueing a worker.
        //
        // Primarily, this is more correct: we want to publish the object as it
        // exists right now. The worker may not execute for some time, and we want
        // to use the current To/CC list, not respect any changes which may occur
        // between now and when the worker executes.
        //
        // As a secondary benefit, this tends to reduce the amount of state that
        // Editors need to pass into workers.
        /** @var ActiveRecordPHID $object */
        $object = $this->willPublish($object, $xactions);

        if (!$this->getIsSilent()) {
            if ($this->shouldSendMail($object, $xactions)) {
                $this->mailShouldSend = true;
                $this->mailToPHIDs = $this->getMailTo($object);
                $this->mailCCPHIDs = $this->getMailCC($object);
                $this->mailUnexpandablePHIDs = $this->newMailUnexpandablePHIDs($object);

                // Add any recipients who were previously on the notification list
                // but were removed by this change.
                $this->applyOldRecipientLists();

                if ($object instanceof PhabricatorSubscribableInterface) {
                    $this->mailMutedPHIDs = PhabricatorEdgeQuery::loadDestinationPHIDs(
                        $object->getPHID(),
                        PhabricatorMutedByEdgeType::EDGECONST);
                } else {
                    $this->mailMutedPHIDs = array();
                }

                $mail_xactions = $this->getTransactionsForMail($object, $xactions);
                $stamps = $this->newMailStamps($object, $xactions);
                foreach ($stamps as $stamp) {
                    $this->mailStamps[] = $stamp->toDictionary();
                }
            }

            if ($this->shouldPublishFeedStory($object, $xactions)) {
                $this->feedShouldPublish = true;
                $this->feedRelatedPHIDs = $this->getFeedRelatedPHIDs(
                    $object,
                    $xactions);
                $this->feedNotifyPHIDs = $this->getFeedNotifyPHIDs(
                    $object,
                    $xactions);
            }
        }

        PhabricatorWorker::scheduleTask(
            'PhabricatorApplicationTransactionPublishWorker',
            array(
                'objectPHID' => $object->getPHID(),
                'actorPHID' => $this->getActingAsPHID(),
                'xactionPHIDs' => mpull($xactions, 'getPHID'),
                'state' => $this->getWorkerState(),
            ),
            array(
                'objectPHID' => $object->getPHID(),
                'priority' => PhabricatorWorker::PRIORITY_ALERTS,
            ));

        $this->flushTransactionQueue($object);

        return $xactions;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @param Exception $ex
     * @author 陈妙威
     */
    protected function didCatchDuplicateKeyException(
        ActiveRecordPHID $object,
        array $xactions,
        Exception $ex)
    {
        return;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return array
     * @throws AphrontQueryException
     * @throws IntegrityException
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws Throwable
     * @throws UnknownPropertyException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    public function publishTransactions(
        ActiveRecordPHID $object,
        array $xactions)
    {

        $this->object = $object;
        $this->xactions = $xactions;

        // Hook for edges or other properties that may need (re-)loading
        $object = $this->willPublish($object, $xactions);

        // The object might have changed, so reassign it.
        $this->object = $object;

        $messages = array();
        if ($this->mailShouldSend) {
            $messages = $this->buildMail($object, $xactions);
        }

        if ($this->supportsSearch()) {
            PhabricatorSearchWorker::queueDocumentForIndexing(
                $object->getPHID(),
                array(
                    'transactionPHIDs' => mpull($xactions, 'getPHID'),
                ));
        }

        if ($this->feedShouldPublish) {
            $mailed = array();
            foreach ($messages as $mail) {
                foreach ($mail->buildRecipientList() as $phid) {
                    $mailed[$phid] = $phid;
                }
            }

            $this->publishFeedStory($object, $xactions, $mailed);
        }

        if ($this->sendHistory) {
            $history_mail = $this->buildHistoryMail($object);
            if ($history_mail) {
                $messages[] = $history_mail;
            }
        }

        // NOTE: This actually sends the mail. We do this last to reduce the chance
        // that we send some mail, hit an exception, then send the mail again when
        // retrying.
        foreach ($messages as $mail) {
            $mail->save();
        }

        // TODO 添加webhook
//        $this->queueWebhooks($object, $xactions);

        return $xactions;
    }

    /**
     * @param $object
     * @param array $xactions
     * @return array
     * @author 陈妙威
     */
    protected function didApplyTransactions($object, array $xactions)
    {
        // Hook for subclasses.
        return $xactions;
    }

    /**
     * @param array $xactions
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    private function loadHandles(array $xactions)
    {
        $phids = array();
        foreach ($xactions as $key => $xaction) {
            $phids[$key] = $xaction->getRequiredHandlePHIDs();
        }
        $handles = array();
        $merged = array_mergev($phids);
        if ($merged) {
            $handles = (new PhabricatorHandleQuery())
                ->setViewer($this->requireActor())
                ->withPHIDs($merged)
                ->execute();
        }
        foreach ($xactions as $key => $xaction) {
            $xaction->setHandles(array_select_keys($handles, $phids[$key]));
        }
    }

    /**
     * @param ActiveRecordPHID $object
     * @throws Exception
     * @author 陈妙威
     */
    private function loadSubscribers(ActiveRecordPHID $object)
    {
        if ($object->getPHID() &&
            ($object instanceof PhabricatorSubscribableInterface)) {
            $subs = PhabricatorSubscribersQuery::loadSubscribersForPHID(
                $object->getPHID());
            $this->setAttribute(PhabricatorSubscriptionsEditEngineExtension::FIELDKEY, array_fuse($subs));
        } else {
            $this->setAttribute(PhabricatorSubscriptionsEditEngineExtension::FIELDKEY, []);
        }
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction[] $xactions
     * @throws Exception
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function validateEditParameters(ActiveRecordPHID $object, $xactions)
    {

        if (!$this->getContentSource()) {
            throw new PhutilInvalidStateException('setContentSource');
        }

        // Do a bunch of sanity checks that the incoming transactions are fresh.
        // They should be unsaved and have only "transactionType" and "newValue"
        // set.

        $types = array_fill_keys($this->getTransactionTypes(), true);

        assert_instances_of($xactions, PhabricatorApplicationTransaction::class);
        foreach ($xactions as $xaction) {
            if ($xaction->getPHID() || $xaction->getID()) {
                throw new PhabricatorApplicationTransactionStructureException(
                    $xaction,
                    Yii::t("app", 'You can not apply transactions which already have IDs/PHIDs!'));
            }

            if ($xaction->getObjectPHID()) {
                throw new PhabricatorApplicationTransactionStructureException(
                    $xaction,
                    Yii::t("app",
                        'You can not apply transactions which already have {0}!',
                        [
                            'objectPHIDs'
                        ]));
            }

            if ($xaction->comment_phid) {
                throw new PhabricatorApplicationTransactionStructureException(
                    $xaction,
                    Yii::t("app",
                        'You can not apply transactions which already have {0}!',
                        [
                            'commentPHIDs'
                        ]));
            }

            if ($xaction->comment_version !== 0) {
                throw new PhabricatorApplicationTransactionStructureException(
                    $xaction,
                    Yii::t("app",
                        'You can not apply transactions which already have {0}',
                        [
                            'commentVersions!'
                        ]));
            }

            $expect_value = !$xaction->shouldGenerateOldValue();
            $has_value = $xaction->hasOldValue();

            if ($expect_value && !$has_value) {
                throw new PhabricatorApplicationTransactionStructureException(
                    $xaction,
                    Yii::t("app",
                        'This transaction is supposed to have an {0} set, but it does not!',
                        [
                            'oldValue'
                        ]));
            }

            if ($has_value && !$expect_value) {
                throw new PhabricatorApplicationTransactionStructureException(
                    $xaction,
                    Yii::t("app",
                        'This transaction should generate its {0} automatically, ' .
                        'but has already had one set!',
                        [
                            'oldValue'
                        ]));
            }

            $type = $xaction->getTransactionType();
            if (empty($types[$type])) {
                throw new PhabricatorApplicationTransactionStructureException(
                    $xaction,
                    Yii::t("app",
                        'Transaction has type "{0}", but that transaction type is not ' .
                        'supported by this editor ({1}).',
                        [
                            $type,
                            get_class($this)
                        ]));
            }
        }
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    private function applyCapabilityChecks(
        ActiveRecordPHID $object,
        array $xactions)
    {
        assert_instances_of($xactions, PhabricatorApplicationTransaction::class);

        $can_edit = PhabricatorPolicyCapability::CAN_EDIT;

        if ($this->getIsNewObject()) {
            // If we're creating a new object, we don't need any special capabilities
            // on the object. The actor has already made it through creation checks,
            // and objects which haven't been created yet often can not be
            // meaningfully tested for capabilities anyway.
            $required_capabilities = array();
        } else {
            if (!$xactions && !$this->xactions) {
                // If we aren't doing anything, require CAN_EDIT to improve consistency.
                $required_capabilities = array($can_edit);
            } else {
                $required_capabilities = array();

                foreach ($xactions as $xaction) {
                    $type = $xaction->getTransactionType();

                    $xtype = $this->getModularTransactionType($type);
                    if (!$xtype) {
                        $capabilities = $this->getLegacyRequiredCapabilities($xaction);
                    } else {
                        $capabilities = $xtype->getRequiredCapabilities($object, $xaction);
                    }

                    // For convenience, we allow flexibility in the return types because
                    // it's very unusual that a transaction actually requires multiple
                    // capability checks.
                    if ($capabilities === null) {
                        $capabilities = array();
                    } else {
                        $capabilities = (array)$capabilities;
                    }

                    foreach ($capabilities as $capability) {
                        $required_capabilities[$capability] = $capability;
                    }
                }
            }
        }

        $required_capabilities = array_fuse($required_capabilities);
        $actor = $this->getActor();

        if ($required_capabilities) {
            (new PhabricatorPolicyFilter())
                ->setViewer($actor)
                ->requireCapabilities($required_capabilities)
                ->raisePolicyExceptions(true)
                ->apply(array($object));
        }
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return null
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    private function getLegacyRequiredCapabilities(
        PhabricatorApplicationTransaction $xaction)
    {

        $type = $xaction->getTransactionType();
        switch ($type) {
            case PhabricatorTransactions::TYPE_COMMENT:
                // TODO: Comments technically require CAN_INTERACT, but this is
                // currently somewhat special and handled through EditEngine. For now,
                // don't enforce it here.
                return null;
            case PhabricatorTransactions::TYPE_SUBSCRIBERS:
                // TODO: Removing subscribers other than yourself should probably
                // require CAN_EDIT permission. You can do this via the API but
                // generally can not via the web interface.
                return null;
            case PhabricatorTransactions::TYPE_TOKEN:
                // TODO: This technically requires CAN_INTERACT, like comments.
                return null;
            case PhabricatorTransactions::TYPE_HISTORY:
                // This is a special magic transaction which sends you history via
                // email and is only partially supported in the upstream. You don't
                // need any capabilities to apply it.
                return null;
            case PhabricatorTransactions::TYPE_EDGE:
                return $this->getLegacyRequiredEdgeCapabilities($xaction);
            default:
                // For other older (non-modular) transactions, always require exactly
                // CAN_EDIT. Transactions which do not need CAN_EDIT or need additional
                // capabilities must move to ModularTransactions.
                return PhabricatorPolicyCapability::CAN_EDIT;
        }
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return null
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    private function getLegacyRequiredEdgeCapabilities(
        PhabricatorApplicationTransaction $xaction)
    {

        // You don't need to have edit permission on an object to mention it or
        // otherwise add a relationship pointing toward it.
        if ($this->getIsInverseEdgeEditor()) {
            return null;
        }

        $edge_type = $xaction->getMetadataValue('edge:type');
        switch ($edge_type) {
            case PhabricatorMutedByEdgeType::EDGECONST:
                // At time of writing, you can only write this edge for yourself, so
                // you don't need permissions. If you can eventually mute an object
                // for other users, this would need to be revisited.
                return null;
            case PhabricatorObjectMentionsObjectEdgeType::EDGECONST:
                return null;
//            case PhabricatorProjectProjectHasMemberEdgeType::EDGECONST:
//                $old = $xaction->getOldValue();
//                $new = $xaction->getNewValue();
//
//                $add = array_keys(array_diff_key($new, $old));
//                $rem = array_keys(array_diff_key($old, $new));
//
//                $actor_phid = $this->requireActor()->getPHID();
//
//                $is_join = (($add === array($actor_phid)) && !$rem);
//                $is_leave = (($rem === array($actor_phid)) && !$add);
//
//                if ($is_join) {
//                    // You need CAN_JOIN to join a project.
//                    return PhabricatorPolicyCapability::CAN_JOIN;
//                }
//
//                if ($is_leave) {
//                    $object = $this->object;
//                    // You usually don't need any capabilities to leave a project...
//                    if ($object->getIsMembershipLocked()) {
//                        // ...you must be able to edit to leave locked projects, though.
//                        return PhabricatorPolicyCapability::CAN_EDIT;
//                    } else {
//                        return null;
//                    }
//                }
//
//                // You need CAN_EDIT to change members other than yourself.
//                return PhabricatorPolicyCapability::CAN_EDIT;
            default:
                return PhabricatorPolicyCapability::CAN_EDIT;
        }
    }


    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @param array $changes
     * @return null
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @throws Exception
     * @author 陈妙威
     */
    private function buildSubscribeTransaction(
        ActiveRecordPHID $object,
        array $xactions,
        array $changes)
    {

        if (!($object instanceof PhabricatorSubscribableInterface)) {
            return null;
        }

        if ($this->shouldEnableMentions($object, $xactions)) {
            // Identify newly mentioned users. We ignore users who were previously
            // mentioned so that we don't re-subscribe users after an edit of text
            // which mentions them.
            $old_texts = mpull($changes, 'getOldValue');
            $new_texts = OranginsUtil::mpull($changes, 'getNewValue');

            $old_phids = PhabricatorMarkupEngine::extractPHIDsFromMentions(
                $this->getActor(),
                $old_texts);

            $new_phids = PhabricatorMarkupEngine::extractPHIDsFromMentions(
                $this->getActor(),
                $new_texts);

            $phids = array_diff($new_phids, $old_phids);
            $phids = array();
        } else {
            $phids = array();
        }

        $this->mentionedPHIDs = $phids;

        if ($object->getPHID()) {
            // Don't try to subscribe already-subscribed mentions: we want to generate
            // a dialog about an action having no effect if the user explicitly adds
            // existing CCs, but not if they merely mention existing subscribers.
            $phids = array_diff($phids, $this->subscribers);
        }

        if ($phids) {
            $users = PhabricatorUser::find()
                ->setViewer($this->getActor())
                ->withPHIDs($phids)
                ->execute();
            $users = OranginsUtil::mpull($users, null, 'getPHID');

            foreach ($phids as $key => $phid) {
                // Do not subscribe mentioned users
                // who do not have VIEW Permissions
                if ($object instanceof PhabricatorPolicyInterface
                    && !PhabricatorPolicyFilter::hasCapability(
                        $users[$phid],
                        $object,
                        PhabricatorPolicyCapability::CAN_VIEW)
                ) {
                    unset($phids[$key]);
                } else {
                    if ($object->isAutomaticallySubscribed($phid)) {
                        unset($phids[$key]);
                    }
                }
            }
            $phids = array_values($phids);
        }
        // No else here to properly return null should we unset all subscriber
        if (!$phids) {
            return null;
        }

        /** @var PhabricatorApplicationTransaction $xaction */
        $xaction = OranginsUtil::newv(get_class(OranginsUtil::head($xactions)), array());
        $xaction->setTransactionType(PhabricatorTransactions::TYPE_SUBSCRIBERS);
        $xaction->setNewValue(array('+' => $phids));

        return $xaction;
    }

    /**
     * @param PhabricatorApplicationTransaction $u
     * @param PhabricatorApplicationTransaction $v
     * @return PhabricatorApplicationTransaction|null
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    protected function mergeTransactions(
        PhabricatorApplicationTransaction $u,
        PhabricatorApplicationTransaction $v)
    {

        $type = $u->getTransactionType();

        $xtype = $this->getModularTransactionType($type);
        if ($xtype) {
            $object = $this->object;
            return $xtype->mergeTransactions($object, $u, $v);
        }

        switch ($type) {
            case PhabricatorTransactions::TYPE_SUBSCRIBERS:
                return $this->mergePHIDOrEdgeTransactions($u, $v);
            case PhabricatorTransactions::TYPE_EDGE:
                $u_type = $u->getMetadataValue('edge:type');
                $v_type = $v->getMetadataValue('edge:type');
                if ($u_type == $v_type) {
                    return $this->mergePHIDOrEdgeTransactions($u, $v);
                }
                return null;
        }

        // By default, do not merge the transactions.
        return null;
    }

    /**
     * Optionally expand transactions which imply other effects. For example,
     * resigning from a revision in Differential implies removing yourself as
     * a reviewer.
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return PhabricatorApplicationTransaction[]
     */
    protected function expandTransactions(
        ActiveRecordPHID $object,
        array $xactions)
    {

        $results = array();
        foreach ($xactions as $xaction) {
            foreach ($this->expandTransaction($object, $xaction) as $expanded) {
                $results[] = $expanded;
            }
        }

        return $results;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return array
     * @author 陈妙威
     */
    protected function expandTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {
        return array($xaction);
    }


    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return array
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws PhutilMethodNotImplementedException
     * @throws ReflectionException
     * @author 陈妙威
     */
    public function getExpandedSupportTransactions(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        $xactions = array($xaction);
        $xactions = $this->expandSupportTransactions(
            $object,
            $xactions);

        if (count($xactions) == 1) {
            return array();
        }

        foreach ($xactions as $index => $cxaction) {
            if ($cxaction === $xaction) {
                unset($xactions[$index]);
                break;
            }
        }

        return $xactions;
    }

    /**
     * @param ActiveRecord|ActiveRecordPHID $object
     * @param array $xactions
     * @return PhabricatorApplicationTransaction[]
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws PhutilMethodNotImplementedException
     * @throws ReflectionException
     * @author 陈妙威
     */
    private function expandSupportTransactions(
        ActiveRecordPHID $object,
        array $xactions)
    {
        $this->loadSubscribers($object);
        $xactions = $this->applyImplicitCC($object, $xactions);

        $changes = $this->getRemarkupChanges($xactions);
        $subscribe_xaction = $this->buildSubscribeTransaction(
            $object,
            $xactions,
            $changes);
        if ($subscribe_xaction) {
            $xactions[] = $subscribe_xaction;
        }

        // TODO: For now, this is just a placeholder.
        $engine = PhabricatorMarkupEngine::getEngine('extract');
        $engine->setConfig('viewer', $this->requireActor());

        $block_xactions = $this->expandRemarkupBlockTransactions(
            $object,
            $xactions,
            $changes,
            $engine);

        foreach ($block_xactions as $xaction) {
            $xactions[] = $xaction;
        }

        return $xactions;
    }

    /**
     * @param array $xactions
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    private function getRemarkupChanges(array $xactions)
    {
        $changes = array();

        foreach ($xactions as $key => $xaction) {
            foreach ($this->getRemarkupChangesFromTransaction($xaction) as $change) {
                $changes[] = $change;
            }
        }

        return $changes;
    }

    /**
     * @param PhabricatorApplicationTransaction $transaction
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function getRemarkupChangesFromTransaction(
        PhabricatorApplicationTransaction $transaction)
    {
        return $transaction->getRemarkupChanges();
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @param array $changes
     * @param PhutilMarkupEngine $engine
     * @return array
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function expandRemarkupBlockTransactions(
        ActiveRecordPHID $object,
        array $xactions,
        array $changes,
        PhutilMarkupEngine $engine)
    {

        $block_xactions = $this->expandCustomRemarkupBlockTransactions(
            $object,
            $xactions,
            $changes,
            $engine);

        $mentioned_phids = array();
        if ($this->shouldEnableMentions($object, $xactions)) {
            foreach ($changes as $change) {
                // Here, we don't care about processing only new mentions after an edit
                // because there is no way for an object to ever "unmention" itself on
                // another object, so we can ignore the old value.
                $engine->markupText($change->getNewValue());

                $mentioned_phids += $engine->getTextMetadata(
                    PhabricatorObjectRemarkupRule::KEY_MENTIONED_OBJECTS,
                    array());
            }
        }

        if (!$mentioned_phids) {
            return $block_xactions;
        }

        $mentioned_objects = (new PhabricatorObjectQuery())
            ->setViewer($this->getActor())
            ->withPHIDs($mentioned_phids)
            ->execute();

        $mentionable_phids = array();
        if ($this->shouldEnableMentions($object, $xactions)) {
            foreach ($mentioned_objects as $mentioned_object) {
                if ($mentioned_object instanceof PhabricatorMentionableInterface) {
                    $mentioned_phid = $mentioned_object->getPHID();
                    if (ArrayHelper::getValue($this->getUnmentionablePHIDMap(), $mentioned_phid)) {
                        continue;
                    }
                    // don't let objects mention themselves
                    if ($object->getPHID() && $mentioned_phid == $object->getPHID()) {
                        continue;
                    }
                    $mentionable_phids[$mentioned_phid] = $mentioned_phid;
                }
            }
        }

        if ($mentionable_phids) {
            $edge_type = PhabricatorObjectMentionsObjectEdgeType::EDGECONST;

            /** @var PhabricatorApplicationTransaction $newv */
            $newv = newv(get_class(head($xactions)), array());
            $block_xactions[] = $newv
                ->setIgnoreOnNoEffect(true)
                ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
                ->setMetadataValue('edge:type', $edge_type)
                ->setNewValue(array('+' => $mentionable_phids));
        }

        return $block_xactions;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @param array $changes
     * @param PhutilMarkupEngine $engine
     * @return array
     * @author 陈妙威
     */
    protected function expandCustomRemarkupBlockTransactions(
        ActiveRecordPHID $object,
        array $xactions,
        array $changes,
        PhutilMarkupEngine $engine)
    {
        return array();
    }


    /**
     * Attempt to combine similar transactions into a smaller number of total
     * transactions. For example, two transactions which edit the title of an
     * object can be merged into a single edit.
     * @param array $xactions
     * @return PhabricatorApplicationTransaction[]
     * @throws Exception
     * @throws PhutilJSONParserException
     */
    private function combineTransactions(array $xactions)
    {
        $stray_comments = array();

        $result = array();
        $types = array();
        foreach ($xactions as $key => $xaction) {
            $type = $xaction->getTransactionType();
            if (isset($types[$type])) {
                foreach ($types[$type] as $other_key) {
                    $other_xaction = $result[$other_key];

                    // Don't merge transactions with different authors. For example,
                    // don't merge Herald transactions and owners transactions.
                    if ($other_xaction->getAuthorPHID() != $xaction->getAuthorPHID()) {
                        continue;
                    }

                    $merged = $this->mergeTransactions($result[$other_key], $xaction);
                    if ($merged) {
                        $result[$other_key] = $merged;

                        if ($xaction->getComment() &&
                            ($xaction->getComment() !== $merged->getComment())) {
                            $stray_comments[] = $xaction->getComment();
                        }

                        if ($result[$other_key]->getComment() &&
                            ($result[$other_key]->getComment() !== $merged->getComment())) {
                            $stray_comments[] = $result[$other_key]->getComment();
                        }

                        // Move on to the next transaction.
                        continue 2;
                    }
                }
            }
            $result[$key] = $xaction;
            $types[$type][] = $key;
        }

        // If we merged any comments away, restore them.
        foreach ($stray_comments as $comment) {
            /** @var PhabricatorApplicationTransaction $xaction */
            $xaction = newv(get_class(head($result)), array());
            $xaction->setTransactionType(PhabricatorTransactions::TYPE_COMMENT);
            $xaction->setComment($comment);
            $result[] = $xaction;
        }

        return array_values($result);
    }

    /**
     * @param PhabricatorApplicationTransaction $u
     * @param PhabricatorApplicationTransaction $v
     * @return PhabricatorApplicationTransaction
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function mergePHIDOrEdgeTransactions(
        PhabricatorApplicationTransaction $u,
        PhabricatorApplicationTransaction $v)
    {

        $result = $u->getNewValue();
        foreach ($v->getNewValue() as $key => $value) {
            if ($u->getTransactionType() == PhabricatorTransactions::TYPE_EDGE) {
                if (empty($result[$key])) {
                    $result[$key] = $value;
                } else {
                    // We're merging two lists of edge adds, sets, or removes. Merge
                    // them by merging individual PHIDs within them.
                    $merged = $result[$key];

                    foreach ($value as $dst => $v_spec) {
                        if (empty($merged[$dst])) {
                            $merged[$dst] = $v_spec;
                        } else {
                            // Two transactions are trying to perform the same operation on
                            // the same edge. Normalize the edge data and then merge it. This
                            // allows transactions to specify how data merges execute in a
                            // precise way.

                            $u_spec = $merged[$dst];

                            if (!is_array($u_spec)) {
                                $u_spec = array('dst' => $u_spec);
                            }
                            if (!is_array($v_spec)) {
                                $v_spec = array('dst' => $v_spec);
                            }

                            $ux_data = ArrayHelper::getValue($u_spec, 'data', array());
                            $vx_data = ArrayHelper::getValue($v_spec, 'data', array());

                            $merged_data = $this->mergeEdgeData(
                                $u->getMetadataValue('edge:type'),
                                $ux_data,
                                $vx_data);

                            $u_spec['data'] = $merged_data;
                            $merged[$dst] = $u_spec;
                        }
                    }

                    $result[$key] = $merged;
                }
            } else {
                $result[$key] = array_merge($value, ArrayHelper::getValue($result, $key, array()));
            }
        }
        $u->setNewValue($result);

        // When combining an "ignore" transaction with a normal transaction, make
        // sure we don't propagate the "ignore" flag.
        if (!$v->getIgnoreOnNoEffect()) {
            $u->setIgnoreOnNoEffect(false);
        }

        return $u;
    }

    /**
     * @param $type
     * @param array $u
     * @param array $v
     * @return array
     * @author 陈妙威
     */
    protected function mergeEdgeData($type, array $u, array $v)
    {
        return $v + $u;
    }

    /**
     * 获取信的值（PHID数组）
     * @param PhabricatorApplicationTransaction $xaction
     * @param null $old
     * @return array
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    protected function getPHIDTransactionNewValue(
        PhabricatorApplicationTransaction $xaction,
        $old = null)
    {

        if ($old !== null) {
            $old = OranginsUtil::array_fuse($old);
        } else {
            $old = OranginsUtil::array_fuse($xaction->getOldValue());
        }

        $new = $xaction->getNewValue();
        return $this->getPHIDList($old, $new);
    }

    /**
     * @param array $old
     * @param array $new
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function getPHIDList(array $old, array $new)
    {
        $new_add = ArrayHelper::getValue($new, '+', array());
        unset($new['+']);
        $new_rem = ArrayHelper::getValue($new, '-', array());
        unset($new['-']);
        $new_set = ArrayHelper::getValue($new, '=', null);
        if ($new_set !== null) {
            $new_set = OranginsUtil::array_fuse($new_set);
        }
        unset($new['=']);

        if ($new) {
            throw new Exception(
                Yii::t("app",
                    "Invalid '{0}' value for PHID transaction. Value should contain only " .
                    "keys '{1}' (add PHIDs), '{2}' (remove PHIDs) and '{3}' (set PHIDS).",
                    [
                        'new',
                        '+',
                        '-',
                        '='
                    ]));
        }

        $result = array();

        foreach ($old as $phid) {
            if ($new_set !== null && empty($new_set[$phid])) {
                continue;
            }
            $result[$phid] = $phid;
        }

        if ($new_set !== null) {
            foreach ($new_set as $phid) {
                $result[$phid] = $phid;
            }
        }

        foreach ($new_add as $phid) {
            $result[$phid] = $phid;
        }

        foreach ($new_rem as $phid) {
            unset($result[$phid]);
        }

        return array_values($result);
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return array
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    protected function getEdgeTransactionNewValue(
        PhabricatorApplicationTransaction $xaction)
    {

        $new = $xaction->getNewValue();
        $new_add = ArrayHelper::getValue($new, '+', array());
        unset($new['+']);
        $new_rem = ArrayHelper::getValue($new, '-', array());
        unset($new['-']);
        $new_set = ArrayHelper::getValue($new, '=', null);
        unset($new['=']);

        if ($new) {
            throw new Exception(
                Yii::t("app",
                    "Invalid '{0}' value for Edge transaction. Value should contain only " .
                    "keys '{1}' (add edges), '{2}' (remove edges) and '{3}' (set edges).",
                    [
                        'new',
                        '+',
                        '-',
                        '='
                    ]));
        }

        $old = $xaction->getOldValue();

        $lists = array($new_set, $new_add, $new_rem);
        foreach ($lists as $list) {
            $this->checkEdgeList($list, $xaction->getMetadataValue('edge:type'));
        }

        $result = array();
        foreach ($old as $dst_phid => $edge) {
            if ($new_set !== null && empty($new_set[$dst_phid])) {
                continue;
            }
            $result[$dst_phid] = $this->normalizeEdgeTransactionValue(
                $xaction,
                $edge,
                $dst_phid);
        }

        if ($new_set !== null) {
            foreach ($new_set as $dst_phid => $edge) {
                $result[$dst_phid] = $this->normalizeEdgeTransactionValue(
                    $xaction,
                    $edge,
                    $dst_phid);
            }
        }

        foreach ($new_add as $dst_phid => $edge) {
            $result[$dst_phid] = $this->normalizeEdgeTransactionValue(
                $xaction,
                $edge,
                $dst_phid);
        }

        foreach ($new_rem as $dst_phid => $edge) {
            unset($result[$dst_phid]);
        }

        return $result;
    }

    /**
     * @param $list
     * @param $edge_type
     * @throws Exception
     * @author 陈妙威
     */
    private function checkEdgeList($list, $edge_type)
    {
        if (!$list) {
            return;
        }
        foreach ($list as $key => $item) {
            if (PhabricatorPHID::phid_get_type($key) === PhabricatorPHIDConstants::PHID_TYPE_UNKNOWN) {
                throw new Exception(
                    Yii::t("app",
                        'Edge transactions must have destination PHIDs as in edge ' .
                        'lists (found key "{0}" on transaction of type "{1}").',
                        [
                            $key,
                            $edge_type
                        ]));
            }
            if (!is_array($item) && $item !== $key) {
                throw new Exception(
                    Yii::t("app",
                        'Edge transactions must have PHIDs or edge specs as values ' .
                        '(found value "{0}" on transaction of type "{1}").',
                        [
                            $item,
                            $edge_type
                        ]));
            }
        }
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @param $edge
     * @param $dst_phid
     * @return array
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     *
     */
    private function normalizeEdgeTransactionValue(
        PhabricatorApplicationTransaction $xaction,
        $edge,
        $dst_phid)
    {

        if (!is_array($edge)) {
            if ($edge != $dst_phid) {
                throw new Exception(
                    Yii::t("app",
                        'Transaction edge data must either be the edge PHID or an edge ' .
                        'specification dictionary.'));
            }
            $edge = array();
        } else {
            foreach ($edge as $key => $value) {
                switch ($key) {
                    case 'src':
                    case 'dst':
                    case 'type':
                    case 'data':
                    case 'created_at':
                    case 'updated_at':
                    case 'seq':
                    case 'data_id':
                        break;
                    default:
                        throw new Exception(
                            Yii::t("app",
                                'Transaction edge specification contains unexpected key "{0}".',
                                [
                                    $key
                                ]));
                }
            }
        }

        $edge['dst'] = $dst_phid;

        $edge_type = $xaction->getMetadataValue('edge:type');
        if (empty($edge['type'])) {
            $edge['type'] = $edge_type;
        } else {
            if ($edge['type'] != $edge_type) {
                $this_type = $edge['type'];
                throw new Exception(
                    Yii::t("app",
                        "Edge transaction includes edge of type '{0}', but " .
                        "transaction is of type '{1}'. Each edge transaction " .
                        "must alter edges of only one type.",
                        [
                            $this_type,
                            $edge_type
                        ]));
            }
        }

        if (!isset($edge['data'])) {
            $edge['data'] = array();
        }

        return $edge;
    }

    /**
     * @param array $xactions
     * @return array
     * @author 陈妙威
     */
    protected function sortTransactions(array $xactions)
    {
        $head = array();
        $tail = array();

        // Move bare comments to the end, so the actions precede them.
        foreach ($xactions as $xaction) {
            $type = $xaction->getTransactionType();
            if ($type == PhabricatorTransactions::TYPE_COMMENT) {
                $tail[] = $xaction;
            } else {
                $head[] = $xaction;
            }
        }

        return array_values(array_merge($head, $tail));
    }


    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction[] $xactions
     * @return array
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    protected function filterTransactions(ActiveRecordPHID $object, array $xactions)
    {

        $type_comment = PhabricatorTransactions::TYPE_COMMENT;

        $no_effect = array();
        $has_comment = false;
        $any_effect = false;
        foreach ($xactions as $key => $xaction) {
            if ($this->transactionHasEffect($object, $xaction)) {
                if ($xaction->getTransactionType() != $type_comment) {
                    $any_effect = true;
                }
            } else if ($xaction->getIgnoreOnNoEffect()) {
                unset($xactions[$key]);
            } else {
                $no_effect[$key] = $xaction;
            }
            if ($xaction->hasComment()) {
                $has_comment = true;
            }
        }

        if (!$no_effect) {
            return $xactions;
        }

        if (!$this->getContinueOnNoEffect() && !$this->getIsPreview()) {
            throw new PhabricatorApplicationTransactionNoEffectException(
                $no_effect,
                $any_effect,
                $has_comment);
        }

        if (!$any_effect && !$has_comment) {
            // If we only have empty comment transactions, just drop them all.
            return array();
        }

        foreach ($no_effect as $key => $xaction) {
            if ($xaction->hasComment()) {
                $xaction->setTransactionType($type_comment);
                $xaction->setOldValue(null);
                $xaction->setNewValue(null);
            } else {
                unset($xactions[$key]);
            }
        }

        return $xactions;
    }


    /**
     * Hook for validating transactions. This callback will be invoked for each
     * available transaction type, even if an edit does not apply any transactions
     * of that type. This allows you to raise exceptions when required fields are
     * missing, by detecting that the object has no field value and there is no
     * transaction which sets one.
     *
     * @param ActiveRecordPHID $object
     * @param ActiveRecord Object being edited.
     * @param array $xactions
     * @return array<PhabricatorApplicationTransactionValidationError> List of
     *   validation errors.
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilJSONParserException
     */
    protected function validateTransaction(
        ActiveRecordPHID $object,
        $type,
        array $xactions)
    {

        $errors = array();

        $xtype = $this->getModularTransactionType($type);
        if ($xtype) {
            $errors[] = $xtype->validateTransactions($object, $xactions);
        }

        switch ($type) {
            case PhabricatorTransactions::TYPE_VIEW_POLICY:
                $errors[] = $this->validatePolicyTransaction(
                    $object,
                    $xactions,
                    $type,
                    PhabricatorPolicyCapability::CAN_VIEW);
                break;
            case PhabricatorTransactions::TYPE_EDIT_POLICY:
                $errors[] = $this->validatePolicyTransaction(
                    $object,
                    $xactions,
                    $type,
                    PhabricatorPolicyCapability::CAN_EDIT);
                break;
            case PhabricatorTransactions::TYPE_SPACE:
                $errors[] = $this->validateSpaceTransactions(
                    $object,
                    $xactions,
                    $type);
                break;
            case PhabricatorTransactions::TYPE_SUBTYPE:
                $errors[] = $this->validateSubtypeTransactions(
                    $object,
                    $xactions,
                    $type);
                break;
            case PhabricatorTransactions::TYPE_CUSTOMFIELD:
                $groups = array();
                foreach ($xactions as $xaction) {
                    $groups[$xaction->getMetadataValue('customfield:key')][] = $xaction;
                }

                $field_list = PhabricatorCustomField::getObjectFields(
                    $object,
                    PhabricatorCustomField::ROLE_EDIT);
                $field_list->setViewer($this->getActor());

                $role_xactions = PhabricatorCustomField::ROLE_APPLICATIONTRANSACTIONS;
                $fields = $field_list->getFields();
                foreach ($fields as $field) {
                    if (!$field->shouldEnableForRole($role_xactions)) {
                        continue;
                    }
                    $errors[] = $field->validateApplicationTransactions(
                        $this,
                        $type,
                        ArrayHelper::getValue($groups, $field->getFieldKey(), array()));
                }
                break;
        }

        return OranginsUtil::array_mergev($errors);
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @param $transaction_type
     * @param $capability
     * @return array
     * @throws PhutilInvalidStateException
     * @throws Exception
     * @throws ReflectionException
     * @author 陈妙威
     */
    public function validatePolicyTransaction(
        ActiveRecordPHID $object,
        array $xactions,
        $transaction_type,
        $capability)
    {

        $actor = $this->requireActor();
        $errors = array();
        // Note $this->xactions is necessary; $xactions is $this->xactions of
        // $transaction_type
        $policy_object = $this->adjustObjectForPolicyChecks(
            $object,
            $this->xactions);

        // Make sure the user isn't editing away their ability to $capability this
        // object.
        foreach ($xactions as $xaction) {
            try {
                PhabricatorPolicyFilter::requireCapabilityWithForcedPolicy(
                    $actor,
                    $policy_object,
                    $capability,
                    $xaction->getNewValue());
            } catch (PhabricatorPolicyException $ex) {
                $errors[] = new PhabricatorApplicationTransactionValidationError(
                    $transaction_type,
                    Yii::t("app", 'Invalid'),
                    Yii::t("app",
                        'You can not select this {0} policy, because you would no longer ' .
                        'be able to {1} the object.',
                        [
                            $capability,
                            $capability
                        ]),
                    $xaction);
            }
        }

        if ($this->getIsNewObject()) {
            if (!$xactions) {
                $has_capability = PhabricatorPolicyFilter::hasCapability(
                    $actor,
                    $policy_object,
                    $capability);
                if (!$has_capability) {
                    $errors[] = new PhabricatorApplicationTransactionValidationError(
                        $transaction_type,
                        Yii::t("app", 'Invalid'),
                        Yii::t("app",
                            'The selected {0} policy excludes you. Choose a {1} policy ' .
                            'which allows you to {2} the object.',
                            [
                                $capability,
                                $capability,
                                $capability
                            ]));
                }
            }
        }

        return $errors;
    }


    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @param $transaction_type
     * @return array
     * @throws Exception
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    private function validateSpaceTransactions(
        ActiveRecordPHID $object,
        array $xactions,
        $transaction_type)
    {
        $errors = array();

        $actor = $this->getActor();

        $has_spaces = PhabricatorSpacesNamespaceQuery::getViewerSpacesExist($actor);
        $actor_spaces = PhabricatorSpacesNamespaceQuery::getViewerSpaces($actor);
        $active_spaces = PhabricatorSpacesNamespaceQuery::getViewerActiveSpaces(
            $actor);
        foreach ($xactions as $xaction) {
            $space_phid = $xaction->getNewValue();

            if ($space_phid === null) {
                if (!$has_spaces) {
                    // The install doesn't have any spaces, so this is fine.
                    continue;
                }

                // The install has some spaces, so every object needs to be put
                // in a valid space.
                $errors[] = new PhabricatorApplicationTransactionValidationError(
                    $transaction_type,
                    Yii::t("app", 'Invalid'),
                    Yii::t("app", 'You must choose a space for this object.'),
                    $xaction);
                continue;
            }

            // If the PHID isn't `null`, it needs to be a valid space that the
            // viewer can see.
            if (empty($actor_spaces[$space_phid])) {
                $errors[] = new PhabricatorApplicationTransactionValidationError(
                    $transaction_type,
                    Yii::t("app", 'Invalid'),
                    Yii::t("app",
                        'You can not shift this object in the selected space, because ' .
                        'the space does not exist or you do not have access to it.'),
                    $xaction);
            } else if (empty($active_spaces[$space_phid])) {

                // It's OK to edit objects in an archived space, so just move on if
                // we aren't adjusting the value.
                $old_space_phid = $this->getTransactionOldValue($object, $xaction);
                if ($space_phid == $old_space_phid) {
                    continue;
                }

                $errors[] = new PhabricatorApplicationTransactionValidationError(
                    $transaction_type,
                    Yii::t("app", 'Archived'),
                    Yii::t("app",
                        'You can not shift this object into the selected space, because ' .
                        'the space is archived. Objects can not be created inside (or ' .
                        'moved into) archived spaces.'),
                    $xaction);
            }
        }

        return $errors;
    }

    /**
     * @param ActiveRecordPHID|PhabricatorEditEngineSubtypeInterface $object
     * @param array $xactions
     * @param $transaction_type
     * @return array
     * @author 陈妙威
     */
    private function validateSubtypeTransactions(
        ActiveRecordPHID $object,
        array $xactions,
        $transaction_type)
    {
        $errors = array();

        $map = $object->newEditEngineSubtypeMap();
        $old = $object->getEditEngineSubtype();
        foreach ($xactions as $xaction) {
            $new = $xaction->getNewValue();

            if ($old == $new) {
                continue;
            }

            if (!isset($map[$new])) {
                $errors[] = new PhabricatorApplicationTransactionValidationError(
                    $transaction_type,
                    Yii::t("app", 'Invalid'),
                    Yii::t("app",
                        'The subtype "{0}" is not a valid subtype.',
                        [
                            $new
                        ]),
                    $xaction);
                continue;
            }
        }

        return $errors;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return PhabricatorPolicyInterface
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    protected function adjustObjectForPolicyChecks(
        ActiveRecordPHID $object,
        array $xactions)
    {

        /** @var PhabricatorPolicyInterface $copy */
        $copy = clone $object;
        foreach ($xactions as $xaction) {
            switch ($xaction->getTransactionType()) {
                case PhabricatorTransactions::TYPE_SUBSCRIBERS:
                    $clone_xaction = clone $xaction;
                    $clone_xaction->setOldValue(array_values($this->subscribers));
                    $clone_xaction->setNewValue(
                        $this->getPHIDTransactionNewValue(
                            $clone_xaction));

                    PhabricatorPolicyRule::passTransactionHintToRule(
                        $copy,
                        new PhabricatorSubscriptionsSubscribersPolicyRule(),
                        OranginsUtil::array_fuse($clone_xaction->getNewValue()));

                    break;
                case PhabricatorTransactions::TYPE_SPACE:
                    $space_phid = $this->getTransactionNewValue($object, $xaction);
                    $copy->setSpacePHID($space_phid);
                    break;
            }
        }

        return $copy;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return array
     * @author 陈妙威
     */
    protected function validateAllTransactions(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return array();
    }

    /**
     * @param array $attributeLabels
     * @author 陈妙威
     */
    public function initAttributeLabels(array $attributeLabels)
    {
        $this->attributeLabels = $attributeLabels;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function attributeLabels()
    {
        return $this->attributeLabels;
    }

    /**
     * Check for a missing text field.
     *
     * A text field is missing if the object has no value and there are no
     * transactions which set a value, or if the transactions remove the value.
     * This method is intended to make implementing @{method:validateTransaction}
     * more convenient:
     *
     *   $missing = $this->validateIsEmptyTextField(
     *     $object->getName(),
     *     $xactions);
     *
     * This will return `true` if the net effect of the object and transactions
     * is an empty field.
     *
     * @param object Current field value.
     * @param array $xactions
     * @return bool True if the field will be an empty text field after edits.
     * @throws PhutilJSONParserException
     */
    protected function validateIsEmptyTextField($field_value, array $xactions)
    {
        if (strlen($field_value) && empty($xactions)) {
            return false;
        }

        /** @var PhabricatorApplicationTransaction $wild */
        $wild = last($xactions);
        if ($xactions && strlen($wild->getNewValue())) {
            return false;
        }

        return true;
    }


    /* -(  Implicit CCs  )------------------------------------------------------- */


    /**
     * When a user interacts with an object, we might want to add them to CC.
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return array
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilMethodNotImplementedException
     */
    final public function applyImplicitCC(
        ActiveRecordPHID $object,
        array $xactions)
    {

        if (!($object instanceof PhabricatorSubscribableInterface)) {
            // If the object isn't subscribable, we can't CC them.
            return $xactions;
        }

        $actor_phid = $this->getActingAsPHID();

        $type_user = PhabricatorPeopleUserPHIDType::TYPECONST;
        if (PhabricatorPHID::phid_get_type($actor_phid) != $type_user) {
            // Transactions by application actors like Herald, Harbormaster and
            // Diffusion should not CC the applications.
            return $xactions;
        }

        if ($object->isAutomaticallySubscribed($actor_phid)) {
            // If they're auto-subscribed, don't CC them.
            return $xactions;
        }

        $should_cc = false;
        foreach ($xactions as $xaction) {
            if ($this->shouldImplyCC($object, $xaction)) {
                $should_cc = true;
                break;
            }
        }

        if (!$should_cc) {
            // Only some types of actions imply a CC (like adding a comment).
            return $xactions;
        }

        if ($object->getPHID()) {
            if (isset($this->subscribers[$actor_phid])) {
                // If the user is already subscribed, don't implicitly CC them.
                return $xactions;
            }

            $unsub = PhabricatorEdgeQuery::loadDestinationPHIDs(
                $object->getPHID(),
                PhabricatorObjectHasUnsubscriberEdgeType::EDGECONST);
            $unsub = OranginsUtil::array_fuse($unsub);
            if (isset($unsub[$actor_phid])) {
                // If the user has previously unsubscribed from this object explicitly,
                // don't implicitly CC them.
                return $xactions;
            }
        }

        $xaction = OranginsUtil::newv(get_class(OranginsUtil::head($xactions)), array());
        $xaction->setTransactionType(PhabricatorTransactions::TYPE_SUBSCRIBERS);
        $xaction->setNewValue(array('+' => array($actor_phid)));

        array_unshift($xactions, $xaction);

        return $xactions;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    protected function shouldImplyCC(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        return $xaction->isCommentTransaction();
    }


    /* -(  Sending Mail  )------------------------------------------------------- */


    /**
     * @task mail
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return bool
     */
    protected function shouldSendMail(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return false;
    }


    /**
     * @task mail
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return array
     * @throws Exception
     */
    private function buildMail(
        ActiveRecordPHID $object,
        array $xactions)
    {

        $email_to = $this->mailToPHIDs;
        $email_cc = $this->mailCCPHIDs;
        $email_cc = array_merge($email_cc, $this->heraldEmailPHIDs);

        $unexpandable = $this->mailUnexpandablePHIDs;
        if (!is_array($unexpandable)) {
            $unexpandable = array();
        }

        $messages = $this->buildMailWithRecipients(
            $object,
            $xactions,
            $email_to,
            $email_cc,
            $unexpandable);

        $this->runHeraldMailRules($messages);

        return $messages;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @param array $email_to
     * @param array $email_cc
     * @param array $unexpandable
     * @return PhabricatorMetaMTAMail[]
     * @throws Exception
     * @author 陈妙威
     */
    private function buildMailWithRecipients(
        ActiveRecordPHID $object,
        array $xactions,
        array $email_to,
        array $email_cc,
        array $unexpandable)
    {

        $targets = $this->buildReplyHandler($object)
            ->setUnexpandablePHIDs($unexpandable)
            ->getMailTargets($email_to, $email_cc);

        // Set this explicitly before we start swapping out the effective actor.
        $this->setActingAsPHID($this->getActingAsPHID());

        $messages = array();
        foreach ($targets as $target) {
            $original_actor = $this->getActor();

            $viewer = $target->getViewer();
            $this->setActor($viewer);
            $locale = PhabricatorEnv::beginScopedLocale($viewer->getTranslation());

            $caught = null;
            $mail = null;
            try {
                // Reload handles for the new viewer.
                $this->loadHandles($xactions);

                $mail = $this->buildMailForTarget($object, $xactions, $target);

                if ($mail) {
                    if ($this->mustEncrypt) {
                        $mail
                            ->setMustEncrypt(true)
                            ->setMustEncryptReasons($this->mustEncrypt);
                    }
                }
            } catch (Exception $ex) {
                $caught = $ex;
            }

            $this->setActor($original_actor);
            unset($locale);

            if ($caught) {
                throw $ex;
            }

            if ($mail) {
                $messages[] = $mail;
            }
        }

        return $messages;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return array
     * @author 陈妙威
     */
    protected function getTransactionsForMail(ActiveRecordPHID $object, array $xactions)
    {
        return $xactions;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @param PhabricatorMailTarget $target
     * @return PhabricatorMetaMTAMail
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    private function buildMailForTarget(
        ActiveRecordPHID $object,
        array $xactions,
        PhabricatorMailTarget $target)
    {

        // Check if any of the transactions are visible for this viewer. If we
        // don't have any visible transactions, don't send the mail.

        $any_visible = false;
        foreach ($xactions as $xaction) {
            if (!$xaction->shouldHideForMail($xactions)) {
                $any_visible = true;
                break;
            }
        }

        if (!$any_visible) {
            return null;
        }

        $mail_xactions = $this->getTransactionsForMail($object, $xactions);


        $mail = $this->buildMailTemplate($object);
        $body = $this->buildMailBody($object, $mail_xactions);

        $mail_tags = $this->getMailTags($object, $mail_xactions);
        $action = $this->getMailAction($object, $mail_xactions);
        $stamps = $this->generateMailStamps($object, $this->mailStamps);

        if (PhabricatorEnv::getEnvConfig('metamta.email-preferences')) {
            $this->addEmailPreferenceSectionToMailBody(
                $body,
                $object,
                $mail_xactions);
        }

        $muted_phids = $this->mailMutedPHIDs;
        if (!is_array($muted_phids)) {
            $muted_phids = array();
        }

        $mail
            ->setSensitiveContent(false)
            ->setFrom($this->getActingAsPHID())
            ->setSubjectPrefix($this->getMailSubjectPrefix())
            ->setVarySubjectPrefix('[' . $action . ']')
            ->setThreadID($this->getMailThreadID($object), $this->getIsNewObject())
            ->setRelatedPHID($object->getPHID())
            ->setExcludeMailRecipientPHIDs($this->getExcludeMailRecipientPHIDs())
            ->setMutedPHIDs($muted_phids)
            ->setForceHeraldMailRecipientPHIDs($this->heraldForcedEmailPHIDs)
            ->setMailTags($mail_tags)
            ->setIsBulk(true)
            ->setBody($body->render())
            ->setHTMLBody($body->renderHTML());

        foreach ($body->getAttachments() as $attachment) {
            $mail->addAttachment($attachment);
        }

        if ($this->heraldHeader) {
            $mail->addHeader('X-Herald-Rules', $this->heraldHeader);
        }

//        if ($object instanceof PhabricatorProjectInterface) {
//            $this->addMailProjectMetadata($object, $mail);
//        }

        if ($this->getParentMessageID()) {
            $mail->setParentMessageID($this->getParentMessageID());
        }

        // If we have stamps, attach the raw dictionary version (not the actual
        // objects) to the mail so that debugging tools can see what we used to
        // render the final list.
        if ($this->mailStamps) {
            $mail->setMailStampMetadata($this->mailStamps);
        }

        // If we have rendered stamps, attach them to the mail.
        if ($stamps) {
            $mail->setMailStamps($stamps);
        }

        return $target->willSendMail($mail);
    }

//    /**
//     * @param ActiveRecordPHID $object
//     * @param PhabricatorMetaMTAMail $template
//     * @throws Exception
//     * @throws PhutilInvalidStateException
//     * @throws \ReflectionException
//
//     * @author 陈妙威
//     */
//    private function addMailProjectMetadata(
//        ActiveRecordPHID $object,
//        PhabricatorMetaMTAMail $template)
//    {
//
//        $project_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
//            $object->getPHID(),
//            PhabricatorProjectObjectHasProjectEdgeType::EDGECONST);
//
//        if (!$project_phids) {
//            return;
//        }
//
//        // TODO: This viewer isn't quite right. It would be slightly better to use
//        // the mail recipient, but that's not very easy given the way rendering
//        // works today.
//
//        $handles = (new PhabricatorHandleQuery())
//            ->setViewer($this->requireActor())
//            ->withPHIDs($project_phids)
//            ->execute();
//
//        $project_tags = array();
//        foreach ($handles as $handle) {
//            if (!$handle->isComplete()) {
//                continue;
//            }
//            $project_tags[] = '<' . $handle->getObjectName() . '>';
//        }
//
//        if (!$project_tags) {
//            return;
//        }
//
//        $project_tags = implode(', ', $project_tags);
//        $template->addHeader('X-Phabricator-Projects', $project_tags);
//    }
//

    /**
     * @param ActiveRecordPHID $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getMailThreadID(ActiveRecordPHID $object)
    {
        return $object->getPHID();
    }


    /**
     * @task mail
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return object
     */
    protected function getStrongestAction(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return last(msort($xactions, 'getActionStrength'));
    }


    /**
     * @return PhabricatorFeedStoryPublisher
     * @param ActiveRecordPHID $object
     * @throws Exception
     */
    protected function buildReplyHandler(ActiveRecordPHID $object)
    {
        throw new Exception(Yii::t("app", 'Capability not supported.'));
    }

    /**
     * @task mail
     * @throws Exception
     */
    protected function getMailSubjectPrefix()
    {
        throw new Exception(Yii::t("app", 'Capability not supported.'));
    }


    /**
     * @task mail
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction[] $xactions
     * @return array|mixed[]
     */
    protected function getMailTags(
        ActiveRecordPHID $object,
        array $xactions)
    {
        $tags = array();

        foreach ($xactions as $xaction) {
            $tags[] = $xaction->getMailTags();
        }

        return OranginsUtil::array_mergev($tags);
    }

    /**
     * @task mail
     */
    public function getMailTagsMap()
    {
        // TODO: We should move shared mail tags, like "comment", here.
        return array();
    }


    /**
     * @task mail
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return
     */
    protected function getMailAction(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return $this->getStrongestAction($object, $xactions)->getActionName();
    }


    /**
     * @task mail
     * @param ActiveRecordPHID $object
     * @return PhabricatorMetaMTAMail
     * @throws Exception
     */
    protected function buildMailTemplate(ActiveRecordPHID $object)
    {
        throw new Exception(Yii::t("app", 'Capability not supported.'));
    }


    /**
     * @param ActiveRecordPHID $object
     * @return string[]
     * @throws Exception
     * @task mail
     */
    protected function getMailTo(ActiveRecordPHID $object)
    {
        throw new Exception(Yii::t("app", 'Capability not supported.'));
    }


    /**
     * @param ActiveRecordPHID $object
     * @return array
     * @author 陈妙威
     */
    protected function newMailUnexpandablePHIDs(ActiveRecordPHID $object)
    {
        return array();
    }


    /**
     * @task mail
     * @param ActiveRecordPHID $object
     * @return array|mixed[]
     * @throws PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     */
    protected function getMailCC(ActiveRecordPHID $object)
    {
        $phids = array();
        $has_support = false;

        if ($object instanceof PhabricatorSubscribableInterface) {
            $phid = $object->getPHID();
            $phids[] = PhabricatorSubscribersQuery::loadSubscribersForPHID($phid);
            $has_support = true;
        }

//        if ($object instanceof PhabricatorProjectInterface) {
//            $project_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
//                $object->getPHID(),
//                PhabricatorProjectObjectHasProjectEdgeType::EDGECONST);
//
//            if ($project_phids) {
//                $projects = (new PhabricatorProjectQuery())
//                    ->setViewer(PhabricatorUser::getOmnipotentUser())
//                    ->withPHIDs($project_phids)
//                    ->needWatchers(true)
//                    ->execute();
//
//                $watcher_phids = array();
//                foreach ($projects as $project) {
//                    foreach ($project->getAllAncestorWatcherPHIDs() as $phid) {
//                        $watcher_phids[$phid] = $phid;
//                    }
//                }
//
//                if ($watcher_phids) {
//                    // We need to do a visibility check for all the watchers, as
//                    // watching a project is not a guarantee that you can see objects
//                    // associated with it.
//                    $users = PhabricatorUser::find()
//                        ->setViewer($this->requireActor())
//                        ->withPHIDs($watcher_phids)
//                        ->execute();
//
//                    $watchers = array();
//                    foreach ($users as $user) {
//                        $can_see = PhabricatorPolicyFilter::hasCapability(
//                            $user,
//                            $object,
//                            PhabricatorPolicyCapability::CAN_VIEW);
//                        if ($can_see) {
//                            $watchers[] = $user->getPHID();
//                        }
//                    }
//                    $phids[] = $watchers;
//                }
//            }
//
//            $has_support = true;
//        }

        if (!$has_support) {
            throw new Exception(
                Yii::t("app", 'The object being edited does not implement any standard ' .
                    'interfaces (like PhabricatorSubscribableInterface) which allow ' .
                    'CCs to be generated automatically. Override the "getMailCC()" ' .
                    'method and generate CCs explicitly.'));
        }

        return OranginsUtil::array_mergev($phids);
    }


    /**
     * @task mail
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return PhabricatorMetaMTAMailBody
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    protected function buildMailBody(
        ActiveRecordPHID $object,
        array $xactions)
    {

        $body = (new PhabricatorMetaMTAMailBody())
            ->setViewer($this->requireActor())
            ->setContextObject($object);

        $this->addHeadersAndCommentsToMailBody($body, $xactions);
        $this->addCustomFieldsToMailBody($body, $object, $xactions);

        return $body;
    }


    /**
     * @task mail
     * @param PhabricatorMetaMTAMailBody $body
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @throws Exception
     */
    protected function addEmailPreferenceSectionToMailBody(
        PhabricatorMetaMTAMailBody $body,
        ActiveRecordPHID $object,
        array $xactions)
    {

        $href = PhabricatorEnv::getProductionURI(
            '/settings/panel/emailpreferences/');
        $body->addLinkSection(Yii::t("app", 'EMAIL PREFERENCES'), $href);
    }


    /**
     * @task mail
     * @param PhabricatorMetaMTAMailBody $body
     * @param array $xactions
     * @param null $object_label
     * @param null $object_href
     * @throws Exception
     */
    protected function addHeadersAndCommentsToMailBody(
        PhabricatorMetaMTAMailBody $body,
        array $xactions,
        $object_label = null,
        $object_href = null)
    {

        // First, remove transactions which shouldn't be rendered in mail.
        foreach ($xactions as $key => $xaction) {
            if ($xaction->shouldHideForMail($xactions)) {
                unset($xactions[$key]);
            }
        }

        $headers = array();
        $headers_html = array();
        $comments = array();
        $details = array();

        $seen_comment = false;
        foreach ($xactions as $xaction) {

            // Most mail has zero or one comments. In these cases, we render the
            // "alice added a comment." transaction in the header, like a normal
            // transaction.

            // Some mail, like Differential undraft mail or "!history" mail, may
            // have two or more comments. In these cases, we'll put the first
            // "alice added a comment." transaction in the header normally, but
            // move the other transactions down so they provide context above the
            // actual comment.

            $comment = $xaction->getBodyForMail();
            if ($comment !== null) {
                $is_comment = true;
                $comments[] = array(
                    'xaction' => $xaction,
                    'comment' => $comment,
                    'initial' => !$seen_comment,
                );
            } else {
                $is_comment = false;
            }

            if (!$is_comment || !$seen_comment) {
                $header = $xaction->getTitleForMail();
                if ($header !== null) {
                    $headers[] = $header;
                }

                $header_html = $xaction->getTitleForHTMLMail();
                if ($header_html !== null) {
                    $headers_html[] = $header_html;
                }
            }

            if ($xaction->hasChangeDetailsForMail()) {
                $details[] = $xaction;
            }

            if ($is_comment) {
                $seen_comment = true;
            }
        }

        $headers_text = implode("\n", $headers);
        $body->addRawPlaintextSection($headers_text);

        $headers_html = phutil_implode_html(JavelinHtml::phutil_tag('br'), $headers_html);

        $header_button = null;
        if ($object_label !== null) {
            $button_style = array(
                'text-decoration: none;',
                'padding: 4px 8px;',
                'margin: 0 8px 8px;',
                'float: right;',
                'color: #464C5C;',
                'font-weight: bold;',
                'border-radius: 3px;',
                'background-color: #F7F7F9;',
                'background-image: linear-gradient(to bottom,#fff,#f1f0f1);',
                'display: inline-block;',
                'border: 1px solid rgba(71,87,120,.2);',
            );

            $header_button = JavelinHtml::phutil_tag(
                'a',
                array(
                    'style' => implode(' ', $button_style),
                    'href' => $object_href,
                ),
                $object_label);
        }

        $xactions_style = array();

        $header_action = JavelinHtml::phutil_tag(
            'td',
            array(),
            $header_button);

        $header_action = JavelinHtml::phutil_tag(
            'td',
            array(
                'style' => implode(' ', $xactions_style),
            ),
            array(
                $headers_html,
                // Add an extra newline to prevent the "View Object" button from
                // running into the transaction text in Mail.app text snippet
                // previews.
                "\n",
            ));

        $headers_html = JavelinHtml::phutil_tag(
            'table',
            array(),
            JavelinHtml::phutil_tag('tr', array(), array($header_action, $header_button)));

        $body->addRawHTMLSection($headers_html);

        foreach ($comments as $spec) {
            $xaction = $spec['xaction'];
            $comment = $spec['comment'];
            $is_initial = $spec['initial'];

            // If this is not the first comment in the mail, add the header showing
            // who wrote the comment immediately above the comment.
            if (!$is_initial) {
                $header = $xaction->getTitleForMail();
                if ($header !== null) {
                    $body->addRawPlaintextSection($header);
                }

                $header_html = $xaction->getTitleForHTMLMail();
                if ($header_html !== null) {
                    $body->addRawHTMLSection($header_html);
                }
            }

            $body->addRemarkupSection(null, $comment);
        }

        foreach ($details as $xaction) {
            $details = $xaction->renderChangeDetailsForMail($body->getViewer());
            if ($details !== null) {
                $label = $this->getMailDiffSectionHeader($xaction);
                $body->addHTMLSection($label, $details);
            }
        }

    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return mixed
     * @author 陈妙威
     */
    private function getMailDiffSectionHeader($xaction)
    {
        $type = $xaction->getTransactionType();

        $xtype = $this->getModularTransactionType($type);
        if ($xtype) {
            return $xtype->getMailDiffSectionHeader();
        }

        return Yii::t("app", 'EDIT DETAILS');
    }

    /**
     * @task mail
     * @param PhabricatorMetaMTAMailBody $body
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @throws Exception
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     */
    protected function addCustomFieldsToMailBody(
        PhabricatorMetaMTAMailBody $body,
        ActiveRecordPHID $object,
        array $xactions)
    {

        if ($object instanceof PhabricatorCustomFieldInterface) {
            $field_list = PhabricatorCustomField::getObjectFields(
                $object,
                PhabricatorCustomField::ROLE_TRANSACTIONMAIL);
            $field_list->setViewer($this->getActor());
            $field_list->readFieldsFromStorage($object);

            foreach ($field_list->getFields() as $field) {
                $field->updateTransactionMailBody(
                    $body,
                    $this,
                    $xactions);
            }
        }
    }


    /**
     * @task mail
     * @param array $messages
     */
    private function runHeraldMailRules(array $messages)
    {
        foreach ($messages as $message) {
            $engine = new HeraldEngine();
            $adapter = (new PhabricatorMailOutboundMailHeraldAdapter())
                ->setObject($message);

            $rules = $engine->loadRulesForAdapter($adapter);
            $effects = $engine->applyRules($rules, $adapter);
            $engine->applyEffects($effects, $adapter, $rules);
        }
    }


    /* -(  Publishing Feed Stories  )-------------------------------------------- */


    /**
     * @task feed
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return bool
     */
    protected function shouldPublishFeedStory(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return false;
    }


    /**
     * @task feed
     */
    protected function getFeedStoryType()
    {
        return 'PhabricatorApplicationTransactionFeedStory';
    }


    /**
     * @task feed
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return array
     */
    protected function getFeedRelatedPHIDs(
        ActiveRecordPHID $object,
        array $xactions)
    {

        $phids = array(
            $object->getPHID(),
            $this->getActingAsPHID(),
        );

//        if ($object instanceof PhabricatorProjectInterface) {
//            $project_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
//                $object->getPHID(),
//                PhabricatorProjectObjectHasProjectEdgeType::EDGECONST);
//            foreach ($project_phids as $project_phid) {
//                $phids[] = $project_phid;
//            }
//        }

        return $phids;
    }


    /**
     * @task feed
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return array
     * @throws Exception
     */
    protected function getFeedNotifyPHIDs(ActiveRecordPHID $object, array $xactions)
    {

        return array_unique(array_merge(
            $this->getMailTo($object),
            $this->getMailCC($object)));
    }


    /**
     * @task feed
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return array
     */
    protected function getFeedStoryData(ActiveRecordPHID $object, array $xactions)
    {

        $xactions = OranginsUtil::msort($xactions, 'getActionStrength');
        $xactions = array_reverse($xactions);

        return array(
            'objectPHID' => $object->getPHID(),
            'transactionPHIDs' => OranginsUtil::mpull($xactions, 'getPHID'),
        );
    }


    /**
     * @task feed
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @param array $mailed_phids
     * @throws AphrontQueryException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws \yii\base\Exception
     * @throws UnknownPropertyException
     * @throws \yii\db\Exception
     * @throws IntegrityException
     */
    protected function publishFeedStory(ActiveRecordPHID $object, array $xactions, array $mailed_phids)
    {

        $xactions = OranginsUtil::mfilter($xactions, 'shouldHideForFeed', true);

        if (!$xactions) {
            return;
        }

        $related_phids = $this->feedRelatedPHIDs;
        $subscribed_phids = $this->feedNotifyPHIDs;

        // Remove muted users from the subscription list so they don't get
        // notifications, either.
        $muted_phids = $this->mailMutedPHIDs;
        if (!is_array($muted_phids)) {
            $muted_phids = array();
        }
        $subscribed_phids = OranginsUtil::array_fuse($subscribed_phids);
        foreach ($muted_phids as $muted_phid) {
            unset($subscribed_phids[$muted_phid]);
        }
        $subscribed_phids = array_values($subscribed_phids);

        $story_type = $this->getFeedStoryType();
        $story_data = $this->getFeedStoryData($object, $xactions);

        $unexpandable_phids = $this->mailUnexpandablePHIDs;
        if (!is_array($unexpandable_phids)) {
            $unexpandable_phids = array();
        }

        (new PhabricatorFeedStoryPublisher())
            ->setStoryType($story_type)
            ->setStoryData($story_data)
            ->setStoryTime(time())
            ->setStoryAuthorPHID($this->getActingAsPHID())
            ->setRelatedPHIDs($related_phids)
            ->setPrimaryObjectPHID($object->getPHID())
            ->setSubscribedPHIDs($subscribed_phids)
            ->setUnexpandablePHIDs($unexpandable_phids)
            ->setMailRecipientPHIDs($mailed_phids)
            ->setMailTags($this->getMailTags($object, $xactions))
            ->publish();
    }


    /* -(  Search Index  )------------------------------------------------------- */


    /**
     * @task search
     */
    protected function supportsSearch()
    {
        return false;
    }


    /* -(  Herald Integration )-------------------------------------------------- */


    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return bool
     * @author 陈妙威
     */
    protected function shouldApplyHeraldRules(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return false;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @throws Exception
     * @author 陈妙威
     */
    protected function buildHeraldAdapter(
        ActiveRecordPHID $object,
        array $xactions)
    {
        throw new Exception(Yii::t("app", 'No herald adapter specified.'));
    }

    /**
     * @param HeraldAdapter $adapter
     * @return $this
     * @author 陈妙威
     */
    private function setHeraldAdapter(HeraldAdapter $adapter)
    {
        $this->heraldAdapter = $adapter;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getHeraldAdapter()
    {
        return $this->heraldAdapter;
    }

    /**
     * @param HeraldTranscript $transcript
     * @return $this
     * @author 陈妙威
     */
    private function setHeraldTranscript(HeraldTranscript $transcript)
    {
        $this->heraldTranscript = $transcript;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getHeraldTranscript()
    {
        return $this->heraldTranscript;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    private function applyHeraldRules(
        ActiveRecordPHID $object,
        array $xactions)
    {

        $adapter = $this->buildHeraldAdapter($object, $xactions)
            ->setContentSource($this->getContentSource())
            ->setIsNewObject($this->getIsNewObject())
            ->setActingAsPHID($this->getActingAsPHID())
            ->setAppliedTransactions($xactions);

        if ($this->getApplicationEmail()) {
            $adapter->setApplicationEmail($this->getApplicationEmail());
        }

        // If this editor is operating in silent mode, tell Herald that we aren't
        // going to send any mail. This allows it to skip "the first time this
        // rule matches, send me an email" rules which would otherwise match even
        // though we aren't going to send any mail.
        if ($this->getIsSilent()) {
            $adapter->setForbiddenAction(
                HeraldMailableState::STATECONST,
                HeraldCoreStateReasons::REASON_SILENT);
        }

        $xscript = HeraldEngine::loadAndApplyRules($adapter);

        $this->setHeraldAdapter($adapter);
        $this->setHeraldTranscript($xscript);

        if ($adapter instanceof HarbormasterBuildableAdapterInterface) {
            $buildable_phid = $adapter->getHarbormasterBuildablePHID();

            HarbormasterBuildable::applyBuildPlans(
                $buildable_phid,
                $adapter->getHarbormasterContainerPHID(),
                $adapter->getQueuedHarbormasterBuildRequests());

            // Whether we queued any builds or not, any automatic buildable for this
            // object is now done preparing builds and can transition into a
            // completed status.
            $buildables = (new HarbormasterBuildableQuery())
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withManualBuildables(false)
                ->withBuildablePHIDs(array($buildable_phid))
                ->execute();
            foreach ($buildables as $buildable) {
                // If this buildable has already moved beyond preparation, we don't
                // need to nudge it again.
                if (!$buildable->isPreparing()) {
                    continue;
                }
                $buildable->sendMessage(
                    $this->getActor(),
                    HarbormasterMessageType::BUILDABLE_BUILD,
                    true);
            }
        }

        $this->mustEncrypt = $adapter->getMustEncryptReasons();

        return array_merge(
            $this->didApplyHeraldRules($object, $adapter, $xscript),
            $adapter->getQueuedTransactions());
    }

    /**
     * @param ActiveRecordPHID $object
     * @param HeraldAdapter $adapter
     * @param HeraldTranscript $transcript
     * @return array
     * @author 陈妙威
     */
    protected function didApplyHeraldRules(
        ActiveRecordPHID $object,
        HeraldAdapter $adapter,
        HeraldTranscript $transcript)
    {
        return array();
    }


    /* -(  Custom Fields  )------------------------------------------------------ */


    /**
     * @task customfield
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return
     * @throws Exception
     */
    private function getCustomFieldForTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        $field_key = $xaction->getMetadataValue('customfield:key');
        if (!$field_key) {
            throw new Exception(
                Yii::t("app",
                    "Custom field transaction has no '{0}'!",
                    [
                        'customfield:key'
                    ]));
        }

        $field = PhabricatorCustomField::getObjectField(
            $object,
            PhabricatorCustomField::ROLE_APPLICATIONTRANSACTIONS,
            $field_key);

        if (!$field) {
            throw new Exception(
                Yii::t("app",
                    "Custom field transaction has invalid '{0}'; field '{1}' " .
                    "is disabled or does not exist.",
                    [
                        'customfield:key',
                        $field_key
                    ]));
        }

        if (!$field->shouldAppearInApplicationTransactions()) {
            throw new Exception(
                Yii::t("app",
                    "Custom field transaction '{0}' does not implement " .
                    "integration for {1}.",
                    [
                        $field_key,
                        'ApplicationTransactions'
                    ]));
        }

        $field->setViewer($this->getActor());

        return $field;
    }


    /* -(  Files  )-------------------------------------------------------------- */


    /**
     * Extract the PHIDs of any files which these transactions attach.
     *
     * @task files
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws InvalidConfigException
     */
    private function extractFilePHIDs(ActiveRecordPHID $object, $xactions)
    {

//        $changes = $this->getRemarkupChanges($xactions);
//        $blocks = OranginsUtil::mpull($changes, 'getNewValue');

        $phids = array();
//        if ($blocks) {
//            $phids[] = PhabricatorMarkupEngine::extractFilePHIDsFromEmbeddedFiles(
//                $this->getActor(),
//                $blocks);
//        }

        foreach ($xactions as $xaction) {
            $type = $xaction->getTransactionType();

            $xtype = $this->getModularTransactionType($type);
            if ($xtype) {
                $phids[] = $xtype->extractFilePHIDs($object, $xaction->getNewValue());
            } else {
                $phids[] = $this->extractFilePHIDsFromCustomTransaction(
                    $object,
                    $xaction);
            }
        }

        $phids = array_unique(array_filter(OranginsUtil::array_mergev($phids)));
        if (!$phids) {
            return array();
        }

        // Only let a user attach files they can actually see, since this would
        // otherwise let you access any file by attaching it to an object you have
        // view permission on.

        $files = PhabricatorFile::find()
            ->setViewer($this->getActor())
            ->withPHIDs($phids)
            ->execute();

        return OranginsUtil::mpull($files, 'getPHID');
    }

    /**
     * @task files
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return array
     */
    protected function extractFilePHIDsFromCustomTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {
        return array();
    }


    /**
     * @task files
     * @param ActiveRecordPHID $object
     * @param array $file_phids
     * @throws Exception
     */
    private function attachFiles(
        ActiveRecordPHID $object,
        array $file_phids)
    {

        if (!$file_phids) {
            return;
        }

        $editor = new PhabricatorEdgeEditor();

        $src = $object->getPHID();
        $type = PhabricatorObjectHasFileEdgeType::EDGECONST;
        foreach ($file_phids as $dst) {
            $editor->addEdge($src, $type, $dst);
        }

        $editor->save();
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @param $inverse_type
     * @throws AphrontObjectMissingQueryException
     * @throws AphrontQueryException
     * @throws IntegrityException
     * @throws InvalidConfigException
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionValidationException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws Throwable
     * @author 陈妙威
     */
    private function applyInverseEdgeTransactions(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction,
        $inverse_type)
    {

        $old = $xaction->getOldValue();
        $new = $xaction->getNewValue();

        $add = array_keys(array_diff_key($new, $old));
        $rem = array_keys(array_diff_key($old, $new));

        $add = OranginsUtil::array_fuse($add);
        $rem = OranginsUtil::array_fuse($rem);
        $all = $add + $rem;

        $nodes = (new PhabricatorObjectQuery())
            ->setViewer($this->requireActor())
            ->withPHIDs($all)
            ->execute();

        foreach ($nodes as $node) {
            if (!($node instanceof PhabricatorApplicationTransactionInterface)) {
                continue;
            }

            if ($node instanceof PhabricatorUser) {
                // TODO: At least for now, don't record inverse edge transactions
                // for users (for example, "alincoln joined project X"): Feed fills
                // this role instead.
                continue;
            }

            $editor = $node->getApplicationTransactionEditor();
            $template = $node->getApplicationTransactionTemplate();
            $target = $node->getApplicationTransactionObject();

            if (isset($add[$node->getPHID()])) {
                $edge_edit_type = '+';
            } else {
                $edge_edit_type = '-';
            }

            $template
                ->setTransactionType($xaction->getTransactionType())
                ->setMetadataValue('edge:type', $inverse_type)
                ->setNewValue(
                    array(
                        $edge_edit_type => array($object->getPHID() => $object->getPHID()),
                    ));

            $editor
                ->setContinueOnNoEffect(true)
                ->setContinueOnMissingFields(true)
                ->setParentMessageID($this->getParentMessageID())
                ->setIsInverseEdgeEditor(true)
                ->setIsSilent($this->getIsSilent())
                ->setActor($this->requireActor())
                ->setActingAsPHID($this->getActingAsPHID())
                ->setContentSource($this->getContentSource());

            $editor->applyTransactions($target, array($template));
        }
    }


    /* -(  Workers  )------------------------------------------------------------ */


    /**
     * Load any object state which is required to publish transactions.
     *
     * This hook is invoked in the main process before we compute data related
     * to publishing transactions (like email "To" and "CC" lists), and again in
     * the worker before publishing occurs.
     *
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return object Publishable object.
     * @task workers
     */
    protected function willPublish(ActiveRecordPHID $object, array $xactions)
    {
        return $object;
    }


    /**
     * Convert the editor state to a serializable dictionary which can be passed
     * to a worker.
     *
     * This data will be loaded with @{method:loadWorkerState} in the worker.
     *
     * @return array<string, wild> Serializable editor state.
     * @task workers
     * @throws Exception
     */
    final private function getWorkerState()
    {
        $state = array();
        foreach ($this->getAutomaticStateProperties() as $property) {
            $state[$property] = $this->$property;
        }

        $custom_state = $this->getCustomWorkerState();
        $custom_encoding = $this->getCustomWorkerStateEncoding();

        $state += array(
            'excludeMailRecipientPHIDs' => $this->getExcludeMailRecipientPHIDs(),
            'custom' => $this->encodeStateForStorage($custom_state, $custom_encoding),
            'custom.encoding' => $custom_encoding,
        );

        return $state;
    }


    /**
     * Hook; return custom properties which need to be passed to workers.
     *
     * @return array<string, wild> Custom properties.
     * @task workers
     */
    protected function getCustomWorkerState()
    {
        return array();
    }


    /**
     * Hook; return storage encoding for custom properties which need to be
     * passed to workers.
     *
     * This primarily allows binary data to be passed to workers and survive
     * JSON encoding.
     *
     * @return array<string, string> Property encodings.
     * @task workers
     */
    protected function getCustomWorkerStateEncoding()
    {
        return array();
    }


    /**
     * Load editor state using a dictionary emitted by @{method:getWorkerState}.
     *
     * This method is used to load state when running worker operations.
     *
     * @param array $state Editor state, from @{method:getWorkerState}.
     * @return static
     * @task workers
     * @throws Exception
     */
    final public function loadWorkerState(array $state)
    {
        foreach ($this->getAutomaticStateProperties() as $property) {
            $this->$property = ArrayHelper::getValue($state, $property);
        }

        $exclude = ArrayHelper::getValue($state, 'excludeMailRecipientPHIDs', array());
        $this->setExcludeMailRecipientPHIDs($exclude);

        $custom_state = ArrayHelper::getValue($state, 'custom', array());
        $custom_encodings = ArrayHelper::getValue($state, 'custom.encoding', array());
        $custom = $this->decodeStateFromStorage($custom_state, $custom_encodings);

        $this->loadCustomWorkerState($custom);

        return $this;
    }


    /**
     * Hook; set custom properties on the editor from data emitted by
     * @{method:getCustomWorkerState}.
     *
     * @param array $state Custom state,
     *   from @{method:getCustomWorkerState}.
     * @return static
     * @task workers
     */
    protected function loadCustomWorkerState(array $state)
    {
        return $this;
    }


    /**
     * Get a list of object properties which should be automatically sent to
     * workers in the state data.
     *
     * These properties will be automatically stored and loaded by the editor in
     * the worker.
     *
     * @return array<string> List of properties.
     * @task workers
     */
    private function getAutomaticStateProperties()
    {
        return array(
            'parentMessageID',
            'isNewObject',
            'heraldEmailPHIDs',
            'heraldForcedEmailPHIDs',
            'heraldHeader',
            'mailToPHIDs',
            'mailCCPHIDs',
            'feedNotifyPHIDs',
            'feedRelatedPHIDs',
            'feedShouldPublish',
            'mailShouldSend',
            'mustEncrypt',
            'mailStamps',
            'mailUnexpandablePHIDs',
            'mailMutedPHIDs',
            'webhookMap',
            'silent',
            'sendHistory',
        );
    }

    /**
     * Apply encodings prior to storage.
     *
     * See @{method:getCustomWorkerStateEncoding}.
     *
     * @param array $state Map of values to encode.
     * @param array $encodings Map of encodings to apply.
     * @return array Map of encoded values.
     * @task workers
     * @throws Exception
     */
    final private function encodeStateForStorage(
        array $state,
        array $encodings)
    {

        foreach ($state as $key => $value) {
            $encoding = ArrayHelper::getValue($encodings, $key);
            switch ($encoding) {
                case self::STORAGE_ENCODING_BINARY:
                    // The mechanics of this encoding (serialize + base64) are a little
                    // awkward, but it allows us encode arrays and still be JSON-safe
                    // with binary data.

                    $value = @serialize($value);
                    if ($value === false) {
                        throw new Exception(
                            Yii::t("app",
                                'Failed to serialize() value for key "{0}".',
                                [
                                    $key
                                ]));
                    }

                    $value = base64_encode($value);
                    if ($value === false) {
                        throw new Exception(
                            Yii::t("app",
                                'Failed to base64 encode value for key "{0}".',
                                [
                                    $key
                                ]));
                    }
                    break;
            }
            $state[$key] = $value;
        }

        return $state;
    }


    /**
     * Undo storage encoding applied when storing state.
     *
     * See @{method:getCustomWorkerStateEncoding}.
     *
     * @param array $state Map of encoded values.
     * @param array $encodings  Map of encodings.
     * @return array Map of decoded values.
     * @task workers
     * @throws Exception
     */
    final private function decodeStateFromStorage(
        array $state,
        array $encodings)
    {

        foreach ($state as $key => $value) {
            $encoding = ArrayHelper::getValue($encodings, $key);
            switch ($encoding) {
                case self::STORAGE_ENCODING_BINARY:
                    $value = base64_decode($value);
                    if ($value === false) {
                        throw new Exception(
                            Yii::t("app",
                                'Failed to base64_decode() value for key "{0}".',
                                [
                                    $key
                                ]));
                    }

                    $value = unserialize($value);
                    break;
            }
            $state[$key] = $value;
        }

        return $state;
    }


//    /**
//     * Remove conflicts from a list of projects.
//     *
//     * Objects aren't allowed to be tagged with multiple milestones in the same
//     * group, nor projects such that one tag is the ancestor of any other tag.
//     * If the list of PHIDs include mutually exclusive projects, remove the
//     * conflicting projects.
//     *
//     * @param array<phid> List of project PHIDs.
//     * @return array<phid> List with conflicts removed.
//     */
//    private function applyProjectConflictRules(array $phids)
//    {
//        if (!$phids) {
//            return array();
//        }
//
//        // Overall, the last project in the list wins in cases of conflict (so when
//        // you add something, the thing you just added sticks and removes older
//        // values).
//
//        // Beyond that, there are two basic cases:
//
//        // Milestones: An object can't be in "A > Sprint 3" and "A > Sprint 4".
//        // If multiple projects are milestones of the same parent, we only keep the
//        // last one.
//
//        // Ancestor: You can't be in "A" and "A > B". If "A > B" comes later
//        // in the list, we remove "A" and keep "A > B". If "A" comes later, we
//        // remove "A > B" and keep "A".
//
//        // Note that it's OK to be in "A > B" and "A > C". There's only a conflict
//        // if one project is an ancestor of another. It's OK to have something
//        // tagged with multiple projects which share a common ancestor, so long as
//        // they are not mutual ancestors.
//
//        $viewer = PhabricatorUser::getOmnipotentUser();
//
//        $projects = (new PhabricatorProjectQuery())
//            ->setViewer($viewer)
//            ->withPHIDs(array_keys($phids))
//            ->execute();
//        $projects = OranginsUtil::mpull($projects, null, 'getPHID');
//
//        // We're going to build a map from each project with milestones to the last
//        // milestone in the list. This last milestone is the milestone we'll keep.
//        $milestone_map = array();
//
//        // We're going to build a set of the projects which have no descendants
//        // later in the list. This allows us to apply both ancestor rules.
//        $ancestor_map = array();
//
//        foreach ($phids as $phid => $ignored) {
//            $project = ArrayHelper::getValue($projects, $phid);
//            if (!$project) {
//                continue;
//            }
//
//            // This is the last milestone we've seen, so set it as the selection for
//            // the project's parent. This might be setting a new value or overwriting
//            // an earlier value.
//            if ($project->isMilestone()) {
//                $parent_phid = $project->getParentProjectPHID();
//                $milestone_map[$parent_phid] = $phid;
//            }
//
//            // Since this is the last item in the list we've examined so far, add it
//            // to the set of projects with no later descendants.
//            $ancestor_map[$phid] = $phid;
//
//            // Remove any ancestors from the set, since this is a later descendant.
//            foreach ($project->getAncestorProjects() as $ancestor) {
//                $ancestor_phid = $ancestor->getPHID();
//                unset($ancestor_map[$ancestor_phid]);
//            }
//        }
//
//        // Now that we've built the maps, we can throw away all the projects which
//        // have conflicts.
//        foreach ($phids as $phid => $ignored) {
//            $project = ArrayHelper::getValue($projects, $phid);
//
//            if (!$project) {
//                // If a PHID is invalid, we just leave it as-is. We could clean it up,
//                // but leaving it untouched is less likely to cause collateral damage.
//                continue;
//            }
//
//            // If this was a milestone, check if it was the last milestone from its
//            // group in the list. If not, remove it from the list.
//            if ($project->isMilestone()) {
//                $parent_phid = $project->getParentProjectPHID();
//                if ($milestone_map[$parent_phid] !== $phid) {
//                    unset($phids[$phid]);
//                    continue;
//                }
//            }
//
//            // If a later project in the list is a subproject of this one, it will
//            // have removed ancestors from the map. If this project does not point
//            // at itself in the ancestor map, it should be discarded in favor of a
//            // subproject that comes later.
//            if (ArrayHelper::getValue($ancestor_map, $phid) !== $phid) {
//                unset($phids[$phid]);
//                continue;
//            }
//
//            // If a later project in the list is an ancestor of this one, it will
//            // have added itself to the map. If any ancestor of this project points
//            // at itself in the map, this project should be discarded in favor of
//            // that later ancestor.
//            foreach ($project->getAncestorProjects() as $ancestor) {
//                $ancestor_phid = $ancestor->getPHID();
//                if (isset($ancestor_map[$ancestor_phid])) {
//                    unset($phids[$phid]);
//                    continue 2;
//                }
//            }
//        }
//
//        return $phids;
//    }

    /**
     * When the view policy for an object is changed, scramble the secret keys
     * for attached files to invalidate existing URIs.
     * @param $object
     * @throws AphrontQueryException
     * @throws IntegrityException
     */
    private function scrambleFileSecrets($object)
    {
        // If this is a newly created object, we don't need to scramble anything
        // since it couldn't have been previously published.
        if ($this->getIsNewObject()) {
            return;
        }

        // If the object is a file itself, scramble it.
        if ($object instanceof PhabricatorFile) {
            if ($this->shouldScramblePolicy($object->getAttribute('view_policy'))) {
                $object->scrambleSecret();
                $object->save();
            }
        }

        return;
//        $phid = $object->getPHID();
//        $attached_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
//            $phid,
//            PhabricatorObjectHasFileEdgeType::EDGECONST);
//        if (!$attached_phids) {
//            return;
//        }
//
//        $omnipotent_viewer = PhabricatorUser::getOmnipotentUser();
//
//        /** @var FileEntities[] $files */
//        $files = FileEntities::find()
//            ->setViewer($omnipotent_viewer)
//            ->withPHIDs($attached_phids)
//            ->execute();
//        foreach ($files as $file) {
//            $view_policy = $file->getAttribute('view_policy');
//            if ($this->shouldScramblePolicy($view_policy)) {
//                $file->scrambleSecret();
//                $file->save();
//            }
//        }
    }


    /**
     * Check if a policy is strong enough to justify scrambling. Objects which
     * are set to very open policies don't need to scramble their files, and
     * files with very open policies don't need to be scrambled when associated
     * objects change.
     * @param $policy
     * @return bool
     */
    private function shouldScramblePolicy($policy)
    {
        switch ($policy) {
            case PhabricatorPolicies::POLICY_PUBLIC:
            case PhabricatorPolicies::POLICY_USER:
                return false;
        }

        return true;
    }

    /**
     * @param $object
     * @param $const
     * @param $old
     * @param $new
     * @return array
     * @author 陈妙威
     */
    private function updateWorkboardColumns($object, $const, $old, $new)
    {
        // If an object is removed from a project, remove it from any proxy
        // columns for that project. This allows a task which is moved up from a
        // milestone to the parent to move back into the "Backlog" column on the
        // parent workboard.

//        if ($const != PhabricatorProjectObjectHasProjectEdgeType::EDGECONST) {
//            return;
//        }
//
//        // TODO: This should likely be some future WorkboardInterface.
//        $appears_on_workboards = ($object instanceof ManiphestTask);
//        if (!$appears_on_workboards) {
//            return;
//        }
//
//        $removed_phids = array_keys(array_diff_key($old, $new));
//        if (!$removed_phids) {
//            return;
//        }
//
//        // Find any proxy columns for the removed projects.
//        $proxy_columns = (new PhabricatorProjectColumnQuery())
//            ->setViewer(PhabricatorUser::getOmnipotentUser())
//            ->withProxyPHIDs($removed_phids)
//            ->execute();
//        if (!$proxy_columns) {
//            return array();
//        }
//
//        $proxy_phids = OranginsUtil::mpull($proxy_columns, 'getPHID');
//
//        $position_table = new PhabricatorProjectColumnPosition();
//        $conn_w = $position_table->establishConnection('w');
//
//        queryfx(
//            $conn_w,
//            'DELETE FROM %T WHERE objectPHID = %s AND columnPHID IN (%Ls)',
//            $position_table->getTableName(),
//            $object->getPHID(),
//            $proxy_phids);
    }

    /**
     * @return array
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function getModularTransactionTypes()
    {
        if ($this->modularTypes === null) {
            $template = $this->object->getApplicationTransactionTemplate();
            if ($template instanceof PhabricatorModularTransaction) {
                $xtypes = $template->newModularTransactionTypes();
                foreach ($xtypes as $key => $xtype) {
                    $xtype = clone $xtype;
                    $xtype->setEditor($this);
                    $xtypes[$key] = $xtype;
                }
            } else {
                $xtypes = array();
            }

            $this->modularTypes = $xtypes;
        }

        return $this->modularTypes;
    }

    /**
     * @param $type
     * @return PhabricatorModularTransactionType
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function getModularTransactionType($type)
    {
        $types = $this->getModularTransactionTypes();
        return ArrayHelper::getValue($types, $type);
    }

    /**
     * @param $object
     * @param array $xactions
     * @throws Exception
     * @author 陈妙威
     */
    private function willApplyTransactions($object, array $xactions)
    {
        foreach ($xactions as $xaction) {
            $type = $xaction->getTransactionType();

            $xtype = $this->getModularTransactionType($type);
            if (!$xtype) {
                continue;
            }

            $xtype->willApplyTransactions($object, $xactions);
        }
    }

    /**
     * @param $author
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function getCreateObjectTitle($author, $object)
    {
        return Yii::t("app", '{0} created this object.', [$author]);
    }

    /**
     * @param $author
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function getCreateObjectTitleForFeed($author, $object)
    {
        return Yii::t("app", '{0} created an object: {1}.', [
            $author, $object
        ]);
    }

    /* -(  Queue  )-------------------------------------------------------------- */

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return $this
     * @author 陈妙威
     */
    protected function queueTransaction(
        PhabricatorApplicationTransaction $xaction)
    {
        $this->transactionQueue[] = $xaction;
        return $this;
    }

    /**
     * @param $object
     * @author 陈妙威
     */
    private function flushTransactionQueue($object)
    {
        if (!$this->transactionQueue) {
            return;
        }

        $xactions = $this->transactionQueue;
        $this->transactionQueue = array();

        $editor = $this->newQueueEditor();

        return $editor->applyTransactions($object, $xactions);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function newQueueEditor()
    {
        /** @var PhabricatorApplicationTransactionEditor $newv */
        $newv = newv(get_class($this), array());
        $editor = $newv
            ->setActor($this->getActor())
            ->setContentSource($this->getContentSource())
            ->setContinueOnNoEffect($this->getContinueOnNoEffect())
            ->setContinueOnMissingFields($this->getContinueOnMissingFields())
            ->setIsSilent($this->getIsSilent());

        if ($this->actingAsPHID !== null) {
            $editor->setActingAsPHID($this->actingAsPHID);
        }

        return $editor;
    }


    /* -(  Stamps  )------------------------------------------------------------- */


    /**
     * @param $object
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function newMailStampTemplates($object)
    {
        $actor = $this->getActor();

        $templates = array();
        $extensions = $this->newMailExtensions($object);
        foreach ($extensions as $extension) {
            $stamps = $extension->newMailStampTemplates($object);
            foreach ($stamps as $stamp) {
                $key = $stamp->getKey();
                if (isset($templates[$key])) {
                    throw new Exception(
                        Yii::t("app",
                            'Mail extension ("{0}") defines a stamp template with the ' .
                            'same key ("{1}") as another template. Each stamp template ' .
                            'must have a unique key.',
                            [
                                get_class($extension),
                                $key
                            ]));
                }

                $stamp->setViewer($actor);

                $templates[$key] = $stamp;
            }
        }

        return $templates;
    }

    /**
     * @param $key
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final public function getMailStamp($key)
    {
        if (!isset($this->stampTemplates)) {
            throw new PhutilInvalidStateException('newMailStampTemplates');
        }

        if (!isset($this->stampTemplates[$key])) {
            throw new Exception(
                Yii::t("app",
                    'Editor ("{0}") has no mail stamp template with provided key ("{1}").',
                    [
                        get_class($this),
                        $key
                    ]));
        }

        return $this->stampTemplates[$key];
    }

    /**
     * @param $object
     * @param array $xactions
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    private function newMailStamps($object, array $xactions)
    {
        $actor = $this->getActor();

        $this->stampTemplates = $this->newMailStampTemplates($object);

        $extensions = $this->newMailExtensions($object);
        $stamps = array();
        foreach ($extensions as $extension) {
            $extension->newMailStamps($object, $xactions);
        }

        return $this->stampTemplates;
    }

    /**
     * @param $object
     * @return array
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function newMailExtensions($object)
    {
        $actor = $this->getActor();

        $all_extensions = PhabricatorMailEngineExtension::getAllExtensions();

        $extensions = array();
        foreach ($all_extensions as $key => $template) {
            $x = clone $template;
            $extension = $x
                ->setViewer($actor)
                ->setEditor($this);

            if ($extension->supportsObject($object)) {
                $extensions[$key] = $extension;
            }
        }

        return $extensions;
    }

    /**
     * @param $object
     * @param $data
     * @return array|null
     * @throws Exception
     * @author 陈妙威
     */
    private function generateMailStamps($object, $data)
    {
        if (!$data || !is_array($data)) {
            return null;
        }

        $templates = $this->newMailStampTemplates($object);
        foreach ($data as $spec) {
            if (!is_array($spec)) {
                continue;
            }

            $key = ArrayHelper::getValue($spec, 'key');
            if (!isset($templates[$key])) {
                continue;
            }

            $type = ArrayHelper::getValue($spec, 'type');
            if ($templates[$key]->getStampType() !== $type) {
                continue;
            }

            $value = ArrayHelper::getValue($spec, 'value');
            $templates[$key]->setValueFromDictionary($value);
        }

        $results = array();
        foreach ($templates as $template) {
            $value = $template->getValueForRendering();

            $rendered = $template->renderStamps($value);
            if ($rendered === null) {
                continue;
            }

            $rendered = (array)$rendered;
            foreach ($rendered as $stamp) {
                $results[] = $stamp;
            }
        }

        natcasesort($results);

        return $results;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getRemovedRecipientPHIDs()
    {
        return $this->mailRemovedPHIDs;
    }

    /**
     * @param $object
     * @param $xactions
     * @return $this
     * @throws PhutilMethodNotImplementedException
     * @throws Exception
     * @author 陈妙威
     */
    private function buildOldRecipientLists($object, $xactions)
    {
        // See T4776. Before we start making any changes, build a list of the old
        // recipients. If a change removes a user from the recipient list for an
        // object we still want to notify the user about that change. This allows
        // them to respond if they didn't want to be removed.

        if (!$this->shouldSendMail($object, $xactions)) {
            return;
        }

        $this->oldTo = $this->getMailTo($object);
        $this->oldCC = $this->getMailCC($object);

        return $this;
    }

    /**
     * @return $this
     * @author 陈妙威
     */
    private function applyOldRecipientLists()
    {
        $actor_phid = $this->getActingAsPHID();

        // If you took yourself off the recipient list (for example, by
        // unsubscribing or resigning) assume that you know what you did and
        // don't need to be notified.

        // If you just moved from "To" to "Cc" (or vice versa), you're still a
        // recipient so we don't need to add you back in.

        $map = OranginsUtil::array_fuse($this->mailToPHIDs) + OranginsUtil::array_fuse($this->mailCCPHIDs);

        foreach ($this->oldTo as $phid) {
            if ($phid === $actor_phid) {
                continue;
            }

            if (isset($map[$phid])) {
                continue;
            }

            $this->mailToPHIDs[] = $phid;
            $this->mailRemovedPHIDs[] = $phid;
        }

        foreach ($this->oldCC as $phid) {
            if ($phid === $actor_phid) {
                continue;
            }

            if (isset($map[$phid])) {
                continue;
            }

            $this->mailCCPHIDs[] = $phid;
            $this->mailRemovedPHIDs[] = $phid;
        }

        return $this;
    }

    /**
     * @param $object
     * @param array $xactions
     * @throws InvalidConfigException
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws Exception
     * @throws Throwable
     * @author 陈妙威
     */
    private function queueWebhooks($object, array $xactions)
    {
        $hook_viewer = PhabricatorUser::getOmnipotentUser();

        $webhook_map = $this->webhookMap;
        if (!is_array($webhook_map)) {
            $webhook_map = array();
        }

        // Add any "Firehose" hooks to the list of hooks we're going to call.
        $firehose_hooks =  HeraldWebhook::find()
            ->setViewer($hook_viewer)
            ->withStatuses(
                [
                    HeraldWebhook::HOOKSTATUS_FIREHOSE,
                ])
            ->execute();
        foreach ($firehose_hooks as $firehose_hook) {
            // This is "the hook itself is the reason this hook is being called",
            // since we're including it because it's configured as a firehose
            // hook.
            $hook_phid = $firehose_hook->getPHID();
            $webhook_map[$hook_phid][] = $hook_phid;
        }

        if (!$webhook_map) {
            return;
        }

        // NOTE: We're going to queue calls to disabled webhooks, they'll just
        // immediately fail in the worker queue. This makes the behavior more
        // visible.

        $call_hooks = (new HeraldWebhookQuery())
            ->setViewer($hook_viewer)
            ->withPHIDs(array_keys($webhook_map))
            ->execute();

        foreach ($call_hooks as $call_hook) {
            $trigger_phids = ArrayHelper::getValue($webhook_map, $call_hook->getPHID());

            $heraldWebhookRequest = HeraldWebhookRequest::initializeNewWebhookRequest($call_hook)
                ->setObjectPHID($object->getPHID())
                ->setTransactionPHIDs(OranginsUtil::mpull($xactions, 'getPHID'))
                ->setTriggerPHIDs($trigger_phids)
                ->setRetryMode(HeraldWebhookRequest::RETRY_FOREVER)
                ->setIsSilentAction((bool)$this->getIsSilent())
                ->setIsSecureAction((bool)$this->getMustEncrypt());
            $heraldWebhookRequest->save();

            $heraldWebhookRequest->queueCall();
        }
    }

    /**
     * @param $object
     * @param $xaction
     * @return bool
     * @author 陈妙威
     */
    private function hasWarnings($object, $xaction)
    {
        // TODO: For the moment, this is a very un-modular hack to support
        // exactly one type of warning (mentioning users on a draft revision)
        // that we want to show. See PHI433.

        if (!($object instanceof DifferentialRevision)) {
            return false;
        }

        if (!$object->isDraft()) {
            return false;
        }

        $type = $xaction->getTransactionType();
        if ($type != PhabricatorTransactions::TYPE_SUBSCRIBERS) {
            return false;
        }

        // NOTE: This will currently warn even if you're only removing
        // subscribers.

        return true;
    }

    /**
     * @param ActiveRecordPHID $object
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function buildHistoryMail(ActiveRecordPHID $object)
    {
        $viewer = $this->requireActor();
        $recipient_phid = $this->getActingAsPHID();

        // Load every transaction so we can build a mail message with a complete
        // history for the object.
        $query = PhabricatorApplicationTransactionQuery::newQueryForObject($object);
        $xactions = $query
            ->setViewer($viewer)
            ->withObjectPHIDs(array($object->getPHID()))
            ->execute();
        $xactions = array_reverse($xactions);

        $mail_messages = $this->buildMailWithRecipients(
            $object,
            $xactions,
            array($recipient_phid),
            array(),
            array());
        $mail = OranginsUtil::head($mail_messages);

        // Since the user explicitly requested "!history", force delivery of this
        // message regardless of their other mail settings.
        $mail->setForceDelivery(true);

        return $mail;
    }

}
