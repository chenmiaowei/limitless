<?php

namespace orangins\lib\request\httpparametertype;

use orangins\lib\request\AphrontRequest;
use yii\web\Request;

/**
 * Class AphrontPHIDHTTPParameterType
 * @package orangins\lib\request\httpparametertype
 * @author 陈妙威
 */
final class AphrontPHIDHTTPParameterType extends AphrontHTTPParameterType
{

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return wild
     * @author 陈妙威
     */
    protected function getParameterValue(AphrontRequest $request, $key)
    {
        return $request->getStr($key);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'phid';
    }

    /**
     * @return array|list
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app",'A single object PHID.'),
        );
    }

    /**
     * @return array|list
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            'v=PHID-XXXX-1111',
        );
    }

}
