<?php
namespace orangins\lib\markup\rule;

use PhutilRemarkupRule;

abstract class PhabricatorRemarkupCustomInlineRule extends PhutilRemarkupRule {

  public function getRuleVersion() {
    return 1;
  }

}
