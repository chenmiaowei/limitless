<?php

namespace orangins\lib\infrastructure\util\password;

use orangins\lib\OranginsObject;
use PhutilClassMapQuery;
use PhutilNumber;
use PhutilOpaqueEnvelope;
use Exception;

/**
 * Provides a mechanism for hashing passwords, like "iterated md5", "bcrypt",
 * "scrypt", etc.
 *
 * Hashers define suitability and strength, and the system automatically
 * chooses the strongest available hasher and can prompt users to upgrade as
 * soon as a stronger hasher is available.
 *
 * @task hasher   Implementing a Hasher
 * @task hashing  Using Hashers
 */
abstract class PhabricatorPasswordHasher extends OranginsObject
{

    const MAXIMUM_STORAGE_SIZE = 128;


    /* -(  Implementing a Hasher  )---------------------------------------------- */


    /**
     * Return a human-readable description of this hasher, like "Iterated MD5".
     *
     * @return string Human readable hash name.
     * @task hasher
     */
    abstract public function getHumanReadableName();


    /**
     * Return a short, unique, key identifying this hasher, like "md5" or
     * "bcrypt". This identifier should not be translated.
     *
     * @return string Short, unique hash name.
     * @task hasher
     */
    abstract public function getHashName();


    /**
     * Return the maximum byte length of hashes produced by this hasher. This is
     * used to prevent storage overflows.
     *
     * @return int  Maximum number of bytes in hashes this class produces.
     * @task hasher
     */
    abstract public function getHashLength();


    /**
     * Return `true` to indicate that any required extensions or dependencies
     * are available, and this hasher is able to perform hashing.
     *
     * @return bool True if this hasher can execute.
     * @task hasher
     */
    abstract public function canHashPasswords();


    /**
     * Return a human-readable string describing why this hasher is unable
     * to operate. For example, "To use bcrypt, upgrade to PHP 5.5.0 or newer.".
     *
     * @return string Human-readable description of how to enable this hasher.
     * @task hasher
     */
    abstract public function getInstallInstructions();


    /**
     * Return an indicator of this hasher's strength. When choosing to hash
     * new passwords, the strongest available hasher which is usable for new
     * passwords will be used, and the presence of a stronger hasher will
     * prompt users to update their hashes.
     *
     * Generally, this method should return a larger number than hashers it is
     * preferable to, but a smaller number than hashers which are better than it
     * is. This number does not need to correspond directly with the actual hash
     * strength.
     *
     * @return float  Strength of this hasher.
     * @task hasher
     */
    abstract public function getStrength();


    /**
     * Return a short human-readable indicator of this hasher's strength, like
     * "Weak", "Okay", or "Good".
     *
     * This is only used to help administrators make decisions about
     * configuration.
     *
     * @return string Short human-readable description of hash strength.
     * @task hasher
     */
    abstract public function getHumanReadableStrength();


    /**
     * Produce a password hash.
     *
     * @param PhutilOpaqueEnvelope $envelope
     * @return  PhutilOpaqueEnvelope  Hashed text.
     * @task hasher
     */
    abstract protected function getPasswordHash(PhutilOpaqueEnvelope $envelope);


    /**
     * Verify that a password matches a hash.
     *
     * The default implementation checks for equality; if a hasher embeds salt in
     * hashes it should override this method and perform a salt-aware comparison.
     *
     * @param PhutilOpaqueEnvelope $password
     * @param PhutilOpaqueEnvelope $hash
     * @return  bool                  True if the passwords match.
     * @throws \Exception
     * @task hasher
     */
    protected function verifyPassword(
        PhutilOpaqueEnvelope $password,
        PhutilOpaqueEnvelope $hash)
    {

        $actual_hash = $this->getPasswordHash($password)->openEnvelope();
        $expect_hash = $hash->openEnvelope();

        return phutil_hashes_are_identical($actual_hash, $expect_hash);
    }


    /**
     * Check if an existing hash created by this algorithm is upgradeable.
     *
     * The default implementation returns `false`. However, hash algorithms which
     * have (for example) an internal cost function may be able to upgrade an
     * existing hash to a stronger one with a higher cost.
     *
     * @param PhutilOpaqueEnvelope $hash
     * @return bool                 True if the hash can be upgraded without
     *                              changing the algorithm (for example, to a
     *                              higher cost).
     * @task hasher
     */
    protected function canUpgradeInternalHash(PhutilOpaqueEnvelope $hash)
    {
        return false;
    }


