<?php

namespace orangins\lib\infrastructure\util\password;


use PhutilOpaqueEnvelope;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorBcryptPasswordHasher
 * @package orangins\lib\infrastructure\util\password
 * @author 陈妙威
 */
final class PhabricatorBcryptPasswordHasher
    extends PhabricatorPasswordHasher
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getHumanReadableName()
    {
        return \Yii::t("app", 'bcrypt');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getHashName()
    {
        return 'bcrypt';
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getHashLength()
    {
        return 60;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canHashPasswords()
    {
        return function_exists('password_hash');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getInstallInstructions()
    {
        return \Yii::t("app", 'Upgrade to PHP 5.5.0 or newer.');
    }

    /**
     * @return float
     * @author 陈妙威
     */
    public function getStrength()
    {
        return 2.0;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getHumanReadableStrength()
    {
        return \Yii::t("app", 'Good');
    }

    /**
     * @param PhutilOpaqueEnvelope $envelope
     * @return PhutilOpaqueEnvelope
     * @author 陈妙威
     */
    protected function getPasswordHash(PhutilOpaqueEnvelope $envelope)
    {
        $raw_input = $envelope->openEnvelope();

        $options = array(
            'cost' => $this->getBcryptCost(),
        );

        $raw_hash = password_hash($raw_input, PASSWORD_BCRYPT, $options);

        return new PhutilOpaqueEnvelope($raw_hash);
    }

    /**
     * @param PhutilOpaqueEnvelope $password
     * @param PhutilOpaqueEnvelope $hash
     * @return bool
     * @author 陈妙威
     */
    protected function verifyPassword(
        PhutilOpaqueEnvelope $password,
        PhutilOpaqueEnvelope $hash)
    {
        return password_verify($password->openEnvelope(), $hash->openEnvelope());
    }

    /**
     * @param PhutilOpaqueEnvelope $hash
     * @return bool
     * @author 陈妙威
     */
    protected function canUpgradeInternalHash(PhutilOpaqueEnvelope $hash)
    {
        $info = password_get_info($hash->openEnvelope());

        // NOTE: If the costs don't match -- even if the new cost is lower than
        // the old cost -- count this as an upgrade. This allows costs to be
        // adjusted down and hashing to be migrated toward the new cost if costs
        // are ever configured too high for some reason.

        $cost = ArrayHelper::getValue($info['options'], 'cost');
        if ($cost != $this->getBcryptCost()) {
            return true;
        }

        return false;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    private function getBcryptCost()
    {
        // NOTE: The default cost is "10", but my laptop can do a hash of cost
        // "12" in about 300ms. Since server hardware is often virtualized or old,
        // just split the difference.
        return 11;
    }

}
