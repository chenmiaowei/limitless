<?php

namespace orangins\modules\people\models;

use orangins\modules\people\phid\PhabricatorPeopleUserPHIDType;
use orangins\modules\people\query\PhabricatorPeopleTransactionQuery;
use orangins\modules\people\xaction\PhabricatorUserTransactionType;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\models\PhabricatorModularTransaction;
use Yii;

/**
 * This is the model class for table "admin_transactions".
 *
 * @property int $id
 * @property string $phid
 * @property string $object_phid 对象ID
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
class PhabricatorUserTransaction extends PhabricatorModularTransaction
{
    /**
     *
     */
    const TYPE_REAL_NAME = 'admin:real_name';
    /**
     *
     */
    const TYPE_TITLE = 'admin:title';
    /**
     *
     */
    const TYPE_ICON = 'admin:icon';
    /**
     *
     */
    const TYPE_BLURB = 'admin:blurb';
    /**
     *
     */
    const TYPE_IMAGE = 'admin:image';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_transactions';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'phid' => Yii::t('app', 'Phid'),
            'object_phid' => Yii::t('app', '对象ID'),
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
        $object_phid = $this->getObjectPHID();

        $old = $this->getOldValue();
        $new = $this->getNewValue();

        $author_link = $this->renderHandleLink($author_phid);

        $type = $this->getTransactionType();
        switch ($type) {
            case self::TYPE_ICON:
                if (!strlen($old)) {
                    return Yii::t("app",
                        '{0} set the dashboard icon.',
                        $author_link);
                } else {
                    return Yii::t("app",
                        '{0} changed this dashboard icon from "{1}" to "{2}".',
                        [
                            $author_link,
                            $old,
                            $new
                        ]);
                }
                break;
            case self::TYPE_TITLE:
                return Yii::t("app",
                    '{0} changed this title from "{1}" to "{2}".',
                    [
                        $author_link,
                        $old,
                        $new
                    ]);
                break;
            case self::TYPE_REAL_NAME:
                return Yii::t("app",
                    '{0} changed this real name from "{1}" to "{2}".',
                    [
                        $author_link,
                        $old,
                        $new
                    ]);
                break;
            case self::TYPE_BLURB:
                return Yii::t("app",
                    '{0} changed this blurb.',
                    [
                        $author_link,
                    ]);
                break;
            case self::TYPE_IMAGE:
                return Yii::t("app",
                    '{0} changed this image.',
                    [
                        $author_link,
                    ]);
                break;

        }
        return parent::getTitle();
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
            /** @var PhabricatorPHIDType $PHIDType */
            $PHIDType = \Yii::createObject($this->getPHIDTypeClassName());
            $this->setAttribute("phid", PhabricatorPHID::generateNewPHID($PHIDType->getTypeConstant() . "-USER"));
        }
        return parent::beforeSave($insert);
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
        return PhabricatorPeopleUserPHIDType::TYPECONST;
    }

    /**
     * @return PhabricatorPeopleTransactionQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorPeopleTransactionQuery(get_called_class());
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBaseTransactionClass()
    {
        return PhabricatorUserTransactionType::className();
    }
}
