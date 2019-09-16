<?php

namespace orangins\modules\config\type;

use orangins\lib\request\AphrontRequest;
use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\config\option\PhabricatorConfigOption;

/**
 * Class PhabricatorTextConfigType
 * @package orangins\modules\config\type
 * @author 陈妙威
 */
abstract class PhabricatorTextConfigType
    extends PhabricatorConfigType
{

    /**
     * @param PhabricatorConfigOption $option
     * @param AphrontRequest $request
     * @return bool
     * @author 陈妙威
     */
    public function isValuePresentInRequest(
        PhabricatorConfigOption $option,
        AphrontRequest $request)
    {
        $value = parent::readValueFromRequest($option, $request);
        return (bool)strlen($value);
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return string
     * @author 陈妙威
     */
    protected function newCanonicalValue(
        PhabricatorConfigOption $option,
        $value)
    {
        return (string)$value;
    }

    /**
     * @return AphrontStringHTTPParameterType
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        return new AphrontStringHTTPParameterType();
    }

    /**
     * @param PhabricatorConfigOption $option
     * @return AphrontFormTextControl
     * @author 陈妙威
     */
    protected function newControl(PhabricatorConfigOption $option)
    {
        return new AphrontFormTextControl();
    }

}
