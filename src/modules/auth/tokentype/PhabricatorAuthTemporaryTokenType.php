<?php

namespace orangins\modules\auth\tokentype;

use orangins\modules\auth\models\PhabricatorAuthTemporaryToken;
use orangins\lib\OranginsObject;
use PhutilClassMapQuery;

/**
 * Class PhabricatorAuthTemporaryTokenType
 * @package orangins\modules\auth\tokentype
 * @author 陈妙威
 */
abstract class PhabricatorAuthTemporaryTokenType extends OranginsObject
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getTokenTypeDisplayName();

    /**
     * @param PhabricatorAuthTemporaryToken $token
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getTokenReadableTypeName(PhabricatorAuthTemporaryToken $token);

    /**
     * @param PhabricatorAuthTemporaryToken $token
     * @return bool
     * @author 陈妙威
     */
    public function isTokenRevocable(PhabricatorAuthTemporaryToken $token)
    {
        return false;
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final public function getTokenTypeConstant()
    {
        return $this->getPhobjectClassConstant('TOKENTYPE', 64);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public static function getAllTypes()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getTokenTypeConstant')
            ->execute();
    }
}
