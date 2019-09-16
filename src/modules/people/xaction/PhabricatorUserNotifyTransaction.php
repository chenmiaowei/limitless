<?php

namespace orangins\modules\people\xaction;

/**
 * Class PhabricatorUserNotifyTransaction
 * @package orangins\modules\people\xaction
 * @author 陈妙威
 */
final class PhabricatorUserNotifyTransaction
    extends PhabricatorUserTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'notify';

    /**
     * @param $object
     * @return null
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return null;
    }

    /**
     * @param $object
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function generateNewValue($object, $value)
    {
        return $value;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTitle()
    {
        return \Yii::t("app",
            '%s sent this user a test notification.',
            $this->renderAuthor());
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        return $this->getNewValue();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldHideForNotifications()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldHideForFeed()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldHideForMail()
    {
        return true;
    }

}
