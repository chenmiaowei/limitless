<?php

namespace orangins\modules\conduit\parametertype;

/**
 * Class ConduitStringListParameterType
 * @package orangins\modules\conduit\parametertype
 * @author 陈妙威
 */
final class ConduitStringListParameterType
    extends ConduitListParameterType
{

    /**
     * @param array $request
     * @param $key
     * @param $strict
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getParameterValue(array $request, $key, $strict)
    {
        $list = parent::getParameterValue($request, $key, $strict);
        return $this->parseStringList($request, $key, $list, $strict);
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'list<string>';
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app", 'List of strings.'),
        );
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            '["mango", "nectarine"]',
        );
    }

}
