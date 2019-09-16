<?php
namespace orangins\modules\people\actions;

abstract class PhabricatorPeopleInviteAction
  extends PhabricatorPeopleAction {

  protected function buildApplicationCrumbs() {
    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb(
      \Yii::t("app",'Invites'),
      $this->getApplicationURI('invite/'));
    return $crumbs;
  }

}
