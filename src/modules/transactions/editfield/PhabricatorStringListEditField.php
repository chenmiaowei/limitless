<?php

namespace orangins\modules\transactions\editfield;

use orangins\lib\request\httpparametertype\AphrontStringListHTTPParameterType;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\conduit\parametertype\ConduitStringListParameterType;

/**
 * Class PhabricatorStringListEditField
 * @package orangins\modules\transactions\editfield
 * @author 陈妙威
 */
final class PhabricatorStringListEditField
    extends PhabricatorEditField
{

    /**
     * @return \orangins\lib\view\form\control\AphrontFormControl|AphrontFormTextControl
     * @author 陈妙威
     */
    protected function newControl()
    {
        return new AphrontFormTextControl();
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getValueForControl()
    {
        $value = $this->getValue();
        return implode(', ', $value);
    }

    /**
     * @return mixed|ConduitStringListParameterType
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitStringListParameterType();
    }

    /**
     * @return \orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType|AphrontStringListHTTPParameterType
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        return new AphrontStringListHTTPParameterType();
    }

}
