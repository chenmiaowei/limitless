<?php

namespace orangins\modules\conduit\parametertype;

/**
 * Class ConduitIntListParameterType
 * @package orangins\modules\conduit\parametertype
 * @author 陈妙威
 */
final class ConduitIntListParameterType
    extends ConduitListParameterType
{

    /**
     * @param array $request
     * @param $key
     * @param $strict
     * @return mixed
     * @author 陈妙威
     */
    protected function getParameterValue(array $request, $key, $strict)
    {
        $list = parent::getParameterValue($request, $key, $strict);

        foreach ($list as $idx => $item) {
            $list[$idx] = $this->parseIntValue(
                $request,
                $key . '[' . $idx . ']',
                $item,
                $strict);
        }

        return $list;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'list<int>';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app", 'List of integers.'),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            '[123, 0, -456]',
        );
    }

}
