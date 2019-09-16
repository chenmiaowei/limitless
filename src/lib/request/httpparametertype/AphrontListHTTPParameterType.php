<?php

namespace orangins\lib\request\httpparametertype;

/**
 * Class AphrontListHTTPParameterType
 * @package orangins\lib\request\httpparametertype
 * @author 陈妙威
 */
abstract class AphrontListHTTPParameterType extends AphrontHTTPParameterType
{

    /**
     * @return array|wild
     * @author 陈妙威
     */
    protected function getParameterDefault()
    {
        return array();
    }

}