    /* -(  Using Hashers  )------------------------------------------------------ */


    /**
     * Get the hash of a password for storage.
     *
     * @param PhutilOpaqueEnvelope $envelope
     * @return  PhutilOpaqueEnvelope  Hashed text.
     * @throws Exception
     * @task hashing
     */
    final public function getPasswordHashForStorage(
        PhutilOpaqueEnvelope $envelope)
    {

        $name = $this->getHashName();
        $hash = $this->getPasswordHash($envelope);

        $actual_len = strlen($hash->openEnvelope());
        $expect_len = $this->getHashLength();
        if ($actual_len > $expect_len) {
            throw new Exception(
                \Yii::t("app",
                    "Password hash '{0}' produced a hash of length {1}, but a " .
                    "maximum length of {2} was expected.",
                   [
                       $name,
                       $actual_len,
                       $expect_len
                   ]
                ));
        }

        return new PhutilOpaqueEnvelope($name . ':' . $hash->openEnvelope());
    }


    /**
     * Parse a storage hash into its components, like the hash type and hash
     * data.
     *
     * @param PhutilOpaqueEnvelope $hash
     * @return array Dictionary of information about the hash.
     * @throws Exception
     * @task hashing
     */
    private static function parseHashFromStorage(PhutilOpaqueEnvelope $hash)
    {
        $raw_hash = $hash->openEnvelope();
        if (strpos($raw_hash, ':') === false) {
            throw new Exception(
                \Yii::t("app",
                    'Malformed password hash, expected "name:hash".'));
        }

        list($name, $hash) = explode(':', $raw_hash);

        return array(
            'name' => $name,
            'hash' => new PhutilOpaqueEnvelope($hash),
        );
    }


