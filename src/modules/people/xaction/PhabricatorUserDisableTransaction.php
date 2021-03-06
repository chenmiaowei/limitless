<?php

namespace orangins\modules\people\xaction;

use orangins\modules\people\capability\PeopleDisableUsersCapability;
use orangins\modules\people\models\PhabricatorUserLog;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorUserDisableTransaction
 * @package orangins\modules\people\xaction
 * @author 陈妙威
 */
final class PhabricatorUserDisableTransaction
    extends PhabricatorUserTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'user.disable';

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return (bool)$object->getIsDisabled();
    }

    /**
     * @param $object
     * @param $value
     * @return bool
     * @author 陈妙威
     */
    public function generateNewValue($object, $value)
    {
        return (bool)$value;
    }

    /**
     * @param $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setIsDisabled((int)$value);
    }

    /**
     * @param $object
     * @param $value
     * @author 陈妙威
     */
    public function applyExternalEffects($object, $value)
    {
        $this->newUserLog(PhabricatorUserLog::ACTION_DISABLE)
            ->setOldValue((bool)$object->getIsDisabled())
            ->setNewValue((bool)$value)
            ->save();
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
                '%s disabled this user.',
                $this->renderAuthor());
        } else {
            return \Yii::t("app",
                '%s enabled this user.',
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

        foreach ($xactions as $xaction) {
            $is_disabled = (bool)$object->getIsDisabled();

            if ((bool)$xaction->getNewValue() === $is_disabled) {
                continue;
            }

            // You must have the "Can Disable Users" permission to disable a user.
            $this->requireApplicationCapability(
                PeopleDisableUsersCapability::CAPABILITY);

            if ($this->getActingAsPHID() === $object->getPHID()) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",'You can not enable or disable your own account.'));
            }
        }

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
