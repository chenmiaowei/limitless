<?php
namespace orangins\modules\config\type;

use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\modules\config\option\PhabricatorConfigOption;

/**
 * Class PhabricatorBoolConfigType
 * @package orangins\modules\config\type
 * @author 陈妙威
 */
final class PhabricatorBoolConfigType
  extends PhabricatorTextConfigType {

    /**
     *
     */
    const TYPEKEY = 'bool';

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return bool|string
     * @throws \ReflectionException
     * @throws \orangins\modules\config\exception\PhabricatorConfigValidationException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */protected function newCanonicalValue(
    PhabricatorConfigOption $option,
    $value) {

    if (!preg_match('/^(true|false)\z/', $value)) {
      throw $this->newException(
        \Yii::t("app",
          'Value for option "%s" of type "%s" must be either '.
          '"true" or "false".',
          $option->getKey(),
          $this->getTypeKey()));
    }

    return ($value === 'true');
  }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed|string
     * @author 陈妙威
     */public function newDisplayValue(
    PhabricatorConfigOption $option,
    $value) {

    if ($value) {
      return 'true';
    } else {
      return 'false';
    }
  }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed|void
     * @throws \ReflectionException
     * @throws \orangins\modules\config\exception\PhabricatorConfigValidationException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */public function validateStoredValue(
    PhabricatorConfigOption $option,
    $value) {

    if (!is_bool($value)) {
      throw $this->newException(
        \Yii::t("app",
          'Option "%s" is of type "%s", but the configured value is not '.
          'a boolean.',
          $option->getKey(),
          $this->getTypeKey()));
    }
  }

    /**
     * @param PhabricatorConfigOption $option
     * @return \orangins\lib\view\form\control\AphrontFormTextControl
     * @author 陈妙威
     */protected function newControl(PhabricatorConfigOption $option) {
    $bool_map = $option->getBoolOptions();

    $map = array(
      '' => \Yii::t("app",'(Use Default)'),
    ) + array(
      'true'  => ArrayHelper::getValue($bool_map, 0),
      'false' => ArrayHelper::getValue($bool_map, 1),
    );

    return (new AphrontFormSelectControl())
      ->setOptions($map);
  }
}
