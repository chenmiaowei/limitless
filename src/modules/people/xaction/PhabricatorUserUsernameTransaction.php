<?php

namespace orangins\modules\people\xaction;

use orangins\modules\auth\query\PhabricatorAuthSSHKeyQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserLog;
use orangins\modules\people\query\PhabricatorPeopleQuery;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorUserUsernameTransaction
 * @package orangins\modules\people\xaction
 * @author 陈妙威
 */
final class PhabricatorUserUsernameTransaction
    extends PhabricatorUserTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'user.rename';

    /**
     * @param $object
     * @author 陈妙威
     * @return
     */
    public function generateOldValue($object)
    {
        return $object->getUsername();
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
     * @param $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setUsername($value);
    }

    /**
     * @param $object
     * @param $value
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function applyExternalEffects($object, $value)
    {
        $actor = $this->getActor();
        $user = $object;

        $old_username = $this->getOldValue();
        $new_username = $this->getNewValue();

        $this->newUserLog(PhabricatorUserLog::ACTION_CHANGE_USERNAME)
            ->setOldValue($old_username)
            ->setNewValue($new_username)
            ->save();

        // The SSH key cache currently includes usernames, so dirty it. See T12554
        // for discussion.
        PhabricatorAuthSSHKeyQuery::deleteSSHKeyCache();

        (new PhabricatorPeopleUsernameMailEngine())
            ->setSender($actor)
            ->setRecipient($object)
            ->setOldUsername($old_username)
            ->setNewUsername($new_username)
            ->sendMail();
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getTitle()
    {
        return \Yii::t("app",
            '%s renamed this user from %s to %s.',
            $this->renderAuthor(),
            $this->renderOldValue(),
            $this->renderNewValue());
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        return \Yii::t("app",
            '%s renamed %s from %s to %s.',
            $this->renderAuthor(),
            $this->renderObject(),
            $this->renderOldValue(),
            $this->renderNewValue());
    }

    /**
     * @param $object
     * @param array $xactions
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        $actor = $this->getActor();
        $errors = array();

        foreach ($xactions as $xaction) {
            $new = $xaction->getNewValue();
            $old = $xaction->getOldValue();

            if ($old === $new) {
                continue;
            }

            if (!$actor->getIsAdmin()) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",'You must be an administrator to rename users.'));
            }

            if (!strlen($new)) {
                $errors[] = $this->newRequiredError(
                    \Yii::t("app",'New username is required.'), $xaction);
            } else if (!PhabricatorUser::validateUsername($new)) {
                $errors[] = $this->newInvalidError(
                    PhabricatorUser::describeValidUsername(), $xaction);
            }

            $user = PhabricatorUser::find()
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withUsernames(array($new))
                ->executeOne();

            if ($user) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",'Another user already has that username.'), $xaction);
            }

        }

        return $errors;
    }

    /**
     * @param $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return null|string
     * @author 陈妙威
     */
    public function getRequiredCapabilities(
        $object,
        PhabricatorApplicationTransaction $xaction)
    {

        // Unlike normal user edits, renames require admin permissions, which
        // is enforced by validateTransactions().

        return null;
    }

    /**
     * @param $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return bool
     * @author 陈妙威
     */
    public function shouldTryMFA(
        $object,
        PhabricatorApplicationTransaction $xaction)
    {
        return true;
    }

}
