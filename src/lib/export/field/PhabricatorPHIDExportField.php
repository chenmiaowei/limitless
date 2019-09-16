<?php
namespace orangins\lib\export\field;

final class PhabricatorPHIDExportField
  extends PhabricatorExportField {

  public function getCharacterWidth() {
    return 32;
  }

}
