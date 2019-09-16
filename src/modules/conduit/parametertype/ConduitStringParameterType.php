<?php

namespace orangins\modules\conduit\parametertype;

/**
 * Class ConduitStringParameterType
 * @package orangins\modules\conduit\parametertype
 * @author 陈妙威
 */
final class ConduitStringParameterType
    extends ConduitParameterType
{

    /**
     * @param array $request
     * @param $key
     * @param $strict
     * @return mixed
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getParameterValue(array $request, $key, $strict)
    {
        $value = parent::getParameterValue($request, $key, $strict);
        return $this->parseStringValue($request, $key, $value, $strict);
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'string';
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app", 'A string.'),
        );
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            '"papaya"',
        );
    }
}
