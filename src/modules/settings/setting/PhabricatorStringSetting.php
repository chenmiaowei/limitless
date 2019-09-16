<?php

namespace orangins\modules\settings\setting;

use orangins\modules\transactions\editfield\PhabricatorEditField;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;

/**
 * Class PhabricatorStringSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
abstract class PhabricatorStringSetting
    extends PhabricatorSetting
{

    /**
     * @param \orangins\modules\widgets\ActiveFormWidgetView $object
     * @return PhabricatorEditField
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final protected function newCustomEditField($object)
    {
        return $this->newEditField($object, new PhabricatorTextEditField());
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
