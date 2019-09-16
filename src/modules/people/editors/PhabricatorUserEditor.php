<?php

namespace orangins\modules\people\editors;

use AphrontDuplicateKeyQueryException;
use orangins\lib\exception\ActiveRecordException;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\infrastructure\contentsource\PhabricatorWebContentSource;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\modules\auth\models\PhabricatorAuthTemporaryToken;
use orangins\modules\auth\query\PhabricatorAuthSSHKeyQuery;
use orangins\modules\auth\tokentype\PhabricatorAuthOneTimeLoginTemporaryTokenType;
use orangins\modules\auth\tokentype\PhabricatorAuthPasswordResetTemporaryTokenType;
use orangins\lib\editor\PhabricatorEditor;
use orangins\lib\events\constant\PhabricatorEventType;
use orangins\modules\metamta\models\PhabricatorMetaMTAApplicationEmail;
use orangins\modules\people\events\PeopleDidVerifyEmail;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserEmail;
use orangins\modules\people\models\PhabricatorUserLog;
use orangins\modules\people\models\PhabricatorUserTransaction;
use orangins\modules\people\xaction\PhabricatorUserAddRbacNodeTransaction;
use Yii;
use Exception;

/**
 * Editor class for creating and adjusting users. This class guarantees data
 * integrity and writes logs when user information changes.
 *
 * @task config     Configuration
 * @task edit       Creating and Editing Users
 * @task role       Editing Roles
 * @task email      Adding, Removing and Changing Emailp
 * @task internal   Internals
 */
final class PhabricatorUserEditor extends PhabricatorEditor
{

    /**
     * @var array
     */
    private $logs = array();


    /* -(  Creating and Editing Users  )----------------------------------------- */

    /**
     * @task edit
     * @param PhabricatorUser $user
     * @return PhabricatorUserEditor
     * @throws AphrontDuplicateKeyQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws Exception
     */
    public function createNewUserWithoutEmail(PhabricatorUser $user)
    {

        if ($user->getID()) {
            throw new Exception(\Yii::t("app", 'User has already been created!'));
        }


        if (!PhabricatorUser::validateUsername($user->getUsername())) {
            $valid = PhabricatorUser::describeValidUsername();
            throw new Exception(\Yii::t("app", 'Username is invalid! {0}', [$valid]));
        }


        $user->openTransaction();
        try {
            if(!$user->save()) {
                throw new ActiveRecordException("用户创建失败", $user->getErrorSummary(true));
            }
        } catch (AphrontDuplicateKeyQueryException $ex) {
            // We might have written the user but failed to write the email; if
            // so, erase the IDs we attached.
            $user->setID(null);
            $user->setPHID(null);

            $user->killTransaction();
            throw $ex;
        }

        $log = PhabricatorUserLog::initializeNewLog(
            $this->requireActor(),
            $user->getPHID(),
            PhabricatorUserLog::ACTION_CREATE);
        $log->setNewValue($user->getUsername());
        $log->save();


        $user->saveTransaction();

        return $this;
    }
    /**
     * @task edit
     * @param PhabricatorUser $user
     * @param PhabricatorUserEmail $email
     * @param bool $allow_reassign
     * @return PhabricatorUserEditor
     * @throws AphrontDuplicateKeyQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws Exception
     */
    public function createNewUser(
        PhabricatorUser $user,
        PhabricatorUserEmail $email,
        $allow_reassign = false)
    {

        if ($user->getID()) {
            throw new Exception(\Yii::t("app", 'User has already been created!'));
        }

        $is_reassign = false;
        if ($email->getID()) {
            if ($allow_reassign) {
                if ($email->getIsPrimary()) {
                    throw new Exception(
                        \Yii::t("app", 'Primary email addresses can not be reassigned.'));
                }
                $is_reassign = true;
            } else {
                throw new Exception(\Yii::t("app", 'Email has already been created!'));
            }
        }

        if (!PhabricatorUser::validateUsername($user->getUsername())) {
            $valid = PhabricatorUser::describeValidUsername();
            throw new Exception(\Yii::t("app", 'Username is invalid! {0}', [$valid]));
        }

        // Always set a new user's email address to primary.
        $email->setIsPrimary(1);

        // If the primary address is already verified, also set the verified flag
        // on the user themselves.
        if ($email->getIsVerified()) {
            $user->setIsEmailVerified(1);
        }

        $this->willAddEmail($email);

        $user->openTransaction();
        try {
            $user->save();
            $email->setUserPHID($user->getPHID());
            $email->save();
        } catch (AphrontDuplicateKeyQueryException $ex) {
            // We might have written the user but failed to write the email; if
            // so, erase the IDs we attached.
            $user->setID(null);
            $user->setPHID(null);

            $user->killTransaction();
            throw $ex;
        }

        $log = PhabricatorUserLog::initializeNewLog(
            $this->requireActor(),
            $user->getPHID(),
            PhabricatorUserLog::ACTION_CREATE);
        $log->setNewValue($email->getAddress());
        $log->save();

        if ($is_reassign) {
            $log = PhabricatorUserLog::initializeNewLog(
                $this->requireActor(),
                $user->getPHID(),
                PhabricatorUserLog::ACTION_EMAIL_REASSIGN);
            $log->setNewValue($email->getAddress());
            $log->save();
        }

        $user->saveTransaction();

        if ($email->getIsVerified()) {
            $this->didVerifyEmail($user, $email);
        }

        return $this;
    }


