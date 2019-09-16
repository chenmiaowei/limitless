<?php

namespace orangins\modules\config\type;

use orangins\modules\config\option\PhabricatorConfigOption;

/**
 * Class PhabricatorIntConfigType
 * @package orangins\modules\config\type
 * @author 陈妙威
 */
final class PhabricatorIntConfigType
    extends PhabricatorTextConfigType
{

    /**
     *
     */
    const TYPEKEY = 'int';

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return int|string
     * @throws \orangins\modules\config\exception\PhabricatorConfigValidationException
     * @author 陈妙威
     */
    protected function newCanonicalValue(
        PhabricatorConfigOption $option,
        $value)
    {

        if (!preg_match('/^-?[0-9]+\z/', $value)) {
            throw $this->newException(
                \Yii::t("app",
                    'Value for option "%s" must be an integer.',
                    $option->getKey()));
        }

        return (int)$value;
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed|void
     * @throws \ReflectionException
     * @throws \orangins\modules\config\exception\PhabricatorConfigValidationException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function validateStoredValue(
        PhabricatorConfigOption $option,
        $value)
    {

        if (!is_int($value)) {
            throw $this->newException(
                \Yii::t("app",
                    'Option "%s" is of type "%s", but the configured value is not ' .
                    'an integer.',
                    $option->getKey(),
                    $this->getTypeKey()));
        }
    }
}