    /**
     * Get all available password hashers. This may include hashers which can not
     * actually be used (for example, a required extension is missing).
     *
     * @return array<PhabricatorPasswordHasher> Hasher objects.
     * @task hashing
     * @throws Exception
     */
    public static function getAllHashers()
    {
        $objects = (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getHashName')
            ->execute();

        foreach ($objects as $object) {
            $name = $object->getHashName();

            $potential_length = strlen($name) + $object->getHashLength() + 1;
            $maximum_length = self::MAXIMUM_STORAGE_SIZE;

            if ($potential_length > $maximum_length) {
                throw new Exception(
                    \Yii::t("app",
                        'Hasher "{0}" may produce hashes which are too long to fit in ' .
                        'storage. {1} characters are available, but its hashes may be ' .
                        'up to {2} characters in length.',
                        [
                            $name,
                            $maximum_length,
                            $potential_length
                        ]));
            }
        }

        return $objects;
    }


    /**
     * Get all usable password hashers. This may include hashers which are
     * not desirable or advisable.
     *
     * @return array<PhabricatorPasswordHasher> Hasher objects.
     * @task hashing
     * @throws Exception
     */
    public static function getAllUsableHashers()
    {
        $hashers = self::getAllHashers();
        foreach ($hashers as $key => $hasher) {
            if (!$hasher->canHashPasswords()) {
                unset($hashers[$key]);
            }
        }
        return $hashers;
    }


    /**
     * Get the best (strongest) available hasher.
     *
     * @return PhabricatorPasswordHasher Best hasher.
     * @throws PhabricatorPasswordHasherUnavailableException
     * @throws Exception
     * @task hashing
     */
    public static function getBestHasher()
    {
        $hashers = self::getAllUsableHashers();
        $hashers = msort($hashers, 'getStrength');

        $hasher = last($hashers);
        if (!$hasher) {
            throw new PhabricatorPasswordHasherUnavailableException(
                \Yii::t("app",
                    'There are no password hashers available which are usable for ' .
                    'new passwords.'));
        }

        return $hasher;
    }


    /**
     * Get the hasher for a given stored hash.
     *
     * @param PhutilOpaqueEnvelope $hash
     * @return PhabricatorPasswordHasher Corresponding hasher.
     * @throws Exception
     * @throws PhabricatorPasswordHasherUnavailableException
     * @task hashing
     */
    public static function getHasherForHash(PhutilOpaqueEnvelope $hash)
    {
        $info = self::parseHashFromStorage($hash);
        $name = $info['name'];

        $usable = self::getAllUsableHashers();
        if (isset($usable[$name])) {
            return $usable[$name];
        }

        $all = self::getAllHashers();
        if (isset($all[$name])) {
            throw new PhabricatorPasswordHasherUnavailableException(
                \Yii::t("app",
                    'Attempting to compare a password saved with the "{0}" hash. The ' .
                    'hasher exists, but is not currently usable. {1}',
                    [
                        $name,
                        $all[$name]->getInstallInstructions()
                    ]));
        }

        throw new PhabricatorPasswordHasherUnavailableException(
            \Yii::t("app",
                'Attempting to compare a password saved with the "{0}" hash. No such ' .
                'hasher is known to Phabricator.',
                [
                    $name
                ]));
    }


    /**
     * Test if a password is using an weaker hash than the strongest available
     * hash. This can be used to prompt users to upgrade, or automatically upgrade
     * on login.
     *
     * @param PhutilOpaqueEnvelope $hash
     * @return bool True to indicate that rehashing this password will improve
     *              the hash strength.
     * @throws Exception
     * @throws PhabricatorPasswordHasherUnavailableException
     * @task hashing
     */
    public static function canUpgradeHash(PhutilOpaqueEnvelope $hash)
    {
        if (!strlen($hash->openEnvelope())) {
            throw new Exception(
                \Yii::t("app",'Expected a password hash, received nothing!'));
        }

        $current_hasher = self::getHasherForHash($hash);
        $best_hasher = self::getBestHasher();

        if ($current_hasher->getHashName() != $best_hasher->getHashName()) {
            // If the algorithm isn't the best one, we can upgrade.
            return true;
        }

        $info = self::parseHashFromStorage($hash);
        if ($current_hasher->canUpgradeInternalHash($info['hash'])) {
            // If the algorithm provides an internal upgrade, we can also upgrade.
            return true;
        }

        // Already on the best algorithm with the best settings.
        return false;
    }


    /**
     * Generate a new hash for a password, using the best available hasher.
     *
     * @param PhutilOpaqueEnvelope $password
     * @return  PhutilOpaqueEnvelope  Hashed password, using best available
     *                                hasher.
     * @throws Exception
     * @throws PhabricatorPasswordHasherUnavailableException
     * @task hashing
     */
    public static function generateNewPasswordHash(
        PhutilOpaqueEnvelope $password)
    {
        $hasher = self::getBestHasher();
        return $hasher->getPasswordHashForStorage($password);
    }


    /**
     * Compare a password to a stored hash.
     *
     * @param PhutilOpaqueEnvelope $password
     * @param PhutilOpaqueEnvelope $hash
     * @return  bool                  True if the passwords match.
     * @throws Exception
     * @throws PhabricatorPasswordHasherUnavailableException
     * @task hashing
     */
    public static function comparePassword(
        PhutilOpaqueEnvelope $password,
        PhutilOpaqueEnvelope $hash)
    {

        $hasher = self::getHasherForHash($hash);
        $parts = self::parseHashFromStorage($hash);

        return $hasher->verifyPassword($password, $parts['hash']);
    }


    /**
     * Get the human-readable algorithm name for a given hash.
     *
     * @param PhutilOpaqueEnvelope $hash
     * @return  string                Human-readable algorithm name.
     * @throws Exception
     */
    public static function getCurrentAlgorithmName(PhutilOpaqueEnvelope $hash)
    {
        $raw_hash = $hash->openEnvelope();
        if (!strlen($raw_hash)) {
            return \Yii::t("app",'None');
        }

        try {
            $current_hasher = self::getHasherForHash($hash);
            return $current_hasher->getHumanReadableName();
        } catch (Exception $ex) {
            $info = self::parseHashFromStorage($hash);
            $name = $info['name'];
            return \Yii::t("app",'Unknown ("{0}")', [
                $name
            ]);
        }
    }


    /**
     * Get the human-readable algorithm name for the best available hash.
     *
     * @return  string                Human-readable name for best hash.
     */
    public static function getBestAlgorithmName()
    {
        try {
            $best_hasher = self::getBestHasher();
            return $best_hasher->getHumanReadableName();
        } catch (Exception $ex) {
            return \Yii::t("app",'Unknown');
        }
    }

}
