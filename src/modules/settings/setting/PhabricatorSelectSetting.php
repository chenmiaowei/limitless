<?php

namespace orangins\modules\settings\setting;

use orangins\modules\settings\models\PhabricatorUserPreferences;
use orangins\modules\transactions\editfield\PhabricatorSelectEditField;
use Exception;

/**
 * Class PhabricatorSelectSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
abstract class PhabricatorSelectSetting
    extends PhabricatorSetting
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getSelectOptions();

    /**
     * @param PhabricatorUserPreferences $object
     * @return null
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final protected function newCustomEditField($object)
    {
        $setting_key = $this->getSettingKey();
        $default_value = $object->getDefaultValue($setting_key);

        $options = $this->getSelectOptions();

        if (isset($options[$default_value])) {
            $default_label = \Yii::t("app", 'Default ({0})', [$options[$default_value]]);
        } else {
            $default_label = \Yii::t("app", 'Default (Unknown, "{0}")', [$default_value]);
        }

        if (empty($options[''])) {
            $options = array(
                    '' => $default_label,
                ) + $options;
        }

        return $this
            ->newEditField($object, new PhabricatorSelectEditField())
            ->setOptions($options);
    }


    /**
     * @param $value
     * @author 陈妙威
     * @throws Exception
     */
    public function assertValidValue($value)
    {
        // This is a slightly stricter check than the transaction check. It's
        // OK for empty string to go through transactions because it gets converted
        // to null later, but we shouldn't be reading the empty string from
        // storage.
        if ($value === null) {
            return;
        }

        if (!strlen($value)) {
            throw new Exception(
                \Yii::t("app",
                    'Empty string is not a valid setting for "%s".',
                    $this->getSettingName()));
        }

        $this->validateTransactionValue($value);
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

        $options = $this->getSelectOptions();

        if (!isset($options[$value])) {
            throw new Exception(
                \Yii::t("app",
                    'Value "{0}" is not valid for setting "{1}": valid values are {2}.', [
                        $value,
                        $this->getSettingName(),
                        implode(', ', array_keys($options))
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
