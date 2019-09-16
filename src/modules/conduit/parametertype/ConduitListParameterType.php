<?php

namespace orangins\modules\conduit\parametertype;

/**
 * Class ConduitListParameterType
 * @package orangins\modules\conduit\parametertype
 * @author 陈妙威
 */
abstract class ConduitListParameterType
    extends ConduitParameterType
{

    /**
     * @var bool
     */
    private $allowEmptyList = true;

    /**
     * @param $allow_empty_list
     * @return $this
     * @author 陈妙威
     */
    public function setAllowEmptyList($allow_empty_list)
    {
        $this->allowEmptyList = $allow_empty_list;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getAllowEmptyList()
    {
        return $this->allowEmptyList;
    }

    /**
     * @param array $request
     * @param $key
     * @param $strict
     * @return mixed
     * @author 陈妙威
     */
    protected function getParameterValue(array $request, $key, $strict)
    {
        $value = parent::getParameterValue($request, $key, $strict);

        if (!is_array($value)) {
            $this->raiseValidationException(
                $request,
                $key,
                \Yii::t("app", 'Expected a list, but value is not a list.'));
        }

        $actual_keys = array_keys($value);
        if ($value) {
            $natural_keys = range(0, count($value) - 1);
        } else {
            $natural_keys = array();
        }

        if ($actual_keys !== $natural_keys) {
            $this->raiseValidationException(
                $request,
                $key,
                \Yii::t("app", 'Expected a list, but value is an object.'));
        }

        if (!$value && !$this->getAllowEmptyList()) {
            $this->raiseValidationException(
                $request,
                $key,
                \Yii::t("app", 'Expected a nonempty list, but value is an empty list.'));
        }

        return $value;
    }

    /**
     * @param array $request
     * @param $key
     * @param array $list
     * @param $strict
     * @return array
     * @author 陈妙威
     */
    protected function parseStringList(
        array $request,
        $key,
        array $list,
        $strict)
    {

        foreach ($list as $idx => $item) {
            $list[$idx] = $this->parseStringValue(
                $request,
                $key . '[' . $idx . ']',
                $item,
                $strict);
        }

        return $list;
    }

    /**
     * @return array|null
     * @author 陈妙威
     */
    protected function getParameterDefault()
    {
        return array();
    }

}
