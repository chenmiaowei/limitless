<?php

namespace orangins\modules\auth\engine;

use AphrontWriteGuard;
use orangins\lib\infrastructure\util\password\PhabricatorPasswordHasherUnavailableException;
use orangins\modules\auth\constants\PhabricatorCommonPasswords;
use orangins\modules\auth\models\PhabricatorAuthPassword;
use orangins\modules\auth\password\PhabricatorAuthPasswordException;
use orangins\modules\auth\password\PhabricatorAuthPasswordHashInterface;
use orangins\lib\env\PhabricatorEnv;
use PhutilOpaqueEnvelope;
use PhutilInvalidStateException;
use orangins\lib\helpers\OranginsUtf8;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use PhutilURI;
use PhutilNumber;
use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorAuthPasswordEngine
 * @package orangins\modules\auth\engine
 * @author 陈妙威
 */
final class PhabricatorAuthPasswordEngine extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $contentSource;
    /**
     * @var
     */
    private $object;
    /**
     * @var
     */
    private $passwordType;
    /**
     * @var bool
     */
    private $upgradeHashers = true;

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param PhabricatorContentSource $content_source
     * @return $this
     * @author 陈妙威
     */
    public function setContentSource(PhabricatorContentSource $content_source)
    {
        $this->contentSource = $content_source;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getContentSource()
    {
        return $this->contentSource;
    }

    /**
     * @param PhabricatorAuthPasswordHashInterface $object
     * @return $this
     * @author 陈妙威
     */
    public function setObject(PhabricatorAuthPasswordHashInterface $object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param $password_type
     * @return $this
     * @author 陈妙威
     */
    public function setPasswordType($password_type)
    {
        $this->passwordType = $password_type;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPasswordType()
    {
        return $this->passwordType;
    }

    /**
     * @param $upgrade_hashers
     * @return $this
     * @author 陈妙威
     */
    public function setUpgradeHashers($upgrade_hashers)
    {
        $this->upgradeHashers = $upgrade_hashers;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getUpgradeHashers()
    {
        return $this->upgradeHashers;
    }

    /**
     * @param PhutilOpaqueEnvelope $password
     * @param PhutilOpaqueEnvelope $confirm
     * @param bool $can_skip
     * @throws PhabricatorAuthPasswordException
     * @throws \yii\base\Exception
     * @throws PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    public function checkNewPassword(
        PhutilOpaqueEnvelope $password,
        PhutilOpaqueEnvelope $confirm,
        $can_skip = false)
    {

        $raw_password = $password->openEnvelope();

        if (!strlen($raw_password)) {
            if ($can_skip) {
                throw new PhabricatorAuthPasswordException(
                    \Yii::t("app", 'You must choose a password or skip this step.'),
                    \Yii::t("app", 'Required'));
            } else {
                throw new PhabricatorAuthPasswordException(
                    \Yii::t("app", 'You must choose a password.'),
                    \Yii::t("app", 'Required'));
            }
        }

        $min_len = PhabricatorEnv::getEnvConfig('account.minimum-password-length');
        $min_len = (int)$min_len;
        if ($min_len) {
            if (strlen($raw_password) < $min_len) {
                throw new PhabricatorAuthPasswordException(
                    \Yii::t("app",
                        'The selected password is too short. Passwords must be a minimum ' .
                        'of %s characters long.',
                        new PhutilNumber($min_len)),
                    \Yii::t("app", 'Too Short'));
            }
        }

        $raw_confirm = $confirm->openEnvelope();

        if (!strlen($raw_confirm)) {
            throw new PhabricatorAuthPasswordException(
                \Yii::t("app", 'You must confirm the selected password.'),
                null,
                \Yii::t("app", 'Required'));
        }

        if ($raw_password !== $raw_confirm) {
            throw new PhabricatorAuthPasswordException(
                \Yii::t("app", 'The password and confirmation do not match.'),
                \Yii::t("app", 'Invalid'),
                \Yii::t("app", 'Invalid'));
        }

        if (PhabricatorCommonPasswords::isCommonPassword($raw_password)) {
            throw new PhabricatorAuthPasswordException(
                \Yii::t("app",
                    'The selected password is very weak: it is one of the most common ' .
                    'passwords in use. Choose a stronger password.'),
                \Yii::t("app", 'Very Weak'));
        }

        // If we're creating a brand new object (like registering a new user)
        // and it does not have a PHID yet, it isn't possible for it to have any
        // revoked passwords or colliding passwords either, so we can skip these
        // checks.

        $object = $this->getObject();

        if ($object->getPHID()) {
            if ($this->isRevokedPassword($password)) {
                throw new PhabricatorAuthPasswordException(
                    \Yii::t("app",
                        'The password you entered has been revoked. You can not reuse ' .
                        'a password which has been revoked. Choose a new password.'),
                    \Yii::t("app", 'Revoked'));
            }

            if (!$this->isUniquePassword($password)) {
                throw new PhabricatorAuthPasswordException(
                    \Yii::t("app",
                        'The password you entered is the same as another password ' .
                        'associated with your account. Each password must be unique.'),
                    \Yii::t("app", 'Not Unique'));
            }
        }

        // Prevent use of passwords which are similar to any object identifier.
        // For example, if your username is "alincoln", your password may not be
        // "alincoln", "lincoln", or "alincoln1".
        $viewer = $this->getViewer();
        $blocklist = $object->newPasswordBlocklist($viewer, $this);

        // Smallest number of overlapping characters that we'll consider to be
        // too similar.
        $minimum_similarity = 4;

        // Add the domain name to the blocklist.
        $base_uri = PhabricatorEnv::getAnyBaseURI();
        $base_uri = new PhutilURI($base_uri);
        $blocklist[] = $base_uri->getDomain();

        // Generate additional subterms by splitting the raw blocklist on
        // characters like "@", " " (space), and "." to break up email addresses,
        // readable names, and domain names into components.
        $terms_map = array();
        foreach ($blocklist as $term) {
            $terms_map[$term] = $term;
            foreach (preg_split('/[ @.]/', $term) as $subterm) {
                $terms_map[$subterm] = $term;
            }
        }

        // Skip very short terms: it's okay if your password has the substring
        // "com" in it somewhere even if the install is on "mycompany.com".
        foreach ($terms_map as $term => $source) {
            if (strlen($term) < $minimum_similarity) {
                unset($terms_map[$term]);
            }
        }

        // Normalize terms for comparison.
        $normal_map = array();
        foreach ($terms_map as $term => $source) {
            $term = OranginsUtf8::phutil_utf8_strtolower($term);
            $normal_map[$term] = $source;
        }

        // Finally, make sure that none of the terms appear in the password,
        // and that the password does not appear in any of the terms.
        $normal_password = OranginsUtf8::phutil_utf8_strtolower($raw_password);
        if (strlen($normal_password) >= $minimum_similarity) {
            foreach ($normal_map as $term => $source) {
                if (strpos($term, $normal_password) === false &&
                    strpos($normal_password, $term) === false) {
                    continue;
                }

                throw new PhabricatorAuthPasswordException(
                    \Yii::t("app",
                        'The password you entered is very similar to a nonsecret account ' .
                        'identifier (like a username or email address). Choose a more ' .
                        'distinct password.'),
                    \Yii::t("app", 'Not Distinct'));
            }
        }
    }

    /**
     * @param PhutilOpaqueEnvelope $envelope
     * @return bool
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     * @throws \yii\base\Exception
     */
    public function isValidPassword(PhutilOpaqueEnvelope $envelope)
    {
        $this->requireSetup();

        $password_type = $this->getPasswordType();

        $query = $this->newQuery();
        $passwords = $query
            ->withPasswordTypes(array($password_type))
            ->withIsRevoked(false)
            ->execute();

        $matches = $this->getMatches($envelope, $passwords);
        if (!$matches) {
            return false;
        }

        if ($this->shouldUpgradeHashers()) {
            $this->upgradeHashers($envelope, $matches);
        }

        return true;
    }

    /**
     * @param PhutilOpaqueEnvelope $envelope
     * @return bool
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     * @throws \yii\base\Exception
     */
    public function isUniquePassword(PhutilOpaqueEnvelope $envelope)
    {
        $this->requireSetup();

        $password_type = $this->getPasswordType();

        // To test that the password is unique, we're loading all active and
        // revoked passwords for all roles for the given user, then throwing out
        // the active passwords for the current role (so a password can't
        // collide with itself).

        // Note that two different objects can have the same password (say,
        // users @alice and @bailey). We're only preventing @alice from using
        // the same password for everything.

        /** @var PhabricatorAuthPassword[] $passwords */
        $passwords = $this->newQuery()
            ->execute();

        foreach ($passwords as $key => $password) {
            $same_type = ($password->getPasswordType() === $password_type);
            $is_active = !$password->getIsRevoked();

            if ($same_type && $is_active) {
                unset($passwords[$key]);
            }
        }

        $matches = $this->getMatches($envelope, $passwords);

        return !$matches;
    }

    /**
     * @param PhutilOpaqueEnvelope $envelope
     * @return bool
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     * @throws \yii\base\Exception
     */
    public function isRevokedPassword(PhutilOpaqueEnvelope $envelope)
    {
        $this->requireSetup();

        // To test if a password is revoked, we're loading all revoked passwords
        // across all roles for the given user. If a password was revoked in one
        // role, you can't reuse it in a different role.

        $passwords = $this->newQuery()
            ->withIsRevoked(true)
            ->execute();

        $matches = $this->getMatches($envelope, $passwords);

        return (bool)$matches;
    }

    /**
     * @throws PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function requireSetup()
    {
        if (!$this->getObject()) {
            throw new PhutilInvalidStateException('setObject');
        }

        if (!$this->getPasswordType()) {
            throw new PhutilInvalidStateException('setPasswordType');
        }

        if (!$this->getViewer()) {
            throw new PhutilInvalidStateException('setViewer');
        }

        if ($this->shouldUpgradeHashers()) {
            if (!$this->getContentSource()) {
                throw new PhutilInvalidStateException('setContentSource');
            }
        }
    }

    /**
     * @return bool
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    private function shouldUpgradeHashers()
    {
        if (!$this->getUpgradeHashers()) {
            return false;
        }

        if (PhabricatorEnv::isReadOnly()) {
            // Don't try to upgrade hashers if we're in read-only mode, since we
            // won't be able to write the new hash to the database.
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function newQuery()
    {
        $viewer = $this->getViewer();
        $object = $this->getObject();

        return PhabricatorAuthPassword::find()
            ->setViewer($viewer)
            ->withObjectPHIDs(array($object->getPHID()));
    }

    /**
     * @param PhutilOpaqueEnvelope $envelope
     * @param array $passwords
     * @return array
     * @author 陈妙威
     */
    private function getMatches(
        PhutilOpaqueEnvelope $envelope,
        array $passwords)
    {

        $object = $this->getObject();

        $matches = array();
        foreach ($passwords as $password) {
            try {
                $is_match = $password->comparePassword($envelope, $object);
            } catch (PhabricatorPasswordHasherUnavailableException $ex) {
                $is_match = false;
            }

            if ($is_match) {
                $matches[] = $password;
            }
        }

        return $matches;
    }

    /**
     * @param PhutilOpaqueEnvelope $envelope
     * @param array $passwords
     * @author 陈妙威
     */
    private function upgradeHashers(
        PhutilOpaqueEnvelope $envelope,
        array $passwords)
    {

        assert_instances_of($passwords, PhabricatorAuthPassword::class);

        $need_upgrade = array();
        foreach ($passwords as $password) {
            if (!$password->canUpgrade()) {
                continue;
            }
            $need_upgrade[] = $password;
        }

        if (!$need_upgrade) {
            return;
        }

        $upgrade_type = PhabricatorAuthPasswordUpgradeTransaction::TRANSACTIONTYPE;
        $viewer = $this->getViewer();
        $content_source = $this->getContentSource();

        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        foreach ($need_upgrade as $password) {

            // This does the actual upgrade. We then apply a transaction to make
            // the upgrade more visible and auditable.
            $old_hasher = $password->getHasher();
            $password->upgradePasswordHasher($envelope, $this->getObject());
            $new_hasher = $password->getHasher();

            // NOTE: We must save the change before applying transactions because
            // the editor will reload the object to obtain a read lock.
            $password->save();

            $xactions = array();

            $xactions[] = $password->getApplicationTransactionTemplate()
                ->setTransactionType($upgrade_type)
                ->setNewValue($new_hasher->getHashName());

            $editor = $password->getApplicationTransactionEditor()
                ->setActor($viewer)
                ->setContinueOnNoEffect(true)
                ->setContinueOnMissingFields(true)
                ->setContentSource($content_source)
                ->setOldHasher($old_hasher)
                ->applyTransactions($password, $xactions);
        }
        unset($unguarded);
    }

}
