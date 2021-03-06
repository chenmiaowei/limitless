<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\request\httpparametertype\AphrontEpochHTTPParameterType;
use orangins\lib\view\form\control\AphrontFormDateControl;

final class PhabricatorEpochEditField
    extends PhabricatorEditField
{

    private $allowNull;
    private $hideTime;

    public function setAllowNull($allow_null)
    {
        $this->allowNull = $allow_null;
        return $this;
    }

    public function getAllowNull()
    {
        return $this->allowNull;
    }

    public function setHideTime($hide_time)
    {
        $this->hideTime = $hide_time;
        return $this;
    }

    public function getHideTime()
    {
        return $this->hideTime;
    }

    protected function newControl()
    {
        return (new AphrontFormDateControl())
            ->setAllowNull($this->getAllowNull())
            ->setIsTimeDisabled($this->getHideTime())
            ->setViewer($this->getViewer());
    }

    protected function newHTTPParameterType()
    {
        return (new AphrontEpochHTTPParameterType())
            ->setAllowNull($this->getAllowNull());
    }

    protected function newConduitParameterType()
    {
        return new ConduitEpochParameterType();
    }

}
