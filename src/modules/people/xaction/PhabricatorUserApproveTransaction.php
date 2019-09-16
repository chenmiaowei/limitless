<?php

namespace orangins\modules\people\xaction;

use orangins\lib\env\PhabricatorEnv;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\people\models\PhabricatorUserLog;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorUserApproveTransaction
 * @package orangins\modules\people\xaction
 * @author 陈妙威
 */
final class PhabricatorUserApproveTransaction
    extends PhabricatorUserTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'user.approve';

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return (bool)$object->getIsApproved();
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
        $object->setIsApproved((int)$value);
    }

    /**
     * @param $object
     * @param $value
     * @throws \AphrontQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function applyExternalEffects($object, $value)
    {
        $user = $object;
        $this->newUserLog(PhabricatorUserLog::ACTION_APPROVE)
            ->setOldValue((bool)$user->getIsApproved())
            ->setNewValue((bool)$value)
            ->save();

        $actor = $this->getActor();
        $title = \Yii::t("app",
            'Phabricator Account "%s" Approved',
            $user->getUsername());

        $body = sprintf(
            "%s\n\n  %s\n\n",
            \Yii::t("app",
                'Your Phabricator account (%s) has been approved by %s. You can ' .
                'login here:',
                $user->getUsername(),
                $actor->getUsername()),
            PhabricatorEnv::getProductionURI('/'));

        $mail = (new PhabricatorMetaMTAMail())
            ->addTos(array($user->getPHID()))
            ->addCCs(array($actor->getPHID()))
            ->setSubject('[Phabricator] ' . $title)
            ->setForceDelivery(true)
            ->setBody($body)
            ->saveAndSend();
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
                '%s approved this user.',
                $this->renderAuthor());
        } else {
            return \Yii::t("app",
                '%s rejected this user.',
                $this->renderAuthor());
        }
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
     * @param $object
     * @param array $xactions
     * @return array
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        $actor = $this->getActor();
        $errors = array();

        foreach ($xactions as $xaction) {
            $is_approved = (bool)$object->getIsApproved();

            if ((bool)$xaction->getNewValue() === $is_approved) {
                continue;
            }

            if (!$actor->getIsAdmin()) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",'You must be an administrator to approve users.'));
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

        // Unlike normal user edits, approvals require admin permissions, which
        // is enforced by validateTransactions().

        return null;
    }
}
