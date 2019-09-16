<?php

namespace orangins\modules\auth\tokentype;

use orangins\modules\auth\models\PhabricatorAuthTemporaryToken;

/**
 * Class PhabricatorAuthOneTimeLoginTemporaryTokenType
 * @package orangins\modules\auth\tokentype
 * @author 陈妙威
 */
final class PhabricatorAuthOneTimeLoginTemporaryTokenType extends PhabricatorAuthTemporaryTokenType
{

    /**
     *
     */
    const TOKENTYPE = 'login:onetime';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTokenTypeDisplayName()
    {
        return \Yii::t("app",'One-Time Login');
    }

    /**
     * @param PhabricatorAuthTemporaryToken $token
     * @return mixed
     * @author 陈妙威
     */
    public function getTokenReadableTypeName(PhabricatorAuthTemporaryToken $token)
    {
        return \Yii::t("app",'One-Time Login Token');
    }

    /**
     * @param PhabricatorAuthTemporaryToken $token
     * @return bool
     * @author 陈妙威
     */
    public function isTokenRevocable(PhabricatorAuthTemporaryToken $token)
    {
        return true;
    }

}
