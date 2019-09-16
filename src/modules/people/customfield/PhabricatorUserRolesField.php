<?php
namespace orangins\modules\people\customfield;

final class PhabricatorUserRolesField
  extends PhabricatorUserCustomField {

  private $value;

  public function getFieldKey() {
    return 'user:roles';
  }

  public function getFieldName() {
    return \Yii::t("app",'Roles');
  }

  public function getFieldDescription() {
    return \Yii::t("app",'Shows roles like "Administrator" and "Disabled".');
  }

  public function shouldAppearInPropertyView() {
    return true;
  }

  public function renderPropertyViewValue(array $handles) {
    $user = $this->getObject();

    $roles = array();
    if ($user->getIsAdmin()) {
      $roles[] = \Yii::t("app",'Administrator');
    }
    if ($user->getIsDisabled()) {
      $roles[] = \Yii::t("app",'Disabled');
    }
    if (!$user->getIsApproved()) {
      $roles[] = \Yii::t("app",'Not Approved');
    }
    if ($user->getIsSystemAgent()) {
      $roles[] = \Yii::t("app",'Bot');
    }
    if ($user->getIsMailingList()) {
      $roles[] = \Yii::t("app",'Mailing List');
    }

    if ($roles) {
      return implode(', ', $roles);
    }

    return null;
  }

}
