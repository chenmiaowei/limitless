<?php

namespace orangins\lib\request\httpparametertype;

use orangins\lib\request\AphrontRequest;
use yii\web\Request;

/**
 * Class AphrontStringListHTTPParameterType
 * @package orangins\lib\request\httpparametertype
 * @author 陈妙威
 */
final class AphrontStringListHTTPParameterType extends AphrontListHTTPParameterType
{

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return wild
     * @author 陈妙威
     */
    protected function getParameterValue(AphrontRequest $request, $key)
    {
        $list = $request->getArr($key, null);

        if ($list === null) {
            $list = $request->getStrList($key);
        }

        return $list;
    }

    /**
     * @return array|wild
     * @author 陈妙威
     */
    protected function getParameterDefault()
    {
        return array();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'list<string>';
    }

    /**
     * @return array|list
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app",'Comma-separated list of strings.'),
            \Yii::t("app",'List of strings, as array.'),
        );
    }

    /**
     * @return array|list
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            'v=cat,dog,pig',
            'v[]=cat&v[]=dog',
        );
    }

}
