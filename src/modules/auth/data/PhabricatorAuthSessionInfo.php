<?php

namespace orangins\modules\auth\data;

use orangins\lib\OranginsObject;

/**
 * Class PhabricatorAuthSessionInfo
 * @package orangins\modules\auth\data
 * @author 陈妙威
 */
final class PhabricatorAuthSessionInfo extends OranginsObject
{

    /**
     * @var
     */
    private $sessionType;
    /**
     * @var
     */
    private $identityPHID;
    /**
     * @var
     */
    private $isPartial;

    /**
     * @param $session_type
     * @return $this
     * @author 陈妙威
     */
    public function setSessionType($session_type)
    {
        $this->sessionType = $session_type;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSessionType()
    {
        return $this->sessionType;
    }

    /**
     * @param $identity_phid
     * @return $this
     * @author 陈妙威
     */
    public function setIdentityPHID($identity_phid)
    {
        $this->identityPHID = $identity_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIdentityPHID()
    {
        return $this->identityPHID;
    }

    /**
     * @param $is_partial
     * @return $this
     * @author 陈妙威
     */
    public function setIsPartial($is_partial)
    {
        $this->isPartial = $is_partial;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsPartial()
    {
        return $this->isPartial;
    }

}
