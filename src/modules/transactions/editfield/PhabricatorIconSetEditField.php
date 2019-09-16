<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\lib\view\form\control\PHUIFormIconSetControl;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\file\iconset\PhabricatorIconSet;

/**
 * Class PhabricatorIconSetEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorIconSetEditField extends PhabricatorEditField
{

    /**
     * @var
     */
    private $iconSet;

    /**
     * @param PhabricatorIconSet $icon_set
     * @return $this
     * @author 陈妙威
     */
    public function setIconSet(PhabricatorIconSet $icon_set)
    {
        $this->iconSet = $icon_set;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIconSet()
    {
        return $this->iconSet;
    }

    /**
     * @return
     * @author 陈妙威
     */
    protected function newControl()
    {
        return (new PHUIFormIconSetControl())
            ->setIconSet($this->getIconSet());
    }

    /**
     * @return ConduitStringParameterType|mixed
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitStringParameterType();
    }

    /**
     * @return \orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType|AphrontStringHTTPParameterType
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        return new AphrontStringHTTPParameterType();
    }

}
