<?php
namespace orangins\modules\config\type;

use orangins\modules\config\option\PhabricatorConfigOption;

/**
 * Class PhabricatorStringConfigType
 * @package orangins\modules\config\type
 * @author 陈妙威
 */
final class PhabricatorStringConfigType
  extends PhabricatorTextConfigType {

    /**
     *
     */
    const TYPEKEY = 'string';

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @author 陈妙威
     */public function validateStoredValue(
    PhabricatorConfigOption $option,
    $value) {

    if (!is_string($value)) {
      throw $this->newException(
        \Yii::t("app",
          'Option "%s" is of type "%s", but the configured value is not '.
          'a string.',
          $option->getKey(),
          $this->getTypeKey()));
    }
  }
}
