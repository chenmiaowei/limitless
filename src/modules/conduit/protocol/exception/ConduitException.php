<?php
namespace orangins\modules\conduit\protocol\exception;

use yii\base\UserException;

/**
 * @concrete-extensible
 */
class ConduitException extends UserException {

  private $errorDescription;

  /**
   * Set a detailed error description. If omitted, the generic error description
   * will be used instead. This is useful to provide specific information about
   * an exception (e.g., which values were wrong in an invalid request).
   *
   * @param string Detailed error description.
   * @return static
   */
  final public function setErrorDescription($error_description) {
    $this->errorDescription = $error_description;
    return $this;
  }

  /**
   * Get a detailed error description, if available.
   *
   * @return string|null Error description, if one is available.
   */
  final public function getErrorDescription() {
    return $this->errorDescription;
  }

}
