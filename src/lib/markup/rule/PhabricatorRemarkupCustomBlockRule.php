<?php
namespace orangins\lib\markup\rule;

use PhutilRemarkupBlockRule;

abstract class PhabricatorRemarkupCustomBlockRule
  extends PhutilRemarkupBlockRule {

  public function getRuleVersion() {
    return 1;
  }

}
