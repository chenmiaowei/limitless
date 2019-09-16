<?php
namespace orangins\modules\search\field;

use orangins\lib\request\AphrontRequest;

final class PhabricatorIDsSearchField
  extends PhabricatorSearchField {

  protected function getDefaultValue() {
    return array();
  }

  protected function getValueFromRequest(AphrontRequest $request, $key) {
    return $request->getStrList($key);
  }

  protected function newControl() {
    if (strlen($this->getValueForControl())) {
      return new AphrontFormTextControl();
    } else {
      return null;
    }
  }

  protected function getValueForControl() {
    return implode(', ', parent::getValueForControl());
  }

  protected function newConduitParameterType() {
    return (new ConduitIntListParameterType())
      ->setAllowEmptyList(false);
  }

}
