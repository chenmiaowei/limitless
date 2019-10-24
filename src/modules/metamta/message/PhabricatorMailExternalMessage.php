<?php

namespace orangins\modules\metamta\message;

use Phobject;
use PhutilClassMapQuery;

/**
 * Class PhabricatorMailExternalMessage
 * @package orangins\modules\metamta\message
 * @author 陈妙威
 */
abstract class PhabricatorMailExternalMessage
    extends Phobject
{

    /**
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    final public function getMessageType()
    {
        return $this->getPhobjectClassConstant('MESSAGETYPE');
    }

    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    final public static function getAllMessageTypes()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getMessageType')
            ->execute();
    }

}
