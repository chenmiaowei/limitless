<?php
namespace orangins\modules\config\actions;

abstract class PhabricatorConfigDatabaseAction
  extends PhabricatorConfigAction {

  protected function renderIcon($status) {
    switch ($status) {
      case PhabricatorConfigStorageSchema::STATUS_OKAY:
        $icon = 'fa-check-circle green';
        break;
      case PhabricatorConfigStorageSchema::STATUS_WARN:
        $icon = 'fa-exclamation-circle yellow';
        break;
      case PhabricatorConfigStorageSchema::STATUS_FAIL:
      default:
        $icon = 'fa-times-circle red';
        break;
    }

    return (new PHUIIconView())
      ->setIcon($icon);
  }

  protected function renderAttr($attr, $issue) {
    if ($issue) {
      return phutil_tag(
        'span',
        array(
          'style' => 'color: #aa0000;',
        ),
        $attr);
    } else {
      return $attr;
    }
  }

  protected function renderBoolean($value) {
    if ($value === null) {
      return '';
    } else if ($value === true) {
      return \Yii::t("app",'Yes');
    } else {
      return \Yii::t("app",'No');
    }
  }

}