    /**
     * @task edit
     * @param PhabricatorUser $user
     * @param PhabricatorUserEmail|null $email
     * @return PhabricatorUserEditor
     * @throws Exception
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     */
    public function updateUser(
        PhabricatorUser $user,
        PhabricatorUserEmail $email = null)
    {

        if (!$user->getID()) {
            throw new Exception(\Yii::t("app", 'User has not been created yet!'));
        }

        $user->openTransaction();
        $user->save();
        if ($email) {
            $email->save();
        }

        $log = PhabricatorUserLog::initializeNewLog(
            $this->requireActor(),
            $user->getPHID(),
            PhabricatorUserLog::ACTION_EDIT);
        $log->save();

        $user->saveTransaction();

        return $this;
    }


    /**
     * @task edit
     * @param PhabricatorUser $user
     * @param $username
     * @throws AphrontDuplicateKeyQueryException
     * @throws Exception
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     */
    public function changeUsername(PhabricatorUser $user, $username)
    {
        $actor = $this->requireActor();

        if (!$user->getID()) {
            throw new Exception(\Yii::t("app", 'User has not been created yet!'));
        }

        if (!PhabricatorUser::validateUsername($username)) {
            $valid = PhabricatorUser::describeValidUsername();
            throw new Exception(\Yii::t("app", 'Username is invalid! %s', $valid));
        }

        $old_username = $user->getUsername();

        $user->openTransaction();
        $user->reload();
        $user->setUsername($username);

        try {
            $user->save();
        } catch (AphrontDuplicateKeyQueryException $ex) {
            $user->setUsername($old_username);
            $user->killTransaction();
            throw $ex;
        }

        $log = PhabricatorUserLog::initializeNewLog(
            $actor,
            $user->getPHID(),
            PhabricatorUserLog::ACTION_CHANGE_USERNAME);
        $log->setOldValue($old_username);
        $log->setNewValue($username);
        $log->save();

        $user->saveTransaction();

        // The SSH key cache currently includes usernames, so dirty it. See T12554
        // for discussion.
        PhabricatorAuthSSHKeyQuery::deleteSSHKeyCache();

        $user->sendUsernameChangeEmail($actor, $old_username);
    }


    /* -(  Editing Roles  )------------------------------------------------------ */


