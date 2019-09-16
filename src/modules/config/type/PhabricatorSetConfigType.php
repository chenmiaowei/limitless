<?php

namespace orangins\modules\config\type;

use orangins\lib\view\form\control\AphrontFormTextAreaControl;
use orangins\modules\config\option\PhabricatorConfigOption;
use Exception;

/**
 * Class PhabricatorSetConfigType
 * @package orangins\modules\config\type
 * @author 陈妙威
 */
final class PhabricatorSetConfigType
    extends PhabricatorTextConfigType
{

    /**
     *
     */
    const TYPEKEY = 'set';

    /**
     * @param PhabricatorConfigOption $option
     * @return \orangins\lib\view\form\control\AphrontFormTextControl
     * @author 陈妙威
     */
    protected function newControl(PhabricatorConfigOption $option)
    {
        return (new AphrontFormTextAreaControl())
            ->setCaption(\Yii::t("app",'Separate values with newlines or commas.'));
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return array|string
     * @author 陈妙威
     */
    protected function newCanonicalValue(
        PhabricatorConfigOption $option,
        $value)
    {

        $value = preg_split('/[\n,]+/', $value);
        foreach ($value as $k => $v) {
            if (!strlen($v)) {
                unset($value[$k]);
            }
            $value[$k] = trim($v);
        }

        return array_fill_keys($value, true);
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return array
     * @author 陈妙威
     */
    public function newValueFromCommandLineValue(
        PhabricatorConfigOption $option,
        $value)
    {

        try {
            $value = phutil_json_decode($value);
        } catch (Exception $ex) {
            throw $this->newException(
                \Yii::t("app",
                    'Option "{0}" is of type "{1}", but the value you provided is not a ' .
                    'valid JSON list: when providing a set from the command line, ' .
                    'specify it as a list of values in JSON. You may need to quote the ' .
                    'value for your shell (for example: \'["a", "b", ...]\').',
                    [
                        $option->getKey(),
                        $this->getTypeKey()
                    ]));
        }

        if ($value) {
            if (array_keys($value) !== range(0, count($value) - 1)) {
                throw $this->newException(
                    \Yii::t("app",
                        'Option "%s" is of type "%s", and should be specified on the ' .
                        'command line as a JSON list of values. You may need to quote ' .
                        'the value for your shell (for example: \'["a", "b", ...]\').',
                        $option->getKey(),
                        $this->getTypeKey()));
            }
        }

        return array_fill_keys($value, true);
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return string
     * @author 陈妙威
     */
    public function newDisplayValue(
        PhabricatorConfigOption $option,
        $value)
    {
        return implode("\n", array_keys($value));
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @author 陈妙威
     */
    public function validateStoredValue(
        PhabricatorConfigOption $option,
        $value)
    {

        if (!is_array($value)) {
            throw $this->newException(
                \Yii::t("app",
                    'Option "%s" is of type "%s", but the configured value is not ' .
                    'a list.',
                    $option->getKey(),
                    $this->getTypeKey()));
        }

        foreach ($value as $k => $v) {
            if ($v !== true) {
                throw $this->newException(
                    \Yii::t("app",
                        'Option "%s" is of type "%s", but the value at index "%s" of the ' .
                        'list is not "true".',
                        $option->getKey(),
                        $this->getTypeKey(),
                        $k));
            }
        }
    }

}
