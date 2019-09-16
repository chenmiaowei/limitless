<?php

namespace orangins\modules\config\type;

use orangins\lib\view\form\control\AphrontFormTextAreaControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\config\option\PhabricatorConfigOption;
use Exception;

/**
 * Class PhabricatorTextListConfigType
 * @package orangins\modules\config\type
 * @author 陈妙威
 */
abstract class PhabricatorTextListConfigType
    extends PhabricatorTextConfigType
{

    /**
     * @param PhabricatorConfigOption $option
     * @return AphrontFormTextControl
     * @author 陈妙威
     */
    protected function newControl(PhabricatorConfigOption $option)
    {
        return (new AphrontFormTextAreaControl())
            ->setCaption(\Yii::t("app",'Separate values with newlines.'));
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

        $value = phutil_split_lines($value, $retain_endings = false);
        foreach ($value as $k => $v) {
            if (!strlen($v)) {
                unset($value[$k]);
            }
        }

        return array_values($value);
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed
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
                    'valid JSON list. When setting a list option from the command ' .
                    'line, specify the value in JSON. You may need to quote the ' .
                    'value for your shell (for example: \'["a", "b", ...]\').',
                    [
                        $option->getKey(),
                        $this->getTypeKey()
                    ]));
        }

        return $value;
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
        return implode("\n", $value);
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

        $expect_key = 0;
        foreach ($value as $k => $v) {
            if (!is_string($v)) {
                throw $this->newException(
                    \Yii::t("app",
                        'Option "%s" is of type "%s", but the item at index "%s" of the ' .
                        'list is not a string.',
                        $option->getKey(),
                        $this->getTypeKey(),
                        $k));
            }

            // Make sure this is a list with keys "0, 1, 2, ...", not a map with
            // arbitrary keys.
            if ($k != $expect_key) {
                throw $this->newException(
                    \Yii::t("app",
                        'Option "%s" is of type "%s", but the value is not a list: it ' .
                        'is a map with unnatural or sparse keys.',
                        $option->getKey(),
                        $this->getTypeKey()));
            }
            $expect_key++;

            $this->validateStoredItem($option, $v);
        }
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @author 陈妙威
     */
    protected function validateStoredItem(
        PhabricatorConfigOption $option,
        $value)
    {
        return;
    }

}
