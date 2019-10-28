<?php

namespace orangins\modules\auth\models;

use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\modules\auth\phid\PhabricatorAuthAuthProviderPHIDType;
use orangins\modules\auth\provider\PhabricatorAuthProvider;
use orangins\modules\auth\query\PhabricatorAuthProviderConfigTransactionQuery;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use PhutilJSONParserException;
use ReflectionException;
use Yii;
use yii\base\InvalidConfigException;

/**
 * This is the model class for table "auth_providerconfigtransaction".
 *
 * @property int $id
 * @property string $phid
 * @property string $object_phid 对象_id
 * @property string $comment_phid 评论
 * @property int $comment_version 评论版本
 * @property string $transaction_type 类型
 * @property string $old_value 旧值
 * @property string $new_value 新值
 * @property string $content_source 内容
 * @property string $metadata 数据
 * @property string $author_phid 作者
 * @property string $view_policy 显示权限
 * @property string $edit_policy 编辑权限
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorAuthProviderConfigTransaction extends PhabricatorApplicationTransaction
{

    /**
     *
     */
    const TYPE_ENABLE = 'config:enable';
    /**
     *
     */
    const TYPE_LOGIN = 'config:login';
    /**
     *
     */
    const TYPE_REGISTRATION = 'config:registration';
    /**
     *
     */
    const TYPE_LINK = 'config:link';
    /**
     *
     */
    const TYPE_UNLINK = 'config:unlink';
    /**
     *
     */
    const TYPE_TRUST_EMAILS = 'config:trustEmails';
    /**
     *
     */
    const TYPE_AUTO_LOGIN = 'config:autoLogin';
    /**
     *
     */
    const TYPE_PROPERTY = 'config:property';

    /**
     *
     */
    const PROPERTY_KEY = 'auth:property';

    /**
     * @var
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_providerconfigtransaction';
    }

    /**
     * @return PhabricatorAuthProviderConfigTransactionQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorAuthProviderConfigTransactionQuery(get_called_class());
    }


    /**
     * @param PhabricatorAuthProvider $provider
     * @return $this
     * @author 陈妙威
     */
    public function setProvider(PhabricatorAuthProvider $provider)
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'auth';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorAuthAuthProviderPHIDType::TYPECONST;
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
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function getIcon()
    {
        $old = $this->getOldValue();
        $new = $this->getNewValue();

        switch ($this->getTransactionType()) {
            case self::TYPE_ENABLE:
                if ($new) {
                    return 'fa-check';
                } else {
                    return 'fa-ban';
                }
        }

        return parent::getIcon();
    }

    /**
     * @return string
     * @throws PhutilJSONParserException
     * @author 陈妙威
     */
    public function getColor()
    {
        $old = $this->getOldValue();
        $new = $this->getNewValue();

        switch ($this->getTransactionType()) {
            case self::TYPE_ENABLE:
                if ($new) {
                    return 'green';
                } else {
                    return 'indigo';
                }
        }

        return parent::getColor();
    }

    /**
     * @return mixed
     * @throws ReflectionException
     * @throws PhutilJSONParserException
     * @throws PhabricatorDataNotAttachedException
     * @throws InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function getTitle()
    {
        $author_phid = $this->getAuthorPHID();

        $old = $this->getOldValue();
        $new = $this->getNewValue();

        switch ($this->getTransactionType()) {
            case self::TYPE_ENABLE:
                if ($old === null) {
                    return Yii::t("app",
                        '{0} created this provider.', [
                            $this->renderHandleLink($author_phid)
                        ]);
                } else if ($new) {
                    return Yii::t("app",
                        '{0} enabled this provider.', [
                            $this->renderHandleLink($author_phid)
                        ]);
                } else {
                    return Yii::t("app",
                        '{0} disabled this provider.', [
                            $this->renderHandleLink($author_phid)
                        ]);
                }
                break;
            case self::TYPE_LOGIN:
                if ($new) {
                    return Yii::t("app",
                        '{0} enabled login.', [
                            $this->renderHandleLink($author_phid)
                        ]);
                } else {
                    return Yii::t("app",
                        '{0} disabled login.', [
                            $this->renderHandleLink($author_phid)
                        ]);
                }
                break;
            case self::TYPE_REGISTRATION:
                if ($new) {
                    return Yii::t("app",
                        '{0} enabled registration.', [
                            $this->renderHandleLink($author_phid)
                        ]);
                } else {
                    return Yii::t("app",
                        '{0} disabled registration.', [
                            $this->renderHandleLink($author_phid)
                        ]);
                }
                break;
            case self::TYPE_LINK:
                if ($new) {
                    return Yii::t("app",
                        '{0} enabled account linking.', [
                            $this->renderHandleLink($author_phid)
                        ]);
                } else {
                    return Yii::t("app",
                        '{0} disabled account linking.', [
                            $this->renderHandleLink($author_phid)
                        ]);
                }
                break;
            case self::TYPE_UNLINK:
                if ($new) {
                    return Yii::t("app",
                        '{0} enabled account unlinking.', [
                            $this->renderHandleLink($author_phid)
                        ]);
                } else {
                    return Yii::t("app",
                        '{0} disabled account unlinking.', [
                            $this->renderHandleLink($author_phid)
                        ]);
                }
                break;
            case self::TYPE_TRUST_EMAILS:
                if ($new) {
                    return Yii::t("app",
                        '{0} enabled email trust.', [
                            $this->renderHandleLink($author_phid)
                        ]);
                } else {
                    return Yii::t("app",
                        '{0} disabled email trust.', [
                            $this->renderHandleLink($author_phid)
                        ]);
                }
                break;
            case self::TYPE_AUTO_LOGIN:
                if ($new) {
                    return Yii::t("app",
                        '{0} enabled auto login.', [
                            $this->renderHandleLink($author_phid)
                        ]);
                } else {
                    return Yii::t("app",
                        '{0} disabled auto login.', [
                            $this->renderHandleLink($author_phid)
                        ]);
                }
                break;
            case self::TYPE_PROPERTY:
                $provider = $this->getProvider();
                if ($provider) {
                    $title = $provider->renderConfigPropertyTransactionTitle($this);
                    if (strlen($title)) {
                        return $title;
                    }
                }

                return Yii::t("app", '{0} edited a property of this provider.', [
                    $this->renderHandleLink($author_phid)
                ]);
                break;
        }

        return parent::getTitle();
    }
}
