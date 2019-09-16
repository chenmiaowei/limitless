<?php

namespace orangins\modules\policy\exception;

use yii\base\UserException;

/**
 * Class PhabricatorPolicyException
 * @package orangins\modules\policy\editor
 * @author 陈妙威
 */
final class PhabricatorPolicyException extends UserException
{

    /**
     * @var
     */
    private $title;
    /**
     * @var
     */
    private $rejection;
    /**
     * @var
     */
    private $capabilityName;
    /**
     * @var array
     */
    private $moreInfo = array();
    /**
     * @var
     */
    private $objectPHID;
    /**
     * @var
     */
    private $context;
    /**
     * @var
     */
    private $capability;

    /**
     * @param $title
     * @return $this
     * @author 陈妙威
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $capability_name
     * @return $this
     * @author 陈妙威
     */
    public function setCapabilityName($capability_name)
    {
        $this->capabilityName = $capability_name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCapabilityName()
    {
        return $this->capabilityName;
    }

    /**
     * @param $rejection
     * @return $this
     * @author 陈妙威
     */
    public function setRejection($rejection)
    {
        $this->rejection = $rejection;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRejection()
    {
        return $this->rejection;
    }

    /**
     * @param array $more_info
     * @return $this
     * @author 陈妙威
     */
    public function setMoreInfo(array $more_info)
    {
        $this->moreInfo = $more_info;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getMoreInfo()
    {
        return $this->moreInfo;
    }

    /**
     * @param $object_phid
     * @return $this
     * @author 陈妙威
     */
    public function setObjectPHID($object_phid)
    {
        $this->objectPHID = $object_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getObjectPHID()
    {
        return $this->objectPHID;
    }

    /**
     * @param $context
     * @return $this
     * @author 陈妙威
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param $capability
     * @return $this
     * @author 陈妙威
     */
    public function setCapability($capability)
    {
        $this->capability = $capability;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCapability()
    {
        return $this->capability;
    }

}
