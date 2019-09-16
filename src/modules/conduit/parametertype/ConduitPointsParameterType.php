<?php

namespace orangins\modules\conduit\parametertype;

/**
 * Class ConduitPointsParameterType
 * @package orangins\modules\conduit\parametertype
 * @author 陈妙威
 */
final class ConduitPointsParameterType
    extends ConduitParameterType
{

    /**
     * @param array $request
     * @param $key
     * @param $strict
     * @return float|mixed
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getParameterValue(array $request, $key, $strict)
    {
        $value = parent::getParameterValue($request, $key, $strict);

        if (($value !== null) && !is_numeric($value)) {
            $this->raiseValidationException(
                $request,
                $key,
                \Yii::t("app", 'Expected numeric points value, got something else.'));
        }

        if ($value !== null) {
            $value = (double)$value;
            if ($value < 0) {
                $this->raiseValidationException(
                    $request,
                    $key,
                    \Yii::t("app", 'Point values must be nonnegative.'));
            }
        }

        return $value;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'points';
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app", 'A nonnegative number, or null.'),
        );
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            'null',
            '0',
            '1',
            '15',
            '0.5',
        );
    }

}
