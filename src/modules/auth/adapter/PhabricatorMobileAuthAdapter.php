<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/7/16
 * Time: 3:03 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\auth\adapter;


use PhutilAuthAdapter;

/**
 * Class PhabricatorMobileAuthAdapter
 * @package orangins\modules\auth\adapter
 * @author 陈妙威
 */
class PhabricatorMobileAuthAdapter extends PhutilAuthAdapter
{
    /**
     * @var
     */
    public $mobile;

    /**
     * Get a unique identifier associated with the identity. For most providers,
     * this is an account ID.
     *
     * The account ID needs to be unique within this adapter's configuration, such
     * that `<adapterKey, accountID>` is globally unique and always identifies the
     * same identity.
     *
     * If the adapter was unable to authenticate an identity, it should return
     * `null`.
     *
     * @return string|null Unique account identifier, or `null` if authentication
     *                     failed.
     */
    public function getAccountID()
    {
        return $this->mobile;
    }

    /**
     * Get a string identifying this adapter, like "ldap". This string should be
     * unique to the adapter class.
     *
     * @return string Unique adapter identifier.
     */
    public function getAdapterType()
    {
        return 'mobile';
    }

    /**
     * Get a string identifying the domain this adapter is acting on. This allows
     * an adapter (like LDAP) to act against different identity domains without
     * conflating credentials. For providers like Facebook or Google, the adapters
     * just return the relevant domain name.
     *
     * @return string Domain the adapter is associated with.
     */
    public function getAdapterDomain()
    {
        return 'self';
    }


    /**
     * @return mixed
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param mixed $mobile
     * @return self
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }
}