<?php

namespace orangins\lib\request\httpparametertype;

use orangins\lib\request\AphrontRequest;
use yii\web\Request;

/**
 * Class AphrontBoolHTTPParameterType
 * @package orangins\lib\request\httpparametertype
 * @author 陈妙威
 */
final class AphrontBoolHTTPParameterType extends AphrontHTTPParameterType
{

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    protected function getParameterExists(AphrontRequest $request, $key)
    {
        if ($request->getExists($key)) {
            return true;
        }

        $checkbox_key = $this->getCheckboxKey($key);
        if ($request->getExists($checkbox_key)) {
            return true;
        }

        return false;
    }

    /**
     * @param Request $request
     * @param $key
     * @return wild|bool
     * @author 陈妙威
     */
    protected function getParameterValue(AphrontRequest $request, $key)
    {
        return (bool)$request->getBool($key);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'bool';
    }

    /**
     * @return array|list
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app", 'A boolean value (true or false).'),
        );
    }

    /**
     * @return array|list
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            'v=true',
            'v=false',
            'v=1',
            'v=0',
        );
    }

    /**
     * @param $key
     * @return string
     * @author 陈妙威
     */
    public function getCheckboxKey($key)
    {
        return "{$key}.exists";
    }

}