    /**
     * @task role
     * @param PhabricatorUser $user
     * @param $admin
     * @return PhabricatorUserEditor
     * @throws Exception
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     */
    public function makeAdminUser(PhabricatorUser $user, $admin)
    {
        $actor = $this->requireActor();

        if (!$user->getID()) {
            throw new Exception(\Yii::t("app", 'User has not been created yet!'));
        }

        $user->openTransaction();
        $user->beginWriteLocking();

        $user->reload();
        if ($user->getIsAdmin() == $admin) {
            $user->endWriteLocking();
            $user->killTransaction();
            return $this;
        }

        $log = PhabricatorUserLog::initializeNewLog(
            $actor,
            $user->getPHID(),
            PhabricatorUserLog::ACTION_ADMIN);
        $log->setOldValue($user->getIsAdmin());
        $log->setNewValue($admin);

        $user->setIsAdmin((int)$admin);
        $user->save();

        $log->save();

        $user->endWriteLocking();
        $user->saveTransaction();

        return $this;
    }

    /**
     * @task role
     * @param PhabricatorUser $user
     * @param $manager
     * @param $nodes
     * @return PhabricatorUserEditor
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws Exception
     */
    public function makeManagerUser(PhabricatorUser $user, $manager, $nodes)
    {
        $actor = $this->requireActor();

        if (!$user->getID()) {
            throw new Exception(\Yii::t("app", 'User has not been created yet!'));
        }

        $user->openTransaction();
        $user->beginWriteLocking();

        $user->reload();

        $xactions = [];
        $xactions[] = (new PhabricatorUserTransaction())
            ->setTransactionType(PhabricatorUserAddRbacNodeTransaction::TRANSACTIONTYPE)
            ->setNewValue($nodes);

        (new PhabricatorUserTransactionEditor())
            ->setActor($this->getActor())
            ->setContinueOnNoEffect(true)
            ->setContentSource(PhabricatorContentSource::newForSource(PhabricatorWebContentSource::SOURCECONST))
            ->applyTransactions($user, $xactions);


        $log = PhabricatorUserLog::initializeNewLog(
            $actor,
            $user->getPHID(),
            PhabricatorUserLog::ACTION_ADMIN);
        $log->setOldValue($user->getIsManager());
        $log->setNewValue($manager);

        $user->setIsManager((int)$manager);
        $user->save();

        $log->save();

        $user->endWriteLocking();
        $user->saveTransaction();
        return $this;
    }


    /**
     * @task role
     * @param PhabricatorUser $user
     * @param $system_agent
     * @return PhabricatorUserEditor
     * @throws Exception
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     */
    public function makeSystemAgentUser(PhabricatorUser $user, $system_agent)
    {
        $actor = $this->requireActor();

        if (!$user->getID()) {
            throw new Exception(\Yii::t("app", 'User has not been created yet!'));
        }

        $user->openTransaction();
        $user->beginWriteLocking();

        $user->reload();
        if ($user->getIsSystemAgent() == $system_agent) {
            $user->endWriteLocking();
            $user->killTransaction();
            return $this;
        }

        $log = PhabricatorUserLog::initializeNewLog(
            $actor,
            $user->getPHID(),
            PhabricatorUserLog::ACTION_SYSTEM_AGENT);
        $log->setOldValue($user->getIsSystemAgent());
        $log->setNewValue($system_agent);

        $user->setIsSystemAgent((int)$system_agent);
        $user->save();

        $log->save();

        $user->endWriteLocking();
        $user->saveTransaction();

        return $this;
    }

    /**
     * @task role
     * @param PhabricatorUser $user
     * @param $mailing_list
     * @return PhabricatorUserEditor
     * @throws Exception
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     */
    public function makeMailingListUser(PhabricatorUser $user, $mailing_list)
    {
        $actor = $this->requireActor();

        if (!$user->getID()) {
            throw new Exception(\Yii::t("app", 'User has not been created yet!'));
        }

        $user->openTransaction();
        $user->beginWriteLocking();

        $user->reload();
        if ($user->getIsMailingList() == $mailing_list) {
            $user->endWriteLocking();
            $user->killTransaction();
            return $this;
        }

        $log = PhabricatorUserLog::initializeNewLog(
            $actor,
            $user->getPHID(),
            PhabricatorUserLog::ACTION_MAILING_LIST);
        $log->setOldValue($user->getIsMailingList());
        $log->setNewValue($mailing_list);

        $user->setIsMailingList((int)$mailing_list);
        $user->save();

        $log->save();

        $user->endWriteLocking();
        $user->saveTransaction();

        return $this;
    }

