<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/9/6
 * Time: 10:11 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\transactions\models;

use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\lib\markup\PhabricatorMarkupEngine;
use orangins\lib\markup\view\PHUIRemarkupView;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\transactions\data\PhabricatorTransactionRemarkupChange;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionTextDiffDetailView;
use PhutilJSONParserException;
use PhutilMethodNotImplementedException;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\infrastructure\customfield\interfaces\PhabricatorCustomFieldInterface;
use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;
use orangins\lib\infrastructure\edges\util\PhabricatorEdgeChangeRecord;
use orangins\modules\transactions\edges\PhabricatorMutedByEdgeType;
use orangins\modules\transactions\edges\PhabricatorMutedEdgeType;
use orangins\modules\transactions\edges\PhabricatorObjectMentionedByObjectEdgeType;
use orangins\modules\transactions\edges\PhabricatorObjectMentionsObjectEdgeType;
use PhutilNumber;
use orangins\modules\feed\story\PhabricatorFeedStory;
use orangins\modules\meta\phid\PhabricatorApplicationApplicationPHIDType;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicyType;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\policy\models\PhabricatorPolicy;
use orangins\modules\policy\models\PhabricatorPolicyQuery;
use orangins\modules\transactions\phid\TransactionPHIDType;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;
use orangins\modules\subscriptions\view\SubscriptionListStringBuilder;
use PhutilReadableSerializer;
use Yii;
use Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Class OranginsApplicationTransaction
 * @package orangins\modules\transactions\models
 * @property string $phid
 * @property string $object_phid 对象ID
 * @property string $comment_phid 评论
 * @property int $comment_version 评论版本
 * @property string $transaction_type 类型
 * @property string $old_value 旧值
 * @property string $new_value 新值
 * @property string $content_source 内容
 * @property mixed $metadata 数据
 * @property integer $created_at 创建时间
 * @property integer $updated_at 更新时间
 * @property string $author_phid 作者
 * @property string $view_policy 显示权限
 * @property string $edit_policy 编辑权限
 * @author 陈妙威
 */
