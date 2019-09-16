<?php
namespace orangins\lib\infrastructure\daemon\workers\exception;

use yii\base\UserException;

final class PhabricatorWorkerPermanentFailureException extends UserException {}