    /**
     * @task role
     * @param PhabricatorUser $user
     * @param $approve
     * @return PhabricatorUserEditor
     * @throws Exception
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     */
    public function approveUser(PhabricatorUser $user, $approve)
    {
        $actor = $this->requireActor();

        if (!$user->getID()) {
            throw new Exception(\Yii::t("app", 'User has not been created yet!'));
        }

        $user->openTransaction();
        $user->beginWriteLocking();

        $user->reload();
        if ($user->getIsApproved() == $approve) {
            $user->endWriteLocking();
            $user->killTransaction();
            return $this;
        }

        $log = PhabricatorUserLog::initializeNewLog(
            $actor,
            $user->getPHID(),
            PhabricatorUserLog::ACTION_APPROVE);
        $log->setOldValue($user->getIsApproved());
        $log->setNewValue($approve);

        $user->setIsApproved($approve);
        $user->save();

        $log->save();

        $user->endWriteLocking();
        $user->saveTransaction();

        return $this;
    }


    /* -(  Adding, Removing and Changing Email  )-------------------------------- */


    /**
     * @task email
     * @param PhabricatorUser $user
     * @param PhabricatorUserEmail $email
     * @return PhabricatorUserEditor
     * @throws AphrontDuplicateKeyQueryException
     * @throws Exception
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     */
    public function addEmail(
        PhabricatorUser $user,
        PhabricatorUserEmail $email)
    {

        $actor = $this->requireActor();

        if (!$user->getID()) {
            throw new Exception(\Yii::t("app", 'User has not been created yet!'));
        }
        if ($email->getID()) {
            throw new Exception(\Yii::t("app", 'Email has already been created!'));
        }

        // Use changePrimaryEmail() to change primary email.
        $email->setIsPrimary(0);
        $email->setUserPHID($user->getPHID());

        $this->willAddEmail($email);

        $user->openTransaction();
        $user->beginWriteLocking();

        $user->reload();

        try {
            $email->save();
        } catch (AphrontDuplicateKeyQueryException $ex) {
            $user->endWriteLocking();
            $user->killTransaction();

            throw $ex;
        }

        $log = PhabricatorUserLog::initializeNewLog(
            $actor,
            $user->getPHID(),
            PhabricatorUserLog::ACTION_EMAIL_ADD);
        $log->setNewValue($email->getAddress());
        $log->save();

        $user->endWriteLocking();
        $user->saveTransaction();

        // Try and match this new address against unclaimed `RepositoryIdentity`s
        PhabricatorWorker::scheduleTask(
            'PhabricatorRepositoryIdentityChangeWorker',
            array('userPHID' => $user->getPHID()),
            array('objectPHID' => $user->getPHID()));

        return $this;
    }


    /**
     * @task email
     * @param PhabricatorUser $user
     * @param PhabricatorUserEmail $email
     * @return PhabricatorUserEditor
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws \yii\db\StaleObjectException
     */
    public function removeEmail(
        PhabricatorUser $user,
        PhabricatorUserEmail $email)
    {

        $actor = $this->requireActor();

        if (!$user->getID()) {
            throw new Exception(\Yii::t("app", 'User has not been created yet!'));
        }
        if (!$email->getID()) {
            throw new Exception(\Yii::t("app", 'Email has not been created yet!'));
        }

        $user->openTransaction();
        $user->beginWriteLocking();

        $user->reload();
        $email->reload();

        if ($email->getIsPrimary()) {
            throw new Exception(\Yii::t("app", "Can't remove primary email!"));
        }
        if ($email->getUserPHID() != $user->getPHID()) {
            throw new Exception(\Yii::t("app", 'Email not owned by user!'));
        }

        $email->delete();

        $log = PhabricatorUserLog::initializeNewLog(
            $actor,
            $user->getPHID(),
            PhabricatorUserLog::ACTION_EMAIL_REMOVE);
        $log->setOldValue($email->getAddress());
        $log->save();

        $user->endWriteLocking();
        $user->saveTransaction();

        $this->revokePasswordResetLinks($user);

        return $this;
    }


