<?php
namespace orangins\lib\export\field;

final class PhabricatorPHIDListExportField
  extends PhabricatorListExportField {

  public function getCharacterWidth() {
    return 32;
  }

}
