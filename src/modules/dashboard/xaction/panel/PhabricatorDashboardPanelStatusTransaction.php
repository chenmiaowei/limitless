<?php
namespace orangins\modules\dashboard\xaction\panel;

final class PhabricatorDashboardPanelStatusTransaction
  extends PhabricatorDashboardPanelTransactionType {

  const TRANSACTIONTYPE = 'dashpanel:archive';

  public function generateOldValue($object) {
    return (bool)$object->getIsArchived();
  }

  public function generateNewValue($object, $value) {
    return (bool)$value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setIsArchived((int)$value);
  }

  public function getTitle() {
    $new = $this->getNewValue();
    if ($new) {
      return \Yii::t("app",
        '%s archived this panel.',
        $this->renderAuthor());
    } else {
      return \Yii::t("app",
        '%s activated this panel.',
        $this->renderAuthor());
    }
  }

}