    /**
     * @task email
     * @param PhabricatorUser $user
     * @param PhabricatorUserEmail $email
     * @return PhabricatorUserEditor
     * @throws Exception
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     */
    public function changePrimaryEmail(
        PhabricatorUser $user,
        PhabricatorUserEmail $email)
    {
        $actor = $this->requireActor();

        if (!$user->getID()) {
            throw new Exception(\Yii::t("app", 'User has not been created yet!'));
        }
        if (!$email->getID()) {
            throw new Exception(\Yii::t("app", 'Email has not been created yet!'));
        }

        $user->openTransaction();
        $user->beginWriteLocking();

        $user->reload();
        $email->reload();

        if ($email->getUserPHID() != $user->getPHID()) {
            throw new Exception(\Yii::t("app", 'User does not own email!'));
        }

        if ($email->getIsPrimary()) {
            throw new Exception(\Yii::t("app", 'Email is already primary!'));
        }

        if (!$email->getIsVerified()) {
            throw new Exception(\Yii::t("app", 'Email is not verified!'));
        }

        $old_primary = $user->loadPrimaryEmail();
        if ($old_primary) {
            $old_primary->setIsPrimary(0);
            $old_primary->save();
        }

        $email->setIsPrimary(1);
        $email->save();

        // If the user doesn't have the verified flag set on their account
        // yet, set it. We've made sure the email is verified above. See
        // T12635 for discussion.
        if (!$user->getIsEmailVerified()) {
            $user->setIsEmailVerified(1);
            $user->save();
        }

        $log = PhabricatorUserLog::initializeNewLog(
            $actor,
            $user->getPHID(),
            PhabricatorUserLog::ACTION_EMAIL_PRIMARY);
        $log->setOldValue($old_primary ? $old_primary->getAddress() : null);
        $log->setNewValue($email->getAddress());

        $log->save();

        $user->endWriteLocking();
        $user->saveTransaction();

        if ($old_primary) {
            $old_primary->sendOldPrimaryEmail($user, $email);
        }
        $email->sendNewPrimaryEmail($user);


        $this->revokePasswordResetLinks($user);

        return $this;
    }


    /**
     * Verify a user's email address.
     *
     * This verifies an individual email address. If the address is the user's
     * primary address and their account was not previously verified, their
     * account is marked as email verified.
     *
     * @task email
     * @param PhabricatorUser $user
     * @param PhabricatorUserEmail $email
     * @throws Exception
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     */
    public function verifyEmail(
        PhabricatorUser $user,
        PhabricatorUserEmail $email)
    {
        $actor = $this->requireActor();

        if (!$user->getID()) {
            throw new Exception(\Yii::t("app", 'User has not been created yet!'));
        }
        if (!$email->getID()) {
            throw new Exception(\Yii::t("app", 'Email has not been created yet!'));
        }

        $user->openTransaction();
        $user->beginWriteLocking();

        $user->reload();
        $email->reload();

        if ($email->getUserPHID() != $user->getPHID()) {
            throw new Exception(\Yii::t("app", 'User does not own email!'));
        }

        if (!$email->getIsVerified()) {
            $email->setIsVerified(1);
            $email->save();

            $log = PhabricatorUserLog::initializeNewLog(
                $actor,
                $user->getPHID(),
                PhabricatorUserLog::ACTION_EMAIL_VERIFY);
            $log->setNewValue($email->getAddress());
            $log->save();
        }

        if (!$user->getIsEmailVerified()) {
            // If the user just verified their primary email address, mark their
            // account as email verified.
            $user_primary = $user->loadPrimaryEmail();
            if ($user_primary->getID() == $email->getID()) {
                $user->setIsEmailVerified(1);
                $user->save();
            }
        }

        $user->endWriteLocking();
        $user->saveTransaction();

        $this->didVerifyEmail($user, $email);
    }


