<?php

namespace orangins\modules\people\xaction;

use orangins\modules\people\models\PhabricatorUserLog;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorUserEmpowerTransaction
 * @package orangins\modules\people\xaction
 * @author 陈妙威
 */
final class PhabricatorUserEmpowerTransaction
    extends PhabricatorUserTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'user.admin';

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return (bool)$object->getIsAdmin();
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
        $object->setIsAdmin((int)$value);
    }

    /**
     * @param $object
     * @param $value
     * @author 陈妙威
     */
    public function applyExternalEffects($object, $value)
    {
        $user = $object;

        $this->newUserLog(PhabricatorUserLog::ACTION_ADMIN)
            ->setOldValue($this->getOldValue())
            ->setNewValue($value)
            ->save();
    }

    /**
     * @param $object
     * @param array $xactions
     * @return array
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        $user = $object;
        $actor = $this->getActor();

        $errors = array();
        foreach ($xactions as $xaction) {
            $old = $xaction->getOldValue();
            $new = $xaction->getNewValue();

            if ($old === $new) {
                continue;
            }

            if ($user->getPHID() === $actor->getPHID()) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",'After a time, your efforts fail. You can not adjust your own ' .
                        'status as an administrator.'), $xaction);
            }

            $is_admin = $actor->getIsAdmin();
            $is_omnipotent = $actor->isOmnipotent();

            if (!$is_admin && !$is_omnipotent) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",'You must be an administrator to create administrators.'),
                    $xaction);
            }
        }

        return $errors;
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
                '%s empowered this user as an administrator.',
                $this->renderAuthor());
        } else {
            return \Yii::t("app",
                '%s defrocked this user.',
                $this->renderAuthor());
        }
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        $new = $this->getNewValue();
        if ($new) {
            return \Yii::t("app",
                '%s empowered %s as an administrator.',
                $this->renderAuthor(),
                $this->renderObject());
        } else {
            return \Yii::t("app",
                '%s defrocked %s.',
                $this->renderAuthor(),
                $this->renderObject());
        }
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

        // Unlike normal user edits, admin promotions require admin
        // permissions, which is enforced by validateTransactions().

        return null;
    }
}
