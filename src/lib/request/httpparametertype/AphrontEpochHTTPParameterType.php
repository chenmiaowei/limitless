<?php

namespace orangins\lib\request\httpparametertype;

use orangins\lib\request\AphrontRequest;
use yii\web\Request;

/**
 * Class AphrontEpochHTTPParameterType
 * @package orangins\lib\request\httpparametertype
 * @author 陈妙威
 */
final class AphrontEpochHTTPParameterType extends AphrontHTTPParameterType
{

    /**
     * @var
     */
    private $allowNull;

    /**
     * @param $allow_null
     * @return $this
     * @author 陈妙威
     */
    public function setAllowNull($allow_null)
    {
        $this->allowNull = $allow_null;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAllowNull()
    {
        return $this->allowNull;
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    protected function getParameterExists(AphrontRequest $request, $key)
    {
        return $request->getExists($key) ||
            $request->getExists($key . '_d');
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return wild
     * @author 陈妙威
     */
    protected function getParameterValue(AphrontRequest $request, $key)
    {
        $value = AphrontFormDateControlValue::newFromRequest($request, $key);

        if ($this->getAllowNull()) {
            $value->setOptional(true);
        }

        return $value;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'epoch';
    }

    /**
     * @return array|list
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app",'An epoch timestamp, as an integer.'),
            \Yii::t("app",'An absolute date, as a string.'),
            \Yii::t("app",'A relative date, as a string.'),
            \Yii::t("app",'Separate date and time inputs, as strings.'),
        );
    }

    /**
     * @return array|list
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            'v=1460050737',
            'v=2022-01-01',
            'v=yesterday',
            'v_d=2022-01-01&v_t=12:34',
        );
    }

}
