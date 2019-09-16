<?php

namespace orangins\modules\auth\engine;

use orangins\lib\OranginsObject;
use orangins\modules\auth\data\PhabricatorAuthSessionInfo;
use orangins\modules\people\models\PhabricatorUser;
use PhutilClassMapQuery;

/**
 * Class PhabricatorAuthSessionEngineExtension
 * @package orangins\modules\auth\engine
 * @author 陈妙威
 */
abstract class PhabricatorAuthSessionEngineExtension
    extends OranginsObject
{

    /**
     * @return string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final public function getExtensionKey()
    {
        return $this->getPhobjectClassConstant('EXTENSIONKEY');
    }

    /**
     * @return PhabricatorAuthSessionEngineExtension[]
     * @author 陈妙威
     */
    final public static function getAllExtensions()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getExtensionKey')
            ->execute();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getExtensionName();

    /**
     * @param PhabricatorAuthSessionInfo $info
     * @author 陈妙威
     */
    public function didEstablishSession(PhabricatorAuthSessionInfo $info)
    {
        return;
    }

    /**
     * @param PhabricatorUser $user
     * @author 陈妙威
     */
    public function willServeRequestForUser(PhabricatorUser $user)
    {
        return;
    }

    /**
     * @param PhabricatorUser $user
     * @param array $sessions
     * @author 陈妙威
     */
    public function didLogout(PhabricatorUser $user, array $sessions)
    {
        return;
    }

}
