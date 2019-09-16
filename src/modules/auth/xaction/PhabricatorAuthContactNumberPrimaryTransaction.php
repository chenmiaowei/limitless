<?php
namespace orangins\modules\auth\xaction;

final class PhabricatorAuthContactNumberPrimaryTransaction
  extends PhabricatorAuthContactNumberTransactionType {

  const TRANSACTIONTYPE = 'primary';

  public function generateOldValue($object) {
    return (bool)$object->getIsPrimary();
  }

  public function applyInternalEffects($object, $value) {
    $object->setIsPrimary((int)$value);
  }

  public function getTitle() {
    return \Yii::t("app",
      '%s made this the primary contact number.',
      $this->renderAuthor());
  }

  public function validateTransactions($object, array $xactions) {
    $errors = array();

    foreach ($xactions as $xaction) {
      $new_value = $xaction->getNewValue();

      if (!$new_value) {
        $errors[] = $this->newInvalidError(
          \Yii::t("app",
            'To choose a different primary contact number, make that '.
            'number primary (instead of trying to demote this one).'),
          $xaction);
        continue;
      }

      if ($object->isDisabled()) {
        $errors[] = $this->newInvalidError(
          \Yii::t("app",
            'You can not make a disabled number a primary contact number.'),
          $xaction);
        continue;
      }

      $mfa_error = $this->newContactNumberMFAError($object, $xaction);
      if ($mfa_error) {
        $errors[] = $mfa_error;
        continue;
      }
    }

    return $errors;
  }

}
