<?php

namespace orangins\modules\transactions\models;

use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\phid\PhabricatorEditEngineConfigurationPHIDType;
use orangins\modules\transactions\query\PhabricatorEditEngineConfigurationTransactionQuery;

/**
 * Class PhabricatorEditEngineConfigurationTransaction
 * @package orangins\modules\transactions\models
 * @author 陈妙威
 */
final class PhabricatorEditEngineConfigurationTransaction
    extends PhabricatorApplicationTransaction
{

    /**
     *
     */
    const TYPE_NAME = 'editengine.config.name';
    /**
     *
     */
    const TYPE_PREAMBLE = 'editengine.config.preamble';
    /**
     *
     */
    const TYPE_ORDER = 'editengine.config.order';
    /**
     *
     */
    const TYPE_DEFAULT = 'editengine.config.default';
    /**
     *
     */
    const TYPE_LOCKS = 'editengine.config.locks';
    /**
     *
     */
    const TYPE_DEFAULTCREATE = 'editengine.config.default.create';
    /**
     *
     */
    const TYPE_ISEDIT = 'editengine.config.isedit';
    /**
     *
     */
    const TYPE_DISABLE = 'editengine.config.disable';
    /**
     *
     */
    const TYPE_CREATEORDER = 'editengine.order.create';
    /**
     *
     */
    const TYPE_EDITORDER = 'editengine.order.edit';
    /**
     *
     */
    const TYPE_SUBTYPE = 'editengine.config.subtype';

    /**
     * @return string
     * @author 陈妙威
     */
    public static function tableName()
    {
        return "search_editengineconfigurationtransaction";
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'search';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorEditEngineConfigurationPHIDType::TYPECONST;
    }

    /**
     * @return null|void
     * @author 陈妙威
     */
    public function getApplicationTransactionCommentObject()
    {
        return null;
    }

    /**
     * @return string
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException

     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getTitle()
    {
        $author_phid = $this->getAuthorPHID();

        $old = $this->getOldValue();
        $new = $this->getNewValue();

        $type = $this->getTransactionType();
        switch ($type) {
            case PhabricatorTransactions::TYPE_CREATE:
                return \Yii::t("app",
                    '%s created this form configuration.',
                    $this->renderHandleLink($author_phid));
            case self::TYPE_NAME:
                if (strlen($old)) {
                    return \Yii::t("app",
                        '%s renamed this form from "%s" to "%s".',
                        $this->renderHandleLink($author_phid),
                        $old,
                        $new);
                } else {
                    return \Yii::t("app",
                        '%s named this form "%s".',
                        $this->renderHandleLink($author_phid),
                        $new);
                }
            case self::TYPE_PREAMBLE:
                return \Yii::t("app",
                    '%s updated the preamble for this form.',
                    $this->renderHandleLink($author_phid));
            case self::TYPE_ORDER:
                return \Yii::t("app",
                    '%s reordered the fields in this form.',
                    $this->renderHandleLink($author_phid));
            case self::TYPE_DEFAULT:
                $key = $this->getMetadataValue('field.key');
                return \Yii::t("app",
                    '%s changed the default value for field "%s".',
                    $this->renderHandleLink($author_phid),
                    $key);
            case self::TYPE_LOCKS:
                return \Yii::t("app",
                    '%s changed locked and hidden fields.',
                    $this->renderHandleLink($author_phid));
            case self::TYPE_DEFAULTCREATE:
                if ($new) {
                    return \Yii::t("app",
                        '%s added this form to the "Create" menu.',
                        $this->renderHandleLink($author_phid));
                } else {
                    return \Yii::t("app",
                        '%s removed this form from the "Create" menu.',
                        $this->renderHandleLink($author_phid));
                }
            case self::TYPE_ISEDIT:
                if ($new) {
                    return \Yii::t("app",
                        '%s marked this form as an edit form.',
                        $this->renderHandleLink($author_phid));
                } else {
                    return \Yii::t("app",
                        '%s unmarked this form as an edit form.',
                        $this->renderHandleLink($author_phid));
                }
            case self::TYPE_DISABLE:
                if ($new) {
                    return \Yii::t("app",
                        '%s disabled this form.',
                        $this->renderHandleLink($author_phid));
                } else {
                    return \Yii::t("app",
                        '%s enabled this form.',
                        $this->renderHandleLink($author_phid));
                }
            case self::TYPE_SUBTYPE:
                return \Yii::t("app",
                    '%s changed the subtype of this form from "%s" to "%s".',
                    $this->renderHandleLink($author_phid),
                    $old,
                    $new);
        }

        return parent::getTitle();
    }

    /**
     * @return null|string
     * @throws \PhutilJSONParserException

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getColor()
    {
        $author_phid = $this->getAuthorPHID();

        $old = $this->getOldValue();
        $new = $this->getNewValue();

        $type = $this->getTransactionType();
        switch ($type) {
            case PhabricatorTransactions::TYPE_CREATE:
                return 'green';
            case self::TYPE_DISABLE:
                if ($new) {
                    return 'indigo';
                } else {
                    return 'green';
                }
        }

        return parent::getColor();
    }

    /**
     * @return string
     * @throws \PhutilJSONParserException

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getIcon()
    {
        $author_phid = $this->getAuthorPHID();

        $old = $this->getOldValue();
        $new = $this->getNewValue();

        $type = $this->getTransactionType();
        switch ($type) {
            case PhabricatorTransactions::TYPE_CREATE:
                return 'fa-plus';
            case self::TYPE_DISABLE:
                if ($new) {
                    return 'fa-ban';
                } else {
                    return 'fa-check';
                }
        }

        return parent::getIcon();
    }

    /**
     * @return PhabricatorEditEngineConfigurationTransactionQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorEditEngineConfigurationTransactionQuery(get_called_class());
    }
}
