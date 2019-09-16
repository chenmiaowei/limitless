<?php

namespace orangins\modules\settings\setting;

use orangins\modules\transactions\editfield\PhabricatorSelectEditField;
use Exception;

/**
 * Class PhabricatorOptionGroupSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
abstract class PhabricatorOptionGroupSetting extends PhabricatorSetting
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getSelectOptionGroups();

    final protected function getSelectOptionMap()
    {
        $groups = $this->getSelectOptionGroups();

        $map = array();
        foreach ($groups as $group) {
            $map += $group['options'];
        }

        return $map;
    }

    /**
     * @param $object
     * @return null
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final protected function newCustomEditField($object)
    {
        $setting_key = $this->getSettingKey();
        $default_value = $object->getDefaultValue($setting_key);

        $options = $this->getSelectOptionGroups();

        $map = $this->getSelectOptionMap();
        if (isset($map[$default_value])) {
            $default_label = \Yii::t("app", 'Default ({0})', [$map[$default_value]]);
        } else {
            $default_label = \Yii::t("app", 'Default (Unknown, "{0}")', [$default_value]);
        }

        $head_key = head_key($options);
        $options[$head_key]['options'] = array(
                '' => $default_label,
            ) + $options[$head_key]['options'];

        $flat_options = array();
        foreach ($options as $group) {
            $flat_options[$group['label']] = $group['options'];
        }

        return $this
            ->newEditField($object, new PhabricatorSelectEditField())
            ->setOptions($flat_options);
    }

    /**
     * @param $value
     * @author 陈妙威
     * @throws Exception
     */
    final public function validateTransactionValue($value)
    {
        if (!strlen($value)) {
            return;
        }

        $map = $this->getSelectOptionMap();

        if (!isset($map[$value])) {
            throw new Exception(
                \Yii::t("app",
                    'Value "{0}" is not valid for setting "{1}": valid values are {2}.',
                    [
                        $value,
                        $this->getSettingName(),
                        implode(', ', array_keys($map))
                    ]));
        }

        return;
    }

    /**
     * @param $value
     * @return mixed|null|string
     * @author 陈妙威
     */
    public function getTransactionNewValue($value)
    {
        if (!strlen($value)) {
            return null;
        }

        return (string)$value;
    }

}
