<?php

namespace orangins\modules\people\xaction;

use orangins\modules\people\cache\PhabricatorUserRbacCacheType;
use orangins\modules\people\capability\PeopleDisableUsersCapability;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserCache;
use orangins\modules\people\models\PhabricatorUserLog;
use orangins\modules\rbac\models\RbacUser;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorUserDisableTransaction
 * @package orangins\modules\people\xaction
 * @author 陈妙威
 */
final class PhabricatorUserAddRbacNodeTransaction extends PhabricatorUserTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'user.rbacnode';

    /**
     * @param PhabricatorUser $object
     * @author 陈妙威
     * @return array
     * @throws \ReflectionException
     */
    public function generateOldValue($object)
    {
        $rbacSettings = $object->getRbacSettings();
        $oldNodes = ArrayHelper::getValue($rbacSettings, 'user.nodes', []);
        return $oldNodes;
    }


    /**
     * @param PhabricatorUser $object
     * @param $value
     * @author 陈妙威
     * @throws \Exception
     */
    public function applyExternalEffects($object, $value)
    {
        $value = $value === null ? [] : $value;

        $rbacSettings = $object->getRbacSettings();
        $objectPHIDs = ArrayHelper::getValue($rbacSettings, 'user.nodes', []);


        $add = [];
        $rem = [];
        foreach ($objectPHIDs as $objectPHID) {
            if (!in_array($objectPHID, $value)) {
                $rem[] = $objectPHID;
            }
        }
        foreach ($value as $item) {
            if (!in_array($item, $objectPHIDs)) {
                $add[] = $item;
            }
        }
        RbacUser::deleteAll([
            'IN', 'object_phid', $rem
        ]);

        foreach ($add as $item) {
            $arr = [
                'object_phid' => $item,
                'user_phid' => $object->getPHID(),
            ];
            \Yii::$app->getDb()->createCommand()->upsert(RbacUser::tableName(), $arr, [
                'object_phid' => new \yii\db\Expression('VALUES(object_phid)'),
                'user_phid' => new \yii\db\Expression('VALUES(user_phid)'),
            ])->execute();
        }

        $notification_key = PhabricatorUserRbacCacheType::KEY_PREFERENCES;
        PhabricatorUserCache::clearCache($notification_key, $object->getPHID());
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTitle()
    {
        $new = $this->getNewValue();
        if ($new) {
            return \Yii::t("app",
                '%s edit this user.',
                $this->renderAuthor());
        } else {
            return \Yii::t("app",
                '%s edit this user.',
                $this->renderAuthor());
        }
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldHideForFeed()
    {
        // Don't publish feed stories about disabling users, since this can be
        // a sensitive action.
        return true;
    }

    /**
     * @param $object
     * @param array $xactions
     * @return array
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        $errors = array();
        return $errors;
    }

    /**
     * @param $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return null
     * @author 陈妙威
     */
    public function getRequiredCapabilities(
        $object,
        PhabricatorApplicationTransaction $xaction)
    {

        // You do not need to be able to edit users to disable them. Instead, this
        // requirement is replaced with a requirement that you have the "Can
        // Disable Users" permission.

        return null;
    }
}
