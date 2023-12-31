<?php
namespace orangins\modules\transactions\editfield;

final class PhabricatorIntEditField
  extends PhabricatorEditField {

  protected function newControl() {
    return new AphrontFormTextControl();
  }

  protected function newConduitParameterType() {
    return new ConduitIntParameterType();
  }

}
