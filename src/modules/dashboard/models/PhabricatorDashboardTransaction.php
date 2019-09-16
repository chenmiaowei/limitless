<?php

namespace orangins\modules\dashboard\models;

use orangins\modules\dashboard\phid\PhabricatorDashboardDashboardPHIDType;
use orangins\modules\dashboard\query\PhabricatorDashboardTransactionQuery;
use orangins\modules\dashboard\xaction\dashboard\PhabricatorDashboardTransactionType;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\models\PhabricatorModularTransaction;
use Yii;

/**
 * This is the model class for table "dashboard_transactions".
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
class PhabricatorDashboardTransaction extends PhabricatorModularTransaction
{

    /**
     *
     */
    const TYPE_NAME = 'dashboard:name';
    /**
     *
     */
    const TYPE_ICON = 'dashboard:icon';
    /**
     *
     */
    const TYPE_LAYOUT_MODE = 'dashboard:layoutmode';
    /**
     *
     */
    const TYPE_STATUS = 'dashboard:status';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dashboard_transactions';
    }



    /**
     * @return mixed|string
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
            case self::TYPE_NAME:
                if (!strlen($old)) {
                    return Yii::t("app",
                        '{0} created this dashboard.', [
                            $author_link
                        ]);
                } else {
                    return Yii::t("app",
                        '{0} renamed this dashboard from "{1}" to "{2}".',
                        [
                            $author_link,
                            $old,
                            $new
                        ]);
                }
                break;
            case self::TYPE_ICON:
                if (!strlen($old)) {
                    return Yii::t("app",
                        '{0} set the dashboard icon.', [
                            $author_link
                        ]);
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
            case self::TYPE_STATUS:
                if ($new == PhabricatorDashboard::STATUS_ACTIVE) {
                    return Yii::t("app",
                        '{0} activated this dashboard.',
                        [$author_link]);
                } else {
                    return Yii::t("app",
                        '{0} archived this dashboard.',
                        [$author_link]);
                }
                break;
        }

        return parent::getTitle();
    }


    /**
     * @return PhabricatorDashboardTransactionQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorDashboardTransactionQuery(get_called_class());
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationTransactionType()
    {
       return PhabricatorDashboardDashboardPHIDType::TYPECONST;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBaseTransactionClass()
    {
        return PhabricatorDashboardTransactionType::className();
    }
}

