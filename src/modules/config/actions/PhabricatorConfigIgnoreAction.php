<?php
namespace orangins\modules\config\actions;

final class PhabricatorConfigIgnoreAction
  extends PhabricatorConfigAction {

  public function run() { $request = $this->getRequest();
    $viewer = $request->getViewer();
    $issue = $request->getURIData('key');
    $verb = $request->getURIData('verb');

    $issue_uri = $this->getApplicationURI('issue/'.$issue.'/');

    if ($request->isDialogFormPost()) {
      $this->manageApplication($issue);
      return (new AphrontRedirectResponse())->setURI($issue_uri);
    }

    if ($verb == 'ignore') {
      $title = \Yii::t("app",'Really ignore this setup issue?');
      $submit_title = \Yii::t("app",'Ignore');
      $body = \Yii::t("app",
        "You can ignore an issue if you don't want to fix it, or plan to ".
        "fix it later. Ignored issues won't appear on every page but will ".
        "still be shown in the list of open issues.");
    } else if ($verb == 'unignore') {
      $title = \Yii::t("app",'Unignore this setup issue?');
      $submit_title = \Yii::t("app",'Unignore');
      $body = \Yii::t("app",
        'This issue will no longer be suppressed, and will return to its '.
        'rightful place as a global setup warning.');
    } else {
      throw new Exception(\Yii::t("app",'Unrecognized verb: %s', $verb));
    }

    return $this->newDialog()
      ->setTitle($title)
      ->appendChild($body)
      ->addSubmitButton($submit_title)
      ->addCancelButton($issue_uri);

  }

  public function manageApplication($issue) {
    $key = 'config.ignore-issues';
    $config_entry = PhabricatorConfigEntry::loadConfigEntry($key);
    $list = $config_entry->getValue();

    if (isset($list[$issue])) {
      unset($list[$issue]);
    } else {
      $list[$issue] = true;
    }

    PhabricatorConfigEditor::storeNewValue(
      $this->getRequest()->getViewer(),
      $config_entry,
      $list,
      PhabricatorContentSource::newFromRequest($this->getRequest()));
  }

}
