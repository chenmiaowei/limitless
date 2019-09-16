<?php
namespace orangins\modules\config\check;

final class PhabricatorFileinfoSetupCheck extends PhabricatorSetupCheck {

  public function getDefaultGroup() {
    return self::GROUP_OTHER;
  }

  protected function executeChecks() {
    if (!extension_loaded('fileinfo')) {
      $message = \Yii::t("app",
        "The '%s' extension is not installed. Without '%s', ".
        "support, Phabricator may not be able to determine the MIME types ".
        "of uploaded files.",
        'fileinfo',
        'fileinfo');

      $this->newIssue('extension.fileinfo')
        ->setName(\Yii::t("app","Missing '%s' Extension", 'fileinfo'))
        ->setMessage($message);
    }
  }
}
