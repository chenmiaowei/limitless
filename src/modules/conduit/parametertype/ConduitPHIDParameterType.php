<?php

namespace orangins\modules\conduit\parametertype;

/**
 * Class ConduitPHIDParameterType
 * @package orangins\modules\conduit\parametertype
 * @author 陈妙威
 */
final class ConduitPHIDParameterType
    extends ConduitParameterType
{

    /**
     * @var
     */
    private $isNullable;

    /**
     * @param $is_nullable
     * @return $this
     * @author 陈妙威
     */
    public function setIsNullable($is_nullable)
    {
        $this->isNullable = $is_nullable;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsNullable()
    {
        return $this->isNullable;
    }

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

        if ($this->getIsNullable()) {
            if ($value === null) {
                return $value;
            }
        }

        if (!is_string($value)) {
            $this->raiseValidationException(
                $request,
                $key,
                \Yii::t("app", 'Expected PHID, got something else.'));
        }

        return $value;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        if ($this->getIsNullable()) {
            return 'phid|null';
        } else {
            return 'phid';
        }
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app", 'A PHID.'),
        );
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        $examples = array(
            '"PHID-WXYZ-1111222233334444"',
        );

        if ($this->getIsNullable()) {
            $examples[] = 'null';
        }

        return $examples;
    }

}