    /**
     * Reassign an unverified email address.
     * @param PhabricatorUser $user
     * @param PhabricatorUserEmail $email
     * @throws Exception
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     */
    public function reassignEmail(
        PhabricatorUser $user,
        PhabricatorUserEmail $email)
    {
        $actor = $this->requireActor();

        if (!$user->getID()) {
            throw new Exception(\Yii::t("app", 'User has not been created yet!'));
        }

        if (!$email->getID()) {
            throw new Exception(\Yii::t("app", 'Email has not been created yet!'));
        }

        $user->openTransaction();
        $user->beginWriteLocking();

        $user->reload();
        $email->reload();

        $old_user = $email->getUserPHID();

        if ($old_user != $user->getPHID()) {
            if ($email->getIsVerified()) {
                throw new Exception(
                    \Yii::t("app", 'Verified email addresses can not be reassigned.'));
            }
            if ($email->getIsPrimary()) {
                throw new Exception(
                    \Yii::t("app", 'Primary email addresses can not be reassigned.'));
            }

            $email->setUserPHID($user->getPHID());
            $email->save();

            $log = PhabricatorUserLog::initializeNewLog(
                $actor,
                $user->getPHID(),
                PhabricatorUserLog::ACTION_EMAIL_REASSIGN);
            $log->setNewValue($email->getAddress());
            $log->save();
        }

        $user->endWriteLocking();
        $user->saveTransaction();
    }


    /* -(  Internals  )---------------------------------------------------------- */


    /**
     * @task internal
     * @param PhabricatorUserEmail $email
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     */
    private function willAddEmail(PhabricatorUserEmail $email)
    {

        // Hard check before write to prevent creation of disallowed email
        // addresses. Normally, the application does checks and raises more
        // user friendly errors for us, but we omit the courtesy checks on some
        // pathways like administrative scripts for simplicity.

        if (!PhabricatorUserEmail::isValidAddress($email->getAddress())) {
            throw new Exception(PhabricatorUserEmail::describeValidAddresses());
        }

        if (!PhabricatorUserEmail::isAllowedAddress($email->getAddress())) {
            throw new Exception(PhabricatorUserEmail::describeAllowedAddresses());
        }

        $application_email = PhabricatorMetaMTAApplicationEmail::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withAddresses(array($email->getAddress()))
            ->executeOne();
        if ($application_email) {
            throw new Exception($application_email->getInUseMessage());
        }
    }

    /**
     * @param PhabricatorUser $user
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function revokePasswordResetLinks(PhabricatorUser $user)
    {
        // Revoke any outstanding password reset links. If an attacker compromises
        // an account, changes the email address, and sends themselves a password
        // reset link, it could otherwise remain live for a short period of time
        // and allow them to compromise the account again later.

        PhabricatorAuthTemporaryToken::revokeTokens(
            $user,
            array($user->getPHID()),
            array(
                PhabricatorAuthOneTimeLoginTemporaryTokenType::TOKENTYPE,
                PhabricatorAuthPasswordResetTemporaryTokenType::TOKENTYPE,
            ));
    }

    /**
     * @param PhabricatorUser $user
     * @param PhabricatorUserEmail $email
     * @author 陈妙威
     */
    private function didVerifyEmail(
        PhabricatorUser $user,
        PhabricatorUserEmail $email)
    {
        $peopleDidVerifyEmail = new PeopleDidVerifyEmail();
        $peopleDidVerifyEmail->setUser($user)->setEmail($email);

        Yii::$app->trigger(PhabricatorEventType::TYPE_AUTH_DIDVERIFYEMAIL, $peopleDidVerifyEmail);
    }
}
