<?php

namespace orangins\modules\auth\models;

use orangins\lib\OranginsObject;

/**
 * Class PhabricatorRegistrationProfile
 * @package orangins\modules\auth\models
 * @author 陈妙威
 */
final class PhabricatorRegistrationProfile extends OranginsObject
{

    /**
     * @var
     */
    private $defaultUsername;
    /**
     * @var
     */
    private $defaultEmail;
    /**
     * @var
     */
    private $defaultRealName;
    /**
     * @var
     */
    private $canEditUsername;
    /**
     * @var
     */
    private $canEditEmail;
    /**
     * @var
     */
    private $canEditRealName;
    /**
     * @var
     */
    private $shouldVerifyEmail;

    /**
     * @param $should_verify_email
     * @return $this
     * @author 陈妙威
     */
    public function setShouldVerifyEmail($should_verify_email)
    {
        $this->shouldVerifyEmail = $should_verify_email;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getShouldVerifyEmail()
    {
        return $this->shouldVerifyEmail;
    }

    /**
     * @param $can_edit_email
     * @return $this
     * @author 陈妙威
     */
    public function setCanEditEmail($can_edit_email)
    {
        $this->canEditEmail = $can_edit_email;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCanEditEmail()
    {
        return $this->canEditEmail;
    }

    /**
     * @param $can_edit_real_name
     * @return $this
     * @author 陈妙威
     */
    public function setCanEditRealName($can_edit_real_name)
    {
        $this->canEditRealName = $can_edit_real_name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCanEditRealName()
    {
        return $this->canEditRealName;
    }


    /**
     * @param $can_edit_username
     * @return $this
     * @author 陈妙威
     */
    public function setCanEditUsername($can_edit_username)
    {
        $this->canEditUsername = $can_edit_username;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCanEditUsername()
    {
        return $this->canEditUsername;
    }

    /**
     * @param $default_email
     * @return $this
     * @author 陈妙威
     */
    public function setDefaultEmail($default_email)
    {
        $this->defaultEmail = $default_email;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDefaultEmail()
    {
        return $this->defaultEmail;
    }

    /**
     * @param $default_real_name
     * @return $this
     * @author 陈妙威
     */
    public function setDefaultRealName($default_real_name)
    {
        $this->defaultRealName = $default_real_name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDefaultRealName()
    {
        return $this->defaultRealName;
    }


    /**
     * @param $default_username
     * @return $this
     * @author 陈妙威
     */
    public function setDefaultUsername($default_username)
    {
        $this->defaultUsername = $default_username;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDefaultUsername()
    {
        return $this->defaultUsername;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getCanEditAnything()
    {
        return $this->getCanEditUsername() ||
            $this->getCanEditEmail() ||
            $this->getCanEditRealName();
    }

}
