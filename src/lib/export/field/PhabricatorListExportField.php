<?php
namespace orangins\lib\export\field;

abstract class PhabricatorListExportField
  extends PhabricatorExportField {

  public function getTextValue($value) {
    return implode("\n", $value);
  }

}
