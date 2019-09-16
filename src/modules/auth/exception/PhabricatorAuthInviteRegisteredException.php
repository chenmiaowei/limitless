<?php
namespace orangins\modules\auth\exception;

/**
 * Exception raised when the user is already registered and the invite is a
 * no-op.
 */
final class PhabricatorAuthInviteRegisteredException
  extends PhabricatorAuthInviteException {}
