<?php

namespace orangins\lib\request\httpparametertype;

use orangins\lib\request\AphrontRequest;
use yii\web\Request;

/**
 * Class AphrontPHIDListHTTPParameterType
 * @package orangins\lib\request\httpparametertype
 * @author 陈妙威
 */
final class AphrontPHIDListHTTPParameterType extends AphrontListHTTPParameterType
{

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return array
     * @author 陈妙威
     */
    protected function getParameterValue(AphrontRequest $request, $key)
    {
        $type = new AphrontStringListHTTPParameterType();
        return $this->getValueWithType($type, $request, $key);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'list<phid>';
    }

    /**
     * @return array|list
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app",'Comma-separated list of PHIDs.'),
            \Yii::t("app",'List of PHIDs, as array.'),
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
            'v=PHID-XXXX-1111,PHID-XXXX-2222',
            'v[]=PHID-XXXX-1111&v[]=PHID-XXXX-2222',
        );
    }

}
