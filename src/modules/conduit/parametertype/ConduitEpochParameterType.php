<?php

namespace orangins\modules\conduit\parametertype;

/**
 * Class ConduitEpochParameterType
 * @package orangins\modules\conduit\parametertype
 * @author 陈妙威
 */
final class ConduitEpochParameterType
    extends ConduitParameterType
{

    /**
     * @param array $request
     * @param $key
     * @param $strict
     * @return int|mixed|string
     * @author 陈妙威
     */
    protected function getParameterValue(array $request, $key, $strict)
    {
        $value = parent::getParameterValue($request, $key, $strict);
        $value = $this->parseIntValue($request, $key, $value, $strict);

        if ($value <= 0) {
            $this->raiseValidationException(
                $request,
                $key,
                \Yii::t("app", 'Epoch timestamp must be larger than 0, got %d.', $value));
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
     * @return array
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app", 'Epoch timestamp, as an integer.'),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            '1450019509',
        );
    }

}
