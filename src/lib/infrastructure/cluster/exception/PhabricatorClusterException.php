<?php

namespace orangins\lib\infrastructure\cluster\exception;

use Exception;

abstract class PhabricatorClusterException
    extends Exception
{

    abstract public function getExceptionTitle();

}
