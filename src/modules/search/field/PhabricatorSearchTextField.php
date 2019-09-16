<?php

namespace orangins\modules\search\field;

use orangins\lib\request\AphrontRequest;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\lib\view\form\control\AphrontFormTextControl;

/**
 * Class PhabricatorSearchTextField
 * @package orangins\modules\search\field
 * @author 陈妙威
 */
final class PhabricatorSearchTextField extends PhabricatorSearchField
{

    /**
     * @return null|string
     * @author 陈妙威
     */
    protected function getDefaultValue()
    {
        return '';
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return mixed|null|string
     * @author 陈妙威
     */
    protected function getValueFromRequest(AphrontRequest $request, $key)
    {
        return $request->getStr($key);
    }

    /**
     * @return AphrontFormTextControl
     * @author 陈妙威
     */
    protected function newControl()
    {
        return new AphrontFormTextControl();
    }

    /**
     * @return ConduitStringParameterType|null
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitStringParameterType();
    }

}
