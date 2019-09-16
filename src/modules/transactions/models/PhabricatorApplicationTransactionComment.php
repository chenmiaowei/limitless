<?php

namespace orangins\modules\transactions\models;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\markup\PhabricatorMarkupEngine;
use orangins\lib\markup\PhabricatorMarkupInterface;
use orangins\lib\request\AphrontRequest;
use orangins\modules\people\db\ActiveRecordAuthorTrait;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorPHIDConstants;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\system\interfaces\PhabricatorDestructibleInterface;
use PhutilMarkupEngine;
use Yii;

/**
 * @property string $transaction_phid
 * @property string $author_phid
 * @property string $comment_version
 * @property string $view_policy
 * @property string $edit_policy
 * @property string $content
 * @property string $content_source
 * @property string $is_deleted
 * Class PhabricatorApplicationTransactionComment
 * @package orangins\modules\transactions\models
 * @author 陈妙威
 */
abstract class PhabricatorApplicationTransactionComment
    extends ActiveRecordPHID
    implements
    PhabricatorMarkupInterface,
    PhabricatorPolicyInterface,
    PhabricatorDestructibleInterface
{
    use ActiveRecordAuthorTrait;

    /**
     *
     */
    const MARKUP_FIELD_COMMENT = 'markup:comment';


    /**
     * @var string
     */
    private $oldComment = self::ATTACHABLE;



    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phid', 'author_phid', 'view_policy', 'edit_policy', 'content', 'content_source', 'is_deleted'], 'required'],
            [['comment_version', 'is_deleted'], 'integer'],
            [['content', 'content_source'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'transaction_phid', 'author_phid', 'view_policy', 'edit_policy'], 'string', 'max' => 64],
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
            'transaction_phid' => Yii::t('app', 'Transaction PHID'),
            'author_phid' => Yii::t('app', '作者'),
            'view_policy' => Yii::t('app', '显示权限'),
            'edit_policy' => Yii::t('app', '编辑权限'),
            'comment_version' => Yii::t('app', '评论版本'),
            'content' => Yii::t('app', 'Content'),
            'content_source' => Yii::t('app', 'Content Source'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getApplicationTransactionObject();

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    public function generatePHID()
    {
        return PhabricatorPHID::generateNewPHID(
            PhabricatorPHIDConstants::PHID_TYPE_XCMT);
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return $this->getApplicationTransactionObject()->getApplicationName();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTableName()
    {
        $xaction = $this->getApplicationTransactionObject();
        return self::getTableNameFromTransaction($xaction);
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return string
     * @author 陈妙威
     */
    public static function getTableNameFromTransaction(
        PhabricatorApplicationTransaction $xaction)
    {
        return $xaction::tableName() . '_comment';
    }

    /**
     * @param PhabricatorContentSource $content_source
     * @return $this
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function setContentSource(PhabricatorContentSource $content_source)
    {
        $this->content_source = $content_source->serialize();
        return $this;
    }

    /**
     * @param AphrontRequest $request
     * @return PhabricatorApplicationTransactionComment
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function setContentSourceFromRequest(AphrontRequest $request)
    {
        return $this->setContentSource(
            PhabricatorContentSource::newFromRequest($request));
    }

    /**
     * @return PhabricatorContentSource
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getContentSource()
    {
        return PhabricatorContentSource::newFromSerialized($this->content_source);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIsRemoved()
    {
        return ($this->getIsDeleted() == 2);
    }

    /**
     * @param $removed
     * @return $this
     * @author 陈妙威
     */
    public function setIsRemoved($removed)
    {
        if ($removed) {
            $this->setIsDeleted(2);
        } else {
            $this->setIsDeleted(0);
        }
        return $this;
    }

    /**
     * @param PhabricatorApplicationTransactionComment $old_comment
     * @return $this
     * @author 陈妙威
     */
    public function attachOldComment(
        PhabricatorApplicationTransactionComment $old_comment)
    {
        $this->oldComment = $old_comment;
        return $this;
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getOldComment()
    {
        return $this->assertAttached($this->oldComment);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function hasOldComment()
    {
        return ($this->oldComment !== self::ATTACHABLE);
    }


    /* -(  PhabricatorMarkupInterface  )----------------------------------------- */


    /**
     * @param $field
     * @return string
     * @author 陈妙威
     */
    public function getMarkupFieldKey($field)
    {
        return PhabricatorPHIDConstants::PHID_TYPE_XCMT . ':' . $this->getPHID();
    }


    /**
     * @param $field
     * @return mixed|null|\PhutilRemarkupEngine
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function newMarkupEngine($field)
    {
        return PhabricatorMarkupEngine::getEngine();
    }


    /**
     * @param $field
     * @return string
     * @author 陈妙威
     */
    public function getMarkupText($field)
    {
        return $this->getContent();
    }


    /**
     * @param $field
     * @param $output
     * @param PhutilMarkupEngine $engine
     * @return string
     * @author 陈妙威
     * @throws \Exception
     */
    public function didMarkupText($field, $output, PhutilMarkupEngine $engine)
    {
        return phutil_tag(
            'div',
            array(
                'class' => 'phabricator-remarkup',
            ),
            $output);
    }


    /**
     * @param $field
     * @return bool
     * @author 陈妙威
     */
    public function shouldUseMarkupCache($field)
    {
        return (bool)$this->getPHID();
    }

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
     * @return mixed
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
     * @throws \yii\base\UnknownPropertyException
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return ($viewer->getPHID() == $this->getAuthorPHID());
    }

    /**
     * @param $capability
     * @return mixed
     * @author 陈妙威
     */
    public function describeAutomaticCapability($capability)
    {
        return \Yii::t("app",
            'Comments are visible to users who can see the object which was ' .
            'commented on. Comments can be edited by their authors.');
    }


    /* -(  PhabricatorDestructibleInterface  )----------------------------------- */

    /**
     * @param PhabricatorDestructionEngine $engine
     * @return mixed|void
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function destroyObjectPermanently(
        PhabricatorDestructionEngine $engine)
    {
        $this->openTransaction();
        $this->delete();
        $this->saveTransaction();
    }

    /**
     * @return string
     */
    public function getisDeleted()
    {
        return $this->is_deleted;
    }

    /**
     * @param string $is_deleted
     * @return self
     */
    public function setIsDeleted($is_deleted)
    {
        $this->is_deleted = $is_deleted;
        return $this;
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
     * @return self
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
     * @return self
     */
    public function setEditPolicy($edit_policy)
    {
        $this->edit_policy = $edit_policy;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return self
     */
    public function setContent($content)
    {
        $this->content = (string)$content;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionPHID()
    {
        return $this->transaction_phid;
    }

    /**
     * @param string $transaction_phid
     * @return self
     */
    public function setTransactionPHID($transaction_phid)
    {
        $this->transaction_phid = $transaction_phid;
        return $this;
    }
}
