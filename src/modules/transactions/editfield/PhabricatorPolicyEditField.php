<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\view\form\control\AphrontFormPolicyControl;
use PhutilInvalidStateException;
use orangins\lib\request\httpparametertype\AphrontPHIDHTTPParameterType;
use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;

/**
 * Class PhabricatorPolicyEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorPolicyEditField extends PhabricatorEditField
{
    /**
     * @var
     */
    private $policies;
    /**
     * @var
     */
    private $capability;
    /**
     * @var
     */
    private $spaceField;

    /**
     * @param array $policies
     * @return $this
     * @author 陈妙威
     */
    public function setPolicies(array $policies)
    {
        $this->policies = $policies;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    public function getPolicies()
    {
        if ($this->policies === null) {
            throw new PhutilInvalidStateException('setPolicies');
        }
        return $this->policies;
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

    /**
     * @param PhabricatorSpaceEditField $space_field
     * @return $this
     * @author 陈妙威
     */
    public function setSpaceField(PhabricatorSpaceEditField $space_field)
    {
        $this->spaceField = $space_field;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSpaceField()
    {
        return $this->spaceField;
    }

    /**
     * @author 陈妙威
     * @return AphrontFormPolicyControl
     * @throws PhutilInvalidStateException
     */
    protected function newControl()
    {
        $control = (new AphrontFormPolicyControl())
            ->setCapability($this->getCapability())
            ->setPolicyObject($this->getObject())
            ->setPolicies($this->getPolicies());

        $space_field = $this->getSpaceField();
        if ($space_field) {
            $control->setSpacePHID($space_field->getValueForControl());
        }

        return $control;
    }

    /**
     * @return AphrontPHIDHTTPParameterType|AphrontStringHTTPParameterType
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        return new AphrontPHIDHTTPParameterType();
    }

    /**
     * @return ConduitStringParameterType|mixed
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitStringParameterType();
    }
}
