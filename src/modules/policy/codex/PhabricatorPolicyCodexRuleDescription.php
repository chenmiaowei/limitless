<?php

namespace orangins\modules\policy\codex;

use orangins\lib\OranginsObject;

/**
 * Class PhabricatorPolicyCodexRuleDescription
 * @package orangins\modules\policy\codex
 * @author 陈妙威
 */
final class PhabricatorPolicyCodexRuleDescription
    extends OranginsObject
{

    /**
     * @var
     */
    private $description;
    /**
     * @var array
     */
    private $capabilities = array();
    /**
     * @var bool
     */
    private $isActive = true;

    /**
     * @param $description
     * @return $this
     * @author 陈妙威
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param array $capabilities
     * @return $this
     * @author 陈妙威
     */
    public function setCapabilities(array $capabilities)
    {
        $this->capabilities = $capabilities;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getCapabilities()
    {
        return $this->capabilities;
    }

    /**
     * @param $is_active
     * @return $this
     * @author 陈妙威
     */
    public function setIsActive($is_active)
    {
        $this->isActive = $is_active;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

}