abstract class PhabricatorApplicationTransaction extends ActiveRecordPHID
    implements PhabricatorPolicyInterface
{
    /**
     *
     */
    const TARGET_TEXT = 'text';
    /**
     *
     */
    const TARGET_HTML = 'html';

    /**
     * @var bool
     */
    public $commentNotLoaded = false;

    /**
     * @var
     */
    private $comment;

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var bool
     */
    public $oldValueHasBeenSet = false;

    /**
     * @var
     */
    public $object = self::ATTACHABLE;

    /**
     * @var PhabricatorUser
     */
    public $viewer = self::ATTACHABLE;

    /**
     * Flag this transaction as a pure side-effect which should be ignored when
     * applying transactions if it has no effect, even if transaction application
     * would normally fail. This both provides users with better error messages
     * and allows transactions to perform optional side effects.
     */
    public $ignoreOnNoEffect;

    /**
     * @var
     */
    private $handles;

    /**
     * @var string
     */
    private $renderingTarget = self::TARGET_HTML;


    /**
     * @var array
     */
    protected $contentSource;

    /**
     * @var array
     */
    private $transactionGroup = array();

    /**
     * @author 陈妙威
     */
    public function init()
    {
        if ($this->getAttribute("comment_version") === null) {
            $this->setAttribute('comment_version', 0);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['object_phid', 'transaction_type', 'author_phid', 'view_policy', 'edit_policy'], 'required'],
            [['comment_version'], 'integer'],
            [['old_value', 'new_value'], 'safe'],
            ['content_source', 'default', 'value' => Json::encode([])],
            ['metadata', 'default', 'value' => Json::encode([])],
            [['content_source', 'metadata'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'object_phid', 'comment_phid', 'author_phid', 'view_policy', 'edit_policy'], 'string', 'max' => 64],
            [['transaction_type'], 'string', 'max' => 32],
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
            'object_phid' => Yii::t('app', '对象_id'),
            'comment_phid' => Yii::t('app', '评论'),
            'comment_version' => Yii::t('app', '评论版本'),
            'transaction_type' => Yii::t('app', '类型'),
            'old_value' => Yii::t('app', '旧值'),
            'new_value' => Yii::t('app', '新值'),
            'content_source' => Yii::t('app', '内容'),
            'metadata' => Yii::t('app', '数据'),
            'author_phid' => Yii::t('app', '作者'),
            'view_policy' => Yii::t('app', '显示权限'),
            'edit_policy' => Yii::t('app', '编辑权限'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string
     */
    public function getPHID()
    {
        return $this->phid;
    }

    /**
     * @param string $phid
     * @return self
     */
    public function setPHID($phid)
    {
        $this->phid = $phid;
        return $this;
    }


    /**
     * @param PhabricatorContentSource $content_source
     * @return $this
     * @throws Exception
     * @author 陈妙威
     */
    public function setContentSource(PhabricatorContentSource $content_source)
    {
        $this->contentSource = $content_source->serialize();
        return $this;
    }

    /**
     * @return PhabricatorContentSource
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getContentSource()
    {
        return PhabricatorContentSource::newFromSerialized($this->contentSource);
    }

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function attachViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->assertAttached($this->viewer);
    }

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    public function attachObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @param PhabricatorApplicationTransactionComment $comment
     * @return $this
     * @author 陈妙威
     */
    public function attachComment(
        PhabricatorApplicationTransactionComment $comment)
    {
        $this->comment = $comment;
        $this->commentNotLoaded = false;
        return $this;
    }

    /**
     * @param $not_loaded
     * @return $this
     * @author 陈妙威
     */
    public function setCommentNotLoaded($not_loaded)
    {
        $this->commentNotLoaded = $not_loaded;
        return $this;
    }

    /**
     * @return ActiveRecordPHID
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getObject()
    {
        return $this->assertAttached($this->object);
    }

    /**
     * @param $property
     * @return mixed
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    protected function assertAttached($property)
    {
        if ($property === self::ATTACHABLE) {
            throw new PhabricatorDataNotAttachedException($this);
        }
        return $property;
    }


    /**
     * @param $rendering_target
     * @return $this
     * @author 陈妙威
     */
    public function setRenderingTarget($rendering_target)
    {
        $this->renderingTarget = $rendering_target;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getRenderingTarget()
    {
        return $this->renderingTarget;
    }

    /**
     * @return string
     */
    public function getAuthorPHID()
    {
        return $this->author_phid;
    }

    /**
     * @param string $author_phid
     * @return PhabricatorApplicationTransaction
     */
    public function setAuthorPHID($author_phid)
    {
        $this->author_phid = $author_phid;
        return $this;
    }

    /**
     * Flag this transaction as a pure side-effect which should be ignored when
     * applying transactions if it has no effect, even if transaction application
     * would normally fail. This both provides users with better error messages
     * and allows transactions to perform optional side effects.
     * @param $ignore
     * @return PhabricatorApplicationTransaction
     */
    public function setIgnoreOnNoEffect($ignore)
    {
        $this->ignoreOnNoEffect = $ignore;
        return $this;
    }

    /**
     * @return int
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param int $created_at
     * @return self
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }

    /**
     * @return int
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @param int $updated_at
     * @return self
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIgnoreOnNoEffect()
    {
        return $this->ignoreOnNoEffect;
    }

    /**
     * @return mixed
     * @throws PhutilJSONParserException
     */
    public function getMetadata()
    {
        $phutil_json_decode = $this->metadata === null ? [] : OranginsUtil::phutil_json_decode($this->metadata);
        return $phutil_json_decode;
    }

    /**
     * @param mixed $metadata
     * @return self
     * @throws Exception
     */
    public function setMetadata($metadata)
    {
        $this->metadata = OranginsUtil::phutil_json_encode($metadata);
        return $this;
    }


    /**
     * @param $key
     * @param $value
     * @return $this
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function setMetadataValue($key, $value)
    {
        $parameter = $this->getMetadata();
        $parameter[$key] = $value;
        $this->metadata = OranginsUtil::phutil_json_encode($parameter);
        return $this;
    }


    /**
     * @return string
     */
    public function getCommentPHID()
    {
        return $this->comment_phid;
    }

    /**
     * @param string $comment_phid
     * @return self
     */
    public function setCommentPHID($comment_phid)
    {
        $this->comment_phid = $comment_phid;
        return $this;
    }

    /**
     * @return int
     */
    public function getCommentVersion()
    {
        return $this->comment_version;
    }

    /**
     * @param int $comment_version
     * @return self
     */
    public function setCommentVersion($comment_version)
    {
        $this->comment_version = $comment_version;
        return $this;
    }


    /**
     * @param $key
     * @param null $default
     * @return mixed
     * @author 陈妙威
     * @throws PhutilJSONParserException
     */
    public function getMetadataValue($key, $default = null)
    {
        return ArrayHelper::getValue($this->getMetadata(), $key, $default);
    }

    /**
     * @param $create
     * @return PhabricatorApplicationTransaction
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function setIsCreateTransaction($create)
    {
        return $this->setMetadataValue('core.create', $create);
    }

    /**
     * @return bool
     * @author 陈妙威
     * @throws PhutilJSONParserException
     */
    public function getIsCreateTransaction()
    {
        return (bool)$this->getMetadataValue('core.create', false);
    }

    /**
     * @param $default
     * @return PhabricatorApplicationTransaction
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function setIsDefaultTransaction($default)
    {
        return $this->setMetadataValue('core.default', $default);
    }

    /**
     * @return bool
     * @author 陈妙威
     * @throws PhutilJSONParserException
     */
    public function getIsDefaultTransaction()
    {
        return (bool)$this->getMetadataValue('core.default', false);
    }

    /**
     * @param $silent
     * @return PhabricatorApplicationTransaction
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function setIsSilentTransaction($silent)
    {
        return $this->setMetadataValue('core.silent', $silent);
    }

    /**
     * @return bool
     * @author 陈妙威
     * @throws PhutilJSONParserException
     */
    public function getIsSilentTransaction()
    {
        return (bool)$this->getMetadataValue('core.silent', false);
    }

    /**
     * @param $mfa
     * @return PhabricatorApplicationTransaction
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function setIsMFATransaction($mfa)
    {
        return $this->setMetadataValue('core.mfa', $mfa);
    }

    /**
     * @return bool
     * @author 陈妙威
     * @throws PhutilJSONParserException
     */
    public function getIsMFATransaction()
    {
        return (bool)$this->getMetadataValue('core.mfa', false);
    }

    /**
     * @return string
     */
    public function getViewPolicy()
    {
        return $this->view_policy;
    }

    /**
     * @param string $view_policy
     * @return PhabricatorApplicationTransaction
     */
    public function setViewPolicy($view_policy)
    {
        $this->view_policy = $view_policy;
        return $this;

    }

    /**
     * @return string
     */
    public function getEditPolicy()
    {
        return $this->edit_policy;
    }

    /**
     * @param string $edit_policy
     * @return PhabricatorApplicationTransaction
     */
    public function setEditPolicy($edit_policy)
    {
        $this->edit_policy = $edit_policy;
        return $this;

    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function hasOldValue()
    {
        return $this->oldValueHasBeenSet;
    }


    /**
     * @return string
     */
    public function getTransactionType()
    {
        return $this->transaction_type;
    }

    /**
     * @param string $transaction_type
     * @return PhabricatorApplicationTransaction
     */
    public function setTransactionType($transaction_type)
    {
        $this->transaction_type = $transaction_type;
        return $this;
    }

    /**
     * @param array $array
     * @author 陈妙威
     * @return PhabricatorApplicationTransaction
     */
    public function setOptions(array $array)
    {
        $this->options = $array;
        return $this;
    }


    /**
     * @param $phid
     * @return PhabricatorObjectHandle
     * @throws Exception
     * @author 陈妙威
     */
    public function getHandle($phid)
    {
        if (empty($this->handles[$phid])) {
            throw new Exception(
                Yii::t("app",
                    'Transaction ("{0}", of type "{1}") requires a handle ("{2}") that it ' .
                    'did not load.',
                    [
                        $this->getPHID(),
                        $this->getTransactionType(),
                        $phid
                    ]));
        }
        return $this->handles[$phid];
    }

    /**
     * @return mixed
     */
    public function getHandles()
    {
        return $this->handles;
    }

    /**
     * @param mixed $handles
     */
    public function setHandles($handles)
    {
        $this->handles = $handles;
    }

    /**
     * @return string
     */
    public function getObjectPHID()
    {
        return $this->object_phid;
    }

    /**
     * @param string $object_phid
     */
    public function setObjectPHID($object_phid)
    {
        $this->object_phid = $object_phid;
    }

    /**
     * @return array
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function getToken()
    {
        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_TOKEN:
                $old = $this->getOldValue();
                $new = $this->getNewValue();
                if ($new) {
                    $icon = substr($new, 10);
                } else {
                    $icon = substr($old, 10);
                }
                return array($icon, !$this->getNewValue());
        }

        return array(null, null);
    }

    /**
     * @return null|string
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function getColor()
    {
        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_COMMENT;
                $comment = $this->getComment();
                if ($comment && $comment->getIsRemoved()) {
                    return 'black';
                }
                break;
            case PhabricatorTransactions::TYPE_EDGE:
//                switch ($this->getMetadataValue('edge:type')) {
//                    case DiffusionCommitRevertedByCommitEdgeType::EDGECONST:
//                        return 'pink';
//                    case DiffusionCommitRevertsCommitEdgeType::EDGECONST:
//                        return 'sky';
//                }
                break;
        }
        return null;
    }

    /**
     * @return string
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function getIcon()
    {
        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_COMMENT:
                $comment = $this->getComment();
                if ($comment && $comment->getIsRemoved()) {
                    return 'fa-trash';
                }
                return 'fa-comment';
            case PhabricatorTransactions::TYPE_SUBSCRIBERS:
                $old = $this->getOldValue();
                $new = $this->getNewValue();
                $add = array_diff($new, $old);
                $rem = array_diff($old, $new);
                if ($add && $rem) {
                    return 'fa-user';
                } else if ($add) {
                    return 'fa-user-plus';
                } else if ($rem) {
                    return 'fa-user-times';
                } else {
                    return 'fa-user';
                }
            case PhabricatorTransactions::TYPE_VIEW_POLICY:
            case PhabricatorTransactions::TYPE_EDIT_POLICY:
            case PhabricatorTransactions::TYPE_JOIN_POLICY:
                return 'fa-lock';
            case PhabricatorTransactions::TYPE_EDGE:
//                switch ($this->getMetadataValue('edge:type')) {
//                    case DiffusionCommitRevertedByCommitEdgeType::EDGECONST:
//                        return 'fa-undo';
//                    case DiffusionCommitRevertsCommitEdgeType::EDGECONST:
//                        return 'fa-ambulance';
//                }
                return 'fa-link';
            case PhabricatorTransactions::TYPE_TOKEN:
                return 'fa-trophy';
            case PhabricatorTransactions::TYPE_SPACE:
                return 'fa-th-large';
            case PhabricatorTransactions::TYPE_COLUMNS:
                return 'fa-columns';
        }

        return 'fa-pencil';
    }

    /**
     * @return string
     * @throws Exception
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilJSONParserException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function getTitle()
    {
        $author_phid = $this->getAuthorPHID();

        $old = $this->getOldValue();
        $new = $this->getNewValue();

        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_CREATE:
                return Yii::t("app",
                    '{0} created this object.',
                    [
                        $this->renderHandleLink($author_phid)
                    ]);
            case PhabricatorTransactions::TYPE_COMMENT:
                return Yii::t("app",
                    '{0} added a comment.',
                    [
                        $this->renderHandleLink($author_phid)
                    ]);
            case PhabricatorTransactions::TYPE_VIEW_POLICY:
                if ($this->getIsCreateTransaction()) {
                    return Yii::t("app",
                        '{0} created this object with visibility "{1}".',
                        [
                            $this->renderHandleLink($author_phid),
                            $this->renderPolicyName($new, 'new')
                        ]);
                } else {
                    return Yii::t("app",
                        '{0} changed the visibility from "{1}" to "{2}".',
                        [
                            $this->renderHandleLink($author_phid),
                            $this->renderPolicyName($old, 'old'),
                            $this->renderPolicyName($new, 'new')
                        ]);
                }
            case PhabricatorTransactions::TYPE_EDIT_POLICY:
                if ($this->getIsCreateTransaction()) {
                    return Yii::t("app",
                        '{0} created this object with edit policy "{1}".',
                        [
                            $this->renderHandleLink($author_phid),
                            $this->renderPolicyName($new, 'new')
                        ]);
                } else {
                    return Yii::t("app",
                        '{0} changed the edit policy from "{1}" to "{2}".',
                        [
                            $this->renderHandleLink($author_phid),
                            $this->renderPolicyName($old, 'old'),
                            $this->renderPolicyName($new, 'new')
                        ]);
                }
            case PhabricatorTransactions::TYPE_JOIN_POLICY:
                if ($this->getIsCreateTransaction()) {
                    return Yii::t("app",
                        '{0} created this object with join policy "{1}".',
                        [
                            $this->renderHandleLink($author_phid),
                            $this->renderPolicyName($new, 'new')
                        ]);
                } else {
                    return Yii::t("app",
                        '{0} changed the join policy from "{1}" to "{2}".',
                        [
                            $this->renderHandleLink($author_phid),
                            $this->renderPolicyName($old, 'old'),
                            $this->renderPolicyName($new, 'new')
                        ]);
                }
            case PhabricatorTransactions::TYPE_SPACE:
                if ($this->getIsCreateTransaction()) {
                    return Yii::t("app",
                        '{0} created this object in space {1}.',
                        [
                            $this->renderHandleLink($author_phid),
                            $this->renderHandleLink($new)
                        ]);
                } else {
                    return Yii::t("app",
                        '{0} shifted this object from the {1} space to the {2} space.',
                        [
                            $this->renderHandleLink($author_phid),
                            $this->renderHandleLink($old),
                            $this->renderHandleLink($new)
                        ]);
                }
            case PhabricatorTransactions::TYPE_SUBSCRIBERS:
                $add = array_diff($new, $old);
                $rem = array_diff($old, $new);

                if ($add && $rem) {
                    return Yii::t("app",
                        '{0} edited subscriber(s), added {1}: {2}; removed %d: {3}.',
                        [
                            $this->renderHandleLink($author_phid),
                            count($add),
                            $this->renderSubscriberList($add, 'add'),
                            count($rem),
                            $this->renderSubscriberList($rem, 'rem')
                        ]);
                } else if ($add) {
                    return Yii::t("app",
                        '{0} added {1} subscriber(s): {2}.',
                        [
                            $this->renderHandleLink($author_phid),
                            count($add),
                            $this->renderSubscriberList($add, 'add')
                        ]);
                } else if ($rem) {
                    return Yii::t("app",
                        '{0} removed {1} subscriber(s): {2}.',
                        [
                            $this->renderHandleLink($author_phid),
                            count($rem),
                            $this->renderSubscriberList($rem, 'rem')
                        ]);
                } else {
                    // This is used when rendering previews, before the user actually
                    // selects any CCs.
                    return Yii::t("app",
                        '{0} updated subscribers...',
                        [
                            $this->renderHandleLink($author_phid)
                        ]);
                }
                break;
            case PhabricatorTransactions::TYPE_EDGE:
                $record = PhabricatorEdgeChangeRecord::newFromTransaction($this);
                $add = $record->getAddedPHIDs();
                $rem = $record->getRemovedPHIDs();

                $type = $this->getMetadata('edge:type');
                $type = OranginsUtil::head($type);

                try {
                    $type_obj = PhabricatorEdgeType::getByConstant($type);
                } catch (Exception $ex) {
                    // Recover somewhat gracefully from edge transactions which
                    // we don't have the classes for.
                    return Yii::t("app",
                        '{0} edited an edge.',
                        [
                            $this->renderHandleLink($author_phid)
                        ]);
                }

                if ($add && $rem) {
                    return $type_obj->getTransactionEditString(
                        $this->renderHandleLink($author_phid),
                        new PhutilNumber(count($add) + count($rem)),
                        count($add),
                        $this->renderHandleList($add),
                        count($rem),
                        $this->renderHandleList($rem));
                } else if ($add) {
                    return $type_obj->getTransactionAddString(
                        $this->renderHandleLink($author_phid),
                        count($add),
                        $this->renderHandleList($add));
                } else if ($rem) {
                    return $type_obj->getTransactionRemoveString(
                        $this->renderHandleLink($author_phid),
                        count($rem),
                        $this->renderHandleList($rem));
                } else {
                    return $type_obj->getTransactionPreviewString(
                        $this->renderHandleLink($author_phid));
                }

            case PhabricatorTransactions::TYPE_CUSTOMFIELD:
                $field = $this->getTransactionCustomField();
                if ($field) {
                    return $field->getApplicationTransactionTitle($this);
                } else {
                    $developer_mode = 'phabricator.developer-mode';
                    $is_developer = PhabricatorEnv::getEnvConfig($developer_mode);
                    if ($is_developer) {
                        return Yii::t("app",
                            '{0} edited a custom field (with key "{0}").',
                            $this->renderHandleLink($author_phid),
                            $this->getMetadata('customfield:key'));
                    } else {
                        return Yii::t("app",
                            '{0} edited a custom field.',
                            $this->renderHandleLink($author_phid));
                    }
                }

            case PhabricatorTransactions::TYPE_TOKEN:
                if ($old && $new) {
                    return Yii::t("app",
                        '{0} updated a token.',
                        $this->renderHandleLink($author_phid));
                } else if ($old) {
                    return Yii::t("app",
                        '{0} rescinded a token.',
                        $this->renderHandleLink($author_phid));
                } else {
                    return Yii::t("app",
                        '{0} awarded a token.',
                        $this->renderHandleLink($author_phid));
                }

            case PhabricatorTransactions::TYPE_INLINESTATE:
                $done = 0;
                $undone = 0;
                foreach ($new as $phid => $state) {
                    if ($state == PhabricatorInlineCommentInterface::STATE_DONE) {
                        $done++;
                    } else {
                        $undone++;
                    }
                }
                if ($done && $undone) {
                    return Yii::t("app",
                        '{0} marked {0} inline comment(s) as done and {0} inline comment(s) ' .
                        'as not done.',
                        $this->renderHandleLink($author_phid),
                        new PhutilNumber($done),
                        new PhutilNumber($undone));
                } else if ($done) {
                    return Yii::t("app",
                        '{0} marked {0} inline comment(s) as done.',
                        $this->renderHandleLink($author_phid),
                        new PhutilNumber($done));
                } else {
                    return Yii::t("app",
                        '{0} marked {0} inline comment(s) as not done.',
                        $this->renderHandleLink($author_phid),
                        new PhutilNumber($undone));
                }
                break;

            case PhabricatorTransactions::TYPE_COLUMNS:
                $moves = $this->getInterestingMoves($new);
                if (count($moves) == 1) {
                    $move = OranginsUtil::head($moves);
                    $from_columns = $move['fromColumnPHIDs'];
                    $to_column = $move['columnPHID'];
                    $board_phid = $move['boardPHID'];
                    if (count($from_columns) == 1) {
                        return Yii::t("app",
                            '{0} moved this task from {0} to {0} on the {0} board.',
                            $this->renderHandleLink($author_phid),
                            $this->renderHandleLink(OranginsUtil::head($from_columns)),
                            $this->renderHandleLink($to_column),
                            $this->renderHandleLink($board_phid));
                    } else {
                        return Yii::t("app",
                            '{0} moved this task to {0} on the {0} board.',
                            $this->renderHandleLink($author_phid),
                            $this->renderHandleLink($to_column),
                            $this->renderHandleLink($board_phid));
                    }
                } else {
                    $fragments = array();
                    foreach ($moves as $move) {
                        $from_columns = $move['fromColumnPHIDs'];
                        $to_column = $move['columnPHID'];
                        $board_phid = $move['boardPHID'];

                        $fragments[] = Yii::t("app",
                            '{0} ({0})',
                            $this->renderHandleLink($board_phid),
                            $this->renderHandleLink($to_column));
                    }

                    return Yii::t("app",
                        '{0} moved this task on {1} board(s): {2}.',
                        [
                            $this->renderHandleLink($author_phid),
                            count($moves),
                            Html::encode(implode(', ', $fragments))
                        ]);
                }
                break;
            default:
                // In developer mode, provide a better hint here about which string
                // we're missing.
                $developer_mode = 'phabricator.developer-mode';
                $is_developer = PhabricatorEnv::getEnvConfig($developer_mode);
                if ($is_developer) {
                    return Yii::t("app",
                        '{0} edited this object (transaction type "{0}").',
                        $this->renderHandleLink($author_phid),
                        $this->getTransactionType());
                } else {
                    return Yii::t("app",
                        '{0} edited this {1}.',
                        [
                            $this->renderHandleLink($author_phid),
                            $this->getApplicationObjectTypeName()
                        ]);
                }
        }
    }

    /**
     * 通过对象(object_phid)的PHIDType获取对象的名称
     * @return string
     * @throws Exception
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function getApplicationObjectTypeName()
    {
        $types = PhabricatorPHIDType::getAllTypes();

        /** @var PhabricatorPHIDType $type */
        $type = ArrayHelper::getValue($types, $this->getPHIDTypeInstance()->getTypeConstant());
        if ($type) {
            return $type->getTypeName();
        }
        return Yii::t("app", 'Object');
    }

    /**
     * @return array|mixed[]
     * @throws Exception
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function getRequiredHandlePHIDs()
    {
        $phids = array();

        $old = $this->getOldValue();
        $new = $this->getNewValue();

        $phids[] = array($this->getAuthorPHID());
        $phids[] = array($this->getObjectPHID());
        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_CUSTOMFIELD:
                $field = $this->getTransactionCustomField();
                if ($field) {
                    $phids[] = $field->getApplicationTransactionRequiredHandlePHIDs(
                        $this);
                }
                break;
            case PhabricatorTransactions::TYPE_SUBSCRIBERS:
                $phids[] = $old;
                $phids[] = $new;
                break;
            case PhabricatorTransactions::TYPE_EDGE:
                $record = PhabricatorEdgeChangeRecord::newFromTransaction($this);
                $phids[] = $record->getChangedPHIDs();
                break;
            case PhabricatorTransactions::TYPE_COLUMNS:
                foreach ($new as $move) {
                    $phids[] = array(
                        $move['columnPHID'],
                        $move['boardPHID'],
                    );
                    $phids[] = $move['fromColumnPHIDs'];
                }
                break;
            case PhabricatorTransactions::TYPE_EDIT_POLICY:
            case PhabricatorTransactions::TYPE_VIEW_POLICY:
            case PhabricatorTransactions::TYPE_JOIN_POLICY:
                if (!PhabricatorPolicyQuery::isSpecialPolicy($old)) {
                    $phids[] = array($old);
                }
                if (!PhabricatorPolicyQuery::isSpecialPolicy($new)) {
                    $phids[] = array($new);
                }
                break;
            case PhabricatorTransactions::TYPE_SPACE:
                if ($old) {
                    $phids[] = array($old);
                }
                if ($new) {
                    $phids[] = array($new);
                }
                break;
            case PhabricatorTransactions::TYPE_TOKEN:
                break;
        }

        if ($this->getComment()) {
            $phids[] = array($this->getComment()->getAuthorPHID());
        }

        return OranginsUtil::array_mergev($phids);
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getComment()
    {
        if ($this->commentNotLoaded) {
            throw new Exception(Yii::t("app", 'Comment for this transaction was not loaded.'));
        }
        return $this->comment;
    }

    /**
     * @param $phid
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     * @throws \Exception
     */
    public function renderHandleLink($phid)
    {
        if ($this->renderingTarget == self::TARGET_HTML) {
            return $this->getHandle($phid)->renderLink();
        } else {
            return $this->getHandle($phid)->getLinkName();
        }
    }

    /**
     * @param array $phids
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public function renderHandleList(array $phids)
    {
        $links = array();
        foreach ($phids as $phid) {
            $links[] = Html::encode($this->renderHandleLink($phid));
        }
        if ($this->renderingTarget == self::TARGET_HTML) {
            return implode("\n", $links);
        } else {
            return implode(', ', $links);
        }
    }

    /**
     * @param $phid
     * @param string $state
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    protected function renderPolicyName($phid, $state = 'old')
    {
        $policy = PhabricatorPolicy::newFromPolicyAndHandle(
            $phid,
            $this->getHandleIfExists($phid));
        if ($this->renderingTarget == self::TARGET_HTML) {
            switch ($policy->getType()) {
                case PhabricatorPolicyType::TYPE_CUSTOM:
                    $policy->setHref('/transactions/' . $state . '/' . $this->getPHID() . '/');
                    $policy->setWorkflow(true);
                    break;
                default:
                    break;
            }
            $output = $policy->renderDescription();
        } else {
            $output = Yii::t("app", '{0}', [$policy->getFullName()]);
        }
        return $output;
    }


    /**
     * @param $phid
     * @return mixed
     * @author 陈妙威
     */
    public function getHandleIfExists($phid)
    {
        return ArrayHelper::getValue($this->handles, $phid);
    }

    /**
     * @param array $phids
     * @param $change_type
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    private function renderSubscriberList(array $phids, $change_type)
    {
        if ($this->getRenderingTarget() == self::TARGET_TEXT) {
            return $this->renderHandleList($phids);
        } else {
            $handles = OranginsUtil::array_select_keys($this->getHandles(), $phids);
            return (new SubscriptionListStringBuilder())
                ->setHandles($handles)
                ->setObjectPHID($this->getPHID())
                ->buildTransactionString($change_type);
        }
    }

    /**
     * @param bool $insert
     * @return bool
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function beforeSave($insert)
    {
        if ($insert && !$this->getAttribute("phid")) {
            /** @var PhabricatorPHIDType $originPHIDType */
            $value = PhabricatorPHID::generateNewPHID(TransactionPHIDType::TYPECONST . "-" . $this->getObject()->getPHIDTypeInstance()->getTypeConstant());
            $this->setAttribute("phid", $value);
        }
        return parent::beforeSave($insert);
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws PhutilJSONParserException
     */
    public function getNewValue()
    {
        $new_value = $this->getAttribute('new_value');
        if ($new_value === null) return $new_value;
        $phutil_json_decode = @json_decode($new_value, true);
        if (json_last_error()) {
            throw new PhutilJSONParserException(
                Yii::t('app', '{0} is not a valid JSON object.', PhutilReadableSerializer::printShort($this->new_value)));
        }
        return $phutil_json_decode;
    }

    /**
     * @param $value
     * @return $this
     * @throws \Exception
     * @author 陈妙威
     */
    public function setNewValue($value)
    {
        $this->new_value = OranginsUtil::phutil_json_encode($value);
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws PhutilJSONParserException
     */
    public function getOldValue()
    {
        $old_value = $this->getAttribute('old_value');
        if ($old_value === null) return $old_value;
        $phutil_json_decode = @json_decode($old_value, true);
        if (json_last_error()) {
            throw new PhutilJSONParserException(
                Yii::t('app', '{0} is not a valid JSON object.', PhutilReadableSerializer::printShort($this->old_value)));
        }
        return $phutil_json_decode;
    }

    /**
     * @param $value
     * @return $this
     * @throws \Exception
     * @author 陈妙威
     */
    public function setOldValue($value)
    {
        $this->oldValueHasBeenSet = true;
        $this->old_value = OranginsUtil::phutil_json_encode($value);
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return TransactionPHIDType::class;
    }

    /**
     * @throws PhutilMethodNotImplementedException|null
     * @author 陈妙威
     */
    public function getApplicationTransactionCommentObject()
    {
        return null;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldGenerateOldValue()
    {
        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_TOKEN:
            case PhabricatorTransactions::TYPE_CUSTOMFIELD:
            case PhabricatorTransactions::TYPE_INLINESTATE:
                return false;
        }
        return true;
    }

    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function hasComment()
    {
        return $this->getComment() && strlen($this->getComment()->getContent());
    }


    /**
     * @return PhabricatorApplicationTransactionView
     * @author 陈妙威
     */
    public function getApplicationTransactionViewObject()
    {
        return new PhabricatorApplicationTransactionView();
    }


    /**
     * @return bool
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function shouldHide()
    {
        // Never hide comments.
        if ($this->hasComment()) {
            return false;
        }

        $xaction_type = $this->getTransactionType();

        // Always hide requests for object history.
        if ($xaction_type === PhabricatorTransactions::TYPE_HISTORY) {
            return true;
        }

        // Hide creation transactions if the old value is empty. These are
        // transactions like "alice set the task title to: ...", which are
        // essentially never interesting.
        if ($this->getIsCreateTransaction()) {
            switch ($xaction_type) {
                case PhabricatorTransactions::TYPE_CREATE:
                case PhabricatorTransactions::TYPE_VIEW_POLICY:
                case PhabricatorTransactions::TYPE_EDIT_POLICY:
                case PhabricatorTransactions::TYPE_JOIN_POLICY:
                case PhabricatorTransactions::TYPE_SPACE:
                    break;
                case PhabricatorTransactions::TYPE_SUBTYPE:
                    return true;
                default:
                    $old = $this->getOldValue();

                    if (is_array($old) && !$old) {
                        return true;
                    }

                    if (!is_array($old)) {
                        if (!strlen($old)) {
                            return true;
                        }

                        // The integer 0 is also uninteresting by default; this is often
                        // an "off" flag for something like "All Day Event".
                        if ($old === 0) {
                            return true;
                        }
                    }

                    break;
            }
        }

        // Hide creation transactions setting values to defaults, even if
        // the old value is not empty. For example, tasks may have a global
        // default view policy of "All Users", but a particular form sets the
        // policy to "Administrators". The transaction corresponding to this
        // change is not interesting, since it is the default behavior of the
        // form.

        if ($this->getIsCreateTransaction()) {
            if ($this->getIsDefaultTransaction()) {
                return true;
            }
        }

        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_VIEW_POLICY:
            case PhabricatorTransactions::TYPE_EDIT_POLICY:
            case PhabricatorTransactions::TYPE_JOIN_POLICY:
            case PhabricatorTransactions::TYPE_SPACE:
                if ($this->getIsCreateTransaction()) {
                    break;
                }

                // TODO: Remove this eventually, this is handling old changes during
                // object creation prior to the introduction of "create" and "default"
                // transaction display flags.

                // NOTE: We can also hit this case with Space transactions that later
                // update a default space (`null`) to an explicit space, so handling
                // the Space case may require some finesse.

                if ($this->getOldValue() === null) {
                    return true;
                } else {
                    return false;
                }
                break;
            case PhabricatorTransactions::TYPE_CUSTOMFIELD:
                $field = $this->getTransactionCustomField();
                if ($field) {
                    return $field->shouldHideInApplicationTransactions($this);
                }
                break;
            case PhabricatorTransactions::TYPE_COLUMNS:
                return !$this->getInterestingMoves($this->getNewValue());
            case PhabricatorTransactions::TYPE_EDGE:
                $edge_type = $this->getMetadataValue('edge:type');
                switch ($edge_type) {
                    case PhabricatorObjectMentionsObjectEdgeType::EDGECONST:
//                    case ManiphestTaskHasDuplicateTaskEdgeType::EDGECONST:
//                    case ManiphestTaskIsDuplicateOfTaskEdgeType::EDGECONST:
                    case PhabricatorMutedEdgeType::EDGECONST:
                    case PhabricatorMutedByEdgeType::EDGECONST:
                        return true;
                        break;
                    case PhabricatorObjectMentionedByObjectEdgeType::EDGECONST:
                        $record = PhabricatorEdgeChangeRecord::newFromTransaction($this);
                        $add = $record->getAddedPHIDs();
                        $add_value = reset($add);
                        $add_handle = $this->getHandle($add_value);
                        if ($add_handle->getPolicyFiltered()) {
                            return true;
                        }
                        return false;
                        break;
                    default:
                        break;
                }
                break;
        }

        return false;
    }

    /**
     * @return bool
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function shouldHideForFeed()
    {
        if ($this->isSelfSubscription()) {
            return true;
        }

        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_TOKEN:
            case PhabricatorTransactions::TYPE_MFA:
                return true;
            case PhabricatorTransactions::TYPE_EDGE:
                $edge_type = $this->getMetadataValue('edge:type');
                switch ($edge_type) {
                    case PhabricatorObjectMentionsObjectEdgeType::EDGECONST:
                    case PhabricatorObjectMentionedByObjectEdgeType::EDGECONST:
                        return true;
                    default:
                        break;
                }
                break;
            case PhabricatorTransactions::TYPE_INLINESTATE:
                return true;
        }

        return $this->shouldHide();
    }

    /**
     * @return bool
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function shouldHideForNotifications()
    {
        return $this->shouldHideForFeed();
    }

    /**
     * @param $new_target
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function getTitleForMailWithRenderingTarget($new_target)
    {
        $old_target = $this->getRenderingTarget();
        try {
            $this->setRenderingTarget($new_target);
            $result = $this->getTitleForMail();
        } catch (Exception $ex) {
            $this->setRenderingTarget($old_target);
            throw $ex;
        }
        $this->setRenderingTarget($old_target);
        return $result;
    }


    /**
     * @param array $group
     * @return $this
     * @author 陈妙威
     */
    public function attachTransactionGroup(array $group)
    {
        OranginsUtil::assert_instances_of($group, __CLASS__);
        $this->transactionGroup = $group;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTransactionGroup()
    {
        return $this->transactionGroup;
    }


    /**
     * Should this transaction be visually grouped with an existing transaction
     * group?
     *
     * @param array $group
     * @return bool True to display in a group with the other transactions.
     * @throws Exception
     * @throws \ReflectionException
     * @throws PhutilJSONParserException
     */
    public function shouldDisplayGroupWith(array $group)
    {
        $this_source = null;
        if ($this->getContentSource()) {
            $this_source = $this->getContentSource()->getSource();
        }

        foreach ($group as $xaction) {
            // Don't group transactions by different authors.
            if ($xaction->getAuthorPHID() != $this->getAuthorPHID()) {
                return false;
            }

            // Don't group transactions for different objects.
            if ($xaction->getObjectPHID() != $this->getObjectPHID()) {
                return false;
            }

            // Don't group anything into a group which already has a comment.
            if ($xaction->isCommentTransaction()) {
                return false;
            }

            // Don't group transactions from different content sources.
            $other_source = null;
            if ($xaction->getContentSource()) {
                $other_source = $xaction->getContentSource()->getSource();
            }

            if ($other_source != $this_source) {
                return false;
            }

            // Don't group transactions which happened more than 2 minutes apart.
            $apart = abs($xaction->created_at - $this->created_at);
            if ($apart > (60 * 2)) {
                return false;
            }

            // Don't group silent and nonsilent transactions together.
            $is_silent = $this->getIsSilentTransaction();
            if ($is_silent != $xaction->getIsSilentTransaction()) {
                return false;
            }

            // Don't group MFA and non-MFA transactions together.
            $is_mfa = $this->getIsMFATransaction();
            if ($is_mfa != $xaction->getIsMFATransaction()) {
                return false;
            }
        }

        return true;
    }


    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function isCommentTransaction()
    {
        if ($this->hasComment()) {
            return true;
        }

        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_COMMENT:
                return true;
        }

        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isInlineCommentTransaction()
    {
        return false;
    }


    /**
     * Test if this transaction is just a user subscribing or unsubscribing
     * themselves.
     */
    private function isSelfSubscription()
    {
        $type = $this->getTransactionType();
        if ($type != PhabricatorTransactions::TYPE_SUBSCRIBERS) {
            return false;
        }

        $old = $this->getOldValue();
        $new = $this->getNewValue();

        $add = array_diff($old, $new);
        $rem = array_diff($new, $old);

        if ((count($add) + count($rem)) != 1) {
            // More than one user affected.
            return false;
        }

        $affected_phid = OranginsUtil::head(array_merge($add, $rem));
        if ($affected_phid != $this->getAuthorPHID()) {
            // Affected user is someone else.
            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    private function isApplicationAuthor()
    {
        $author_phid = $this->getAuthorPHID();
        $author_type = PhabricatorPHID::phid_get_type($author_phid);
        $application_type = PhabricatorApplicationApplicationPHIDType::TYPECONST;
        return ($author_type == $application_type);
    }

    /**
     * @return float
     * @author 陈妙威
     */
    public function getActionStrength()
    {
        if ($this->isInlineCommentTransaction()) {
            return 0.25;
        }

        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_COMMENT:
                return 0.5;
            case PhabricatorTransactions::TYPE_SUBSCRIBERS:
                if ($this->isSelfSubscription()) {
                    // Make this weaker than TYPE_COMMENT.
                    return 0.25;
                }

                if ($this->isApplicationAuthor()) {
                    // When applications (most often: Herald) change subscriptions it
                    // is very uninteresting.
                    return 0.000000001;
                }

                // In other cases, subscriptions are more interesting than comments
                // (which are shown anyway) but less interesting than any other type of
                // transaction.
                return 0.75;
        }

        return 1.0;
    }


    /**
     * @return bool
     * @throws Exception
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function hasChangeDetails()
    {
        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_CUSTOMFIELD:
                $field = $this->getTransactionCustomField();
                if ($field) {
                    return $field->getApplicationTransactionHasChangeDetails($this);
                }
                break;
        }
        return false;
    }

    /**
     * @return PhabricatorCustomField
     * @throws Exception
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    protected function getTransactionCustomField()
    {
        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_CUSTOMFIELD:
                $key = $this->getMetadataValue('customfield:key');
                if (!$key) {
                    return null;
                }

                $object = $this->getObject();

                if (!($object instanceof PhabricatorCustomFieldInterface)) {
                    return null;
                }

                $field = PhabricatorCustomField::getObjectField(
                    $object,
                    PhabricatorCustomField::ROLE_APPLICATIONTRANSACTIONS,
                    $key);
                if (!$field) {
                    return null;
                }

                $field->setViewer($this->getViewer());
                return $field;
        }

        return null;
    }


    /**
     * @return null|string
     * @throws Exception
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function renderExtraInformationLink()
    {
        $herald_xscript_id = $this->getMetadataValue('herald:transcriptID');

        if ($herald_xscript_id) {
            return JavelinHtml::phutil_tag(
                'a',
                array(
                    'href' => '/herald/transcript/' . $herald_xscript_id . '/',
                ),
                Yii::t("app", 'View Herald Transcript'));
        }

        return null;
    }

//    /**
//     * @param DoorkeeperFeedStoryPublisher $publisher
//     * @param PhabricatorFeedStory $story
//     * @param array $xactions
//     * @return string
//     * @author 陈妙威
//     */
//    public function renderAsTextForDoorkeeper(
//        DoorkeeperFeedStoryPublisher $publisher,
//        PhabricatorFeedStory $story,
//        array $xactions)
//    {
//
//        $text = array();
//        $body = array();
//
//        foreach ($xactions as $xaction) {
//            $xaction_body = $xaction->getBodyForMail();
//            if ($xaction_body !== null) {
//                $body[] = $xaction_body;
//            }
//
//            if ($xaction->shouldHideForMail($xactions)) {
//                continue;
//            }
//
//            $old_target = $xaction->getRenderingTarget();
//            $new_target = self::TARGET_TEXT;
//            $xaction->setRenderingTarget($new_target);
//
//            if ($publisher->getRenderWithImpliedContext()) {
//                $text[] = $xaction->getTitle();
//            } else {
//                $text[] = $xaction->getTitleForFeed();
//            }
//
//            $xaction->setRenderingTarget($old_target);
//        }
//
//        $text = implode("\n", $text);
//        $body = implode("\n\n", $body);
//
//        return rtrim($text . "\n\n" . $body);
//    }


    /* -(  PhabricatorPolicyInterface Implementation  )-------------------------- */


    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getCapabilities()
    {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
        );
    }

    /**
     * @param $capability
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                return $this->getViewPolicy();
            case PhabricatorPolicyCapability::CAN_EDIT:
                return $this->getEditPolicy();
        }
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool|mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return ($viewer->getPHID() == $this->getAuthorPHID());
    }

    /**
     * @param $capability
     * @return string
     * @author 陈妙威
     */
    public function describeAutomaticCapability($capability)
    {
        return Yii::t("app",
            'Transactions are visible to users that can see the object which was ' .
            'acted upon. Some transactions - in particular, comments - are ' .
            'editable by the transaction author.');
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getApplicationTransactionType();


    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function getRemarkupChanges()
    {
        $changes = $this->newRemarkupChanges();
        assert_instances_of($changes, PhabricatorTransactionRemarkupChange::class);

        // Convert older-style remarkup blocks into newer-style remarkup changes.
        // This builds changes that do not have the correct "old value", so rules
        // that operate differently against edits (like @user mentions) won't work
        // properly.
        foreach ($this->getRemarkupBlocks() as $block) {
            $changes[] = $this->newRemarkupChange()
                ->setOldValue(null)
                ->setNewValue($block);
        }

        $comment = $this->getComment();
        if ($comment) {
            if ($comment->hasOldComment()) {
                $old_value = $comment->getOldComment()->getContent();
            } else {
                $old_value = null;
            }

            $new_value = $comment->getContent();

            $changes[] = $this->newRemarkupChange()
                ->setOldValue($old_value)
                ->setNewValue($new_value);
        }

        return $changes;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function newRemarkupChanges()
    {
        return array();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function newRemarkupChange()
    {
        return (new PhabricatorTransactionRemarkupChange())
            ->setTransaction($this);
    }

    /**
     * @deprecated
     */
    public function getRemarkupBlocks()
    {
        $blocks = array();

        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_CUSTOMFIELD:
                $field = $this->getTransactionCustomField();
                if ($field) {
                    $custom_blocks = $field->getApplicationTransactionRemarkupBlocks(
                        $this);
                    foreach ($custom_blocks as $custom_block) {
                        $blocks[] = $custom_block;
                    }
                }
                break;
        }

        return $blocks;
    }

    /**
     * @return string
     * @throws Exception
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getNoEffectDescription()
    {

        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_COMMENT:
                return \Yii::t("app", 'You can not post an empty comment.');
            case PhabricatorTransactions::TYPE_VIEW_POLICY:
                return \Yii::t("app",
                    'This %s already has that view policy.',
                    $this->getApplicationObjectTypeName());
            case PhabricatorTransactions::TYPE_EDIT_POLICY:
                return \Yii::t("app",
                    'This %s already has that edit policy.',
                    $this->getApplicationObjectTypeName());
            case PhabricatorTransactions::TYPE_JOIN_POLICY:
                return \Yii::t("app",
                    'This %s already has that join policy.',
                    $this->getApplicationObjectTypeName());
            case PhabricatorTransactions::TYPE_SUBSCRIBERS:
                return \Yii::t("app",
                    'All users are already subscribed to this %s.',
                    $this->getApplicationObjectTypeName());
            case PhabricatorTransactions::TYPE_SPACE:
                return \Yii::t("app", 'This object is already in that space.');
            case PhabricatorTransactions::TYPE_EDGE:
                return \Yii::t("app", 'Edges already exist; transaction has no effect.');
            case PhabricatorTransactions::TYPE_COLUMNS:
                return \Yii::t("app",
                    'You have not moved this object to any columns it is not ' .
                    'already in.');
            case PhabricatorTransactions::TYPE_MFA:
                return \Yii::t("app",
                    'You can not sign a transaction group that has no other ' .
                    'effects.');
        }

        return \Yii::t("app",
            'Transaction (of type "%s") has no effect.',
            $this->getTransactionType());
    }

    /**
     * @return string
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilJSONParserException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getTitleForMail()
    {
        return $this->getTitle();
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getTitleForTextMail()
    {
        return $this->getTitleForMailWithRenderingTarget(self::TARGET_TEXT);
    }

    /**
     * @return array|mixed|null
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilJSONParserException
     * @throws Exception
     * @author 陈妙威
     */
    public function getTitleForHTMLMail()
    {
        // TODO: For now, rendering this with TARGET_HTML generates links with
        // bad targets ("/x/y/" instead of "https://dev.example.com/x/y/"). Throw
        // a rug over the issue for the moment. See T12921.

        $title = $this->getTitleForMailWithRenderingTarget(self::TARGET_TEXT);
        if ($title === null) {
            return null;
        }

        if ($this->hasChangeDetails()) {
            $details_uri = $this->getChangeDetailsURI();
            $details_uri = PhabricatorEnv::getProductionURI($details_uri);

            $show_details = phutil_tag(
                'a',
                array(
                    'href' => $details_uri,
                ),
                pht('(Show Details)'));

            $title = array($title, ' ', $show_details);
        }

        return $title;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getChangeDetailsURI()
    {
        return '/transactions/detail/' . $this->getPHID() . '/';
    }

    /**
     * @return null
     * @throws Exception
     * @author 陈妙威
     */
    public function getBodyForMail()
    {
        if ($this->isInlineCommentTransaction()) {
            // We don't return inline comment content as mail body content, because
            // applications need to contextualize it (by adding line numbers, for
            // example) in order for it to make sense.
            return null;
        }

        $comment = $this->getComment();
        if ($comment && strlen($comment->getContent())) {
            return $comment->getContent();
        }

        return null;
    }

    /**
     * @return null|string
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilJSONParserException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        $author_phid = $this->getAuthorPHID();
        $object_phid = $this->getObjectPHID();

        $old = $this->getOldValue();
        $new = $this->getNewValue();

        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_CREATE:
                return pht(
                    '%s created %s.',
                    $this->renderHandleLink($author_phid),
                    $this->renderHandleLink($object_phid));
            case PhabricatorTransactions::TYPE_COMMENT:
                return pht(
                    '%s added a comment to %s.',
                    $this->renderHandleLink($author_phid),
                    $this->renderHandleLink($object_phid));
            case PhabricatorTransactions::TYPE_VIEW_POLICY:
                return pht(
                    '%s changed the visibility for %s.',
                    $this->renderHandleLink($author_phid),
                    $this->renderHandleLink($object_phid));
            case PhabricatorTransactions::TYPE_EDIT_POLICY:
                return pht(
                    '%s changed the edit policy for %s.',
                    $this->renderHandleLink($author_phid),
                    $this->renderHandleLink($object_phid));
            case PhabricatorTransactions::TYPE_JOIN_POLICY:
                return pht(
                    '%s changed the join policy for %s.',
                    $this->renderHandleLink($author_phid),
                    $this->renderHandleLink($object_phid));
            case PhabricatorTransactions::TYPE_SUBSCRIBERS:
                return pht(
                    '%s updated subscribers of %s.',
                    $this->renderHandleLink($author_phid),
                    $this->renderHandleLink($object_phid));
            case PhabricatorTransactions::TYPE_SPACE:
                if ($this->getIsCreateTransaction()) {
                    return pht(
                        '%s created %s in the %s space.',
                        $this->renderHandleLink($author_phid),
                        $this->renderHandleLink($object_phid),
                        $this->renderHandleLink($new));
                } else {
                    return pht(
                        '%s shifted %s from the %s space to the %s space.',
                        $this->renderHandleLink($author_phid),
                        $this->renderHandleLink($object_phid),
                        $this->renderHandleLink($old),
                        $this->renderHandleLink($new));
                }
            case PhabricatorTransactions::TYPE_EDGE:
                $record = PhabricatorEdgeChangeRecord::newFromTransaction($this);
                $add = $record->getAddedPHIDs();
                $rem = $record->getRemovedPHIDs();

                $type = $this->getMetadata('edge:type');
                $type = head($type);

                $type_obj = PhabricatorEdgeType::getByConstant($type);

                if ($add && $rem) {
                    return $type_obj->getFeedEditString(
                        $this->renderHandleLink($author_phid),
                        $this->renderHandleLink($object_phid),
                        new PhutilNumber(count($add) + count($rem)),
                        phutil_count($add),
                        $this->renderHandleList($add),
                        phutil_count($rem),
                        $this->renderHandleList($rem));
                } else if ($add) {
                    return $type_obj->getFeedAddString(
                        $this->renderHandleLink($author_phid),
                        $this->renderHandleLink($object_phid),
                        phutil_count($add),
                        $this->renderHandleList($add));
                } else if ($rem) {
                    return $type_obj->getFeedRemoveString(
                        $this->renderHandleLink($author_phid),
                        $this->renderHandleLink($object_phid),
                        phutil_count($rem),
                        $this->renderHandleList($rem));
                } else {
                    return pht(
                        '%s edited edge metadata for %s.',
                        $this->renderHandleLink($author_phid),
                        $this->renderHandleLink($object_phid));
                }

            case PhabricatorTransactions::TYPE_CUSTOMFIELD:
                $field = $this->getTransactionCustomField();
                if ($field) {
                    return $field->getApplicationTransactionTitleForFeed($this);
                } else {
                    return pht(
                        '%s edited a custom field on %s.',
                        $this->renderHandleLink($author_phid),
                        $this->renderHandleLink($object_phid));
                }

            case PhabricatorTransactions::TYPE_COLUMNS:
                $moves = $this->getInterestingMoves($new);
                if (count($moves) == 1) {
                    $move = head($moves);
                    $from_columns = $move['fromColumnPHIDs'];
                    $to_column = $move['columnPHID'];
                    $board_phid = $move['boardPHID'];
                    if (count($from_columns) == 1) {
                        return pht(
                            '%s moved %s from %s to %s on the %s board.',
                            $this->renderHandleLink($author_phid),
                            $this->renderHandleLink($object_phid),
                            $this->renderHandleLink(head($from_columns)),
                            $this->renderHandleLink($to_column),
                            $this->renderHandleLink($board_phid));
                    } else {
                        return pht(
                            '%s moved %s to %s on the %s board.',
                            $this->renderHandleLink($author_phid),
                            $this->renderHandleLink($object_phid),
                            $this->renderHandleLink($to_column),
                            $this->renderHandleLink($board_phid));
                    }
                } else {
                    $fragments = array();
                    foreach ($moves as $move) {
                        $from_columns = $move['fromColumnPHIDs'];
                        $to_column = $move['columnPHID'];
                        $board_phid = $move['boardPHID'];

                        $fragments[] = pht(
                            '%s (%s)',
                            $this->renderHandleLink($board_phid),
                            $this->renderHandleLink($to_column));
                    }

                    return pht(
                        '%s moved %s on %s board(s): %s.',
                        $this->renderHandleLink($author_phid),
                        $this->renderHandleLink($object_phid),
                        phutil_count($moves),
                        phutil_implode_html(', ', $fragments));
                }
                break;

            case PhabricatorTransactions::TYPE_MFA:
                return null;

        }

        return $this->getTitle();
    }

    /**
     * @param PhabricatorFeedStory $story
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function getMarkupFieldsForFeed(PhabricatorFeedStory $story)
    {
        $fields = array();

        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_COMMENT:
                $text = $this->getComment()->getContent();
                if (strlen($text)) {
                    $fields[] = 'comment/' . $this->getID();
                }
                break;
        }

        return $fields;
    }

    /**
     * @param PhabricatorFeedStory $story
     * @param $field
     * @return null
     * @throws Exception
     * @author 陈妙威
     */
    public function getMarkupTextForFeed(PhabricatorFeedStory $story, $field)
    {
        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_COMMENT:
                $text = $this->getComment()->getContent();
                return PhabricatorMarkupEngine::summarize($text);
        }

        return null;
    }

    /**
     * @param PhabricatorFeedStory $story
     * @return mixed|null|PHUIRemarkupView
     * @throws PhutilJSONParserException
     * @throws Exception
     * @author 陈妙威
     */
    public function getBodyForFeed(PhabricatorFeedStory $story)
    {
        $remarkup = $this->getRemarkupBodyForFeed($story);
        if ($remarkup !== null) {
            $remarkup = PhabricatorMarkupEngine::summarize($remarkup);
            return new PHUIRemarkupView($this->viewer, $remarkup);
        }

        $old = $this->getOldValue();
        $new = $this->getNewValue();

        $body = null;

        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_COMMENT:
                $text = $this->getComment()->getContent();
                if (strlen($text)) {
                    $body = $story->getMarkupFieldOutput('comment/' . $this->getID());
                }
                break;
        }

        return $body;
    }

    /**
     * @param PhabricatorFeedStory $story
     * @return null
     * @author 陈妙威
     */
    public function getRemarkupBodyForFeed(PhabricatorFeedStory $story)
    {
        return null;
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getActionName()
    {
        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_COMMENT:
                return pht('Commented On');
            case PhabricatorTransactions::TYPE_VIEW_POLICY:
            case PhabricatorTransactions::TYPE_EDIT_POLICY:
            case PhabricatorTransactions::TYPE_JOIN_POLICY:
                return pht('Changed Policy');
            case PhabricatorTransactions::TYPE_SUBSCRIBERS:
                return pht('Changed Subscribers');
            default:
                return pht('Updated');
        }
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getMailTags()
    {
        return array();
    }


    /**
     * @return bool
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function hasChangeDetailsForMail()
    {
        return $this->hasChangeDetails();
    }

    /**
     * @param PhabricatorUser $viewer
     * @return null
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function renderChangeDetailsForMail(PhabricatorUser $viewer)
    {
        $view = $this->renderChangeDetails($viewer);
        if ($view instanceof PhabricatorApplicationTransactionTextDiffDetailView) {
            return $view->renderForMail();
        }
        return null;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return null
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function renderChangeDetails(PhabricatorUser $viewer)
    {
        switch ($this->getTransactionType()) {
            case PhabricatorTransactions::TYPE_CUSTOMFIELD:
                $field = $this->getTransactionCustomField();
                if ($field) {
                    return $field->getApplicationTransactionChangeDetails($this, $viewer);
                }
                break;
        }

        return $this->renderTextCorpusChangeDetails(
            $viewer,
            $this->getOldValue(),
            $this->getNewValue());
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $old
     * @param $new
     * @return mixed
     * @author 陈妙威
     */
    public function renderTextCorpusChangeDetails(
        PhabricatorUser $viewer,
        $old,
        $new)
    {
        return (new PhabricatorApplicationTransactionTextDiffDetailView())
            ->setUser($viewer)
            ->setOldText($old)
            ->setNewText($new);
    }


    /**
     * @param array $moves
     * @return array
     * @author 陈妙威
     */
    private function getInterestingMoves(array $moves)
    {
        // Remove moves which only shift the position of a task within a column.
        foreach ($moves as $key => $move) {
            $from_phids = array_fuse($move['fromColumnPHIDs']);
            if (isset($from_phids[$move['columnPHID']])) {
                unset($moves[$key]);
            }
        }

        return $moves;
    }

    /**
     * @return array
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    private function getInterestingInlineStateChangeCounts()
    {
        // See PHI995. Newer inline state transactions have additional details
        // which we use to tailor the rendering behavior. These details are not
        // present on older transactions.
        $details = $this->getMetadataValue('inline.details', array());

        $new = $this->getNewValue();

        $done = 0;
        $undone = 0;
        foreach ($new as $phid => $state) {
            $is_done = ($state == PhabricatorInlineCommentInterface::STATE_DONE);

            // See PHI995. If you're marking your own inline comments as "Done",
            // don't count them when rendering a timeline story. In the case where
            // you're only affecting your own comments, this will hide the
            // "alice marked X comments as done" story entirely.

            // Usually, this happens when you pre-mark inlines as "done" and submit
            // them yourself. We'll still generate an "alice added inline comments"
            // story (in most cases/contexts), but the state change story is largely
            // just clutter and slightly confusing/misleading.

            $inline_details = ArrayHelper::getValue($details, $phid, array());
            $inline_author_phid = ArrayHelper::getValue($inline_details, 'authorPHID');
            if ($inline_author_phid) {
                if ($inline_author_phid == $this->getAuthorPHID()) {
                    if ($is_done) {
                        continue;
                    }
                }
            }

            if ($is_done) {
                $done++;
            } else {
                $undone++;
            }
        }

        return array($done, $undone);
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getModularType()
    {
        return null;
    }

    /**
     * @param array $phids
     * @return $this
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function setForceNotifyPHIDs(array $phids)
    {
        $this->setMetadataValue('notify.force', $phids);
        return $this;
    }

    /**
     * @return mixed
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function getForceNotifyPHIDs()
    {
        return $this->getMetadataValue('notify.force', array());
    }


    /* -(  PhabricatorDestructibleInterface  )----------------------------------- */


    /**
     * @param PhabricatorDestructionEngine $engine
     * @throws PhutilMethodNotImplementedException
     * @throws \Throwable
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function destroyObjectPermanently(
        PhabricatorDestructionEngine $engine)
    {

        $this->openTransaction();
        $comment_template = $this->getApplicationTransactionCommentObject();

        if ($comment_template) {
            $comments = $comment_template::find()->andWhere(['transaction_phid' => $this->getPHID()])->all();
            foreach ($comments as $comment) {
                $engine->destroyObject($comment);
            }
        }

        $this->delete();
        $this->saveTransaction();
    }
}