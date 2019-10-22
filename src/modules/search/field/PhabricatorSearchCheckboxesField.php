<?php

namespace orangins\modules\search\field;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\control\AphrontFormCheckboxControl;
use orangins\modules\conduit\data\ConduitConstantDescription;
use orangins\modules\conduit\parametertype\ConduitStringListParameterType;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorSearchCheckboxesField
 * @package orangins\modules\search\field
 * @author 陈妙威
 */
final class PhabricatorSearchCheckboxesField extends PhabricatorSearchField
{

    /**
     * @var
     */
    private $options;
    /**
     * @var array
     */
    private $deprecatedOptions = array();

    /**
     * @param array $options
     * @return $this
     * @author 陈妙威
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $deprecated_options
     * @return $this
     * @author 陈妙威
     */
    public function setDeprecatedOptions(array $deprecated_options)
    {
        $this->deprecatedOptions = $deprecated_options;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getDeprecatedOptions()
    {
        return $this->deprecatedOptions;
    }

    /**
     * @return array|null
     * @author 陈妙威
     */
    protected function getDefaultValue()
    {
        return array();
    }

    /**
     * @param $value
     * @return array|mixed
     * @author 陈妙威
     */
    protected function didReadValueFromSavedQuery($value)
    {
        if (!is_array($value)) {
            return array();
        }

        return $this->getCanonicalValue($value);
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getValueFromRequest(AphrontRequest $request, $key)
    {
        $value = $this->getListFromRequest($request, $key);
        return $this->getCanonicalValue($value);
    }

    /**
     * @return AphrontFormCheckboxControl
     * @author 陈妙威
     */
    protected function newControl()
    {
        $value = array_fuse($this->getValue());

        $control = new AphrontFormCheckboxControl();
        foreach ($this->getOptions() as $key => $option) {
            $control->addCheckbox(
                $this->getKey() . '[]',
                $key,
                $option,
                isset($value[$key]));
        }

        return $control;
    }

    /**
     * @return ConduitStringListParameterType|null
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitStringListParameterType();
    }

    /**
     * @return ConduitConstantDescription[]
     * @author 陈妙威
     */
    public function newConduitConstants()
    {
        $list = array();

        foreach ($this->getOptions() as $key => $option) {
            $list[] = (new ConduitConstantDescription())
                ->setKey($key)
                ->setValue($option);
        }

        foreach ($this->getDeprecatedOptions() as $key => $value) {
            $list[] = (new ConduitConstantDescription())
                ->setKey($key)
                ->setIsDeprecated(true)
                ->setValue(\Yii::t("app", 'Deprecated alias for "%s".', $value));
        }

        return $list;
    }

    /**
     * @param array $values
     * @return array
     * @author 陈妙威
     */
    private function getCanonicalValue(array $values)
    {
        // Always map the current normal options to themselves.
        $normal_options = array_fuse(array_keys($this->getOptions()));

        // Map deprecated values to their new values.
        $deprecated_options = $this->getDeprecatedOptions();

        $map = $normal_options + $deprecated_options;
        foreach ($values as $key => $value) {
            $values[$key] = ArrayHelper::getValue($map, $value, $value);
        }

        return $values;
    }

}
