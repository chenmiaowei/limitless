<?php
namespace orangins\modules\auth\view;

use orangins\lib\view\AphrontView;

final class PhabricatorAuthInviteActionTableView extends AphrontView {

  private $inviteActions;
  private $handles;

  public function setInviteActions(array $invite_actions) {
    $this->inviteActions = $invite_actions;
    return $this;
  }

  public function getInviteActions() {
    return $this->inviteActions;
  }

  public function setHandles(array $handles) {
    $this->handles = $handles;
    return $this;
  }

  public function render() {
    $actions = $this->getInviteActions();
    $handles = $this->handles;

    $rows = array();
    $rowc = array();
    foreach ($actions as $action) {
      $issues = $action->getIssues();
      foreach ($issues as $key => $issue) {
        $issues[$key] = $action->getShortNameForIssue($issue);
      }
      $issues = implode(', ', $issues);

      if (!$action->willSend()) {
        $rowc[] = 'highlighted';
      } else {
        $rowc[] = null;
      }

      $action_icon = $action->getIconForAction($action->getAction());
      $action_name = $action->getShortNameForAction($action->getAction());

      $rows[] = array(
        $action->getRawInput(),
        $action->getEmailAddress(),
        ($action->getUserPHID()
          ? $handles[$action->getUserPHID()]->renderLink()
          : null),
        $issues,
        $action_icon,
        $action_name,
      );
    }

    $table = (new AphrontTableView($rows))
      ->setRowClasses($rowc)
      ->setHeaders(
        array(
          \Yii::t("app",'Raw Address'),
          \Yii::t("app",'Parsed Address'),
          \Yii::t("app",'User'),
          \Yii::t("app",'Issues'),
          null,
          \Yii::t("app",'Action'),
        ))
      ->setColumnClasses(
        array(
          '',
          '',
          '',
          'wide',
          'icon',
          '',
        ));

    return $table;
  }

}
