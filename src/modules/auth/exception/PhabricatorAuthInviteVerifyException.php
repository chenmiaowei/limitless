<?php
namespace orangins\modules\auth\exception;

/**
 * Exception raised when the user needs to verify an action.
 */
final class PhabricatorAuthInviteVerifyException
  extends PhabricatorAuthInviteDialogException {}
