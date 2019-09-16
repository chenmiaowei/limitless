<?php
namespace orangins\modules\auth\exception;

/**
 * Exception raised when the user is logged in to the wrong account.
 */
final class PhabricatorAuthInviteAccountException
  extends PhabricatorAuthInviteDialogException {}
