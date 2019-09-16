<?php
namespace orangins\modules\auth\password;

use orangins\modules\auth\engine\PhabricatorAuthPasswordEngine;
use orangins\modules\auth\models\PhabricatorAuthPassword;
use PhutilOpaqueEnvelope;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Interface PhabricatorAuthPasswordHashInterface
 */
interface PhabricatorAuthPasswordHashInterface
{

    /**
     * @param PhutilOpaqueEnvelope $envelope
     * @param PhabricatorAuthPassword $password
     * @return mixed
     * @author 陈妙威
     */
    public function newPasswordDigest(
        PhutilOpaqueEnvelope $envelope,
        PhabricatorAuthPassword $password);

    /**
     * Return a list of strings which passwords associated with this object may
     * not be similar to.
     *
     * This method allows you to prevent users from selecting their username
     * as their password or picking other passwords which are trivially similar
     * to an account or object identifier.
     *
     * @param PhabricatorUser The user selecting the password.
     * @param PhabricatorAuthPasswordEngine The password engine updating a
     *  password.
     * @return array<string> Blocklist of nonsecret identifiers which the password
     *  should not be similar to.
     */
    public function newPasswordBlocklist(
        PhabricatorUser $viewer,
        PhabricatorAuthPasswordEngine $engine);

}
