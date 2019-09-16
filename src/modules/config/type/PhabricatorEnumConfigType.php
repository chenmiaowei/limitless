<?php

namespace orangins\modules\config\type;

use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\modules\config\option\PhabricatorConfigOption;

/**
 * Class PhabricatorEnumConfigType
 * @package orangins\modules\config\type
 * @author 陈妙威
 */
final class PhabricatorEnumConfigType
    extends PhabricatorTextConfigType
{

    /**
     *
     */
    const TYPEKEY = 'enum';

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed|void
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \orangins\modules\config\exception\PhabricatorConfigValidationException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function validateStoredValue(
        PhabricatorConfigOption $option,
        $value)
    {

        if (!is_string($value)) {
            throw $this->newException(
                \Yii::t("app",
                    'Option "%s" is of type "%s", but the configured value is not ' .
                    'a string.',
                    $option->getKey(),
                    $this->getTypeKey()));
        }

        $map = $option->getEnumOptions();
        if (!isset($map[$value])) {
            throw $this->newException(
                \Yii::t("app",
                    'Option "%s" is of type "%s", but the current value ("%s") is not ' .
                    'among the set of valid values: %s.',
                    $option->getKey(),
                    $this->getTypeKey(),
                    $value,
                    implode(', ', array_keys($map))));
        }
    }

    /**
     * @param PhabricatorConfigOption $option
     * @return \orangins\lib\view\form\control\AphrontFormTextControl
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function newControl(PhabricatorConfigOption $option)
    {
        $map = array(
                '' => \Yii::t("app",'(Use Default)'),
            ) + $option->getEnumOptions();

        return (new AphrontFormSelectControl())
            ->setOptions($map);
    }
}
