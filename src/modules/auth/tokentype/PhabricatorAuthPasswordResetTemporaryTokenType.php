<?php

namespace orangins\modules\auth\tokentype;

use orangins\modules\auth\models\PhabricatorAuthTemporaryToken;

/**
 * Class PhabricatorAuthPasswordResetTemporaryTokenType
 * @package orangins\modules\auth\tokentype
 * @author 陈妙威
 */
final class PhabricatorAuthPasswordResetTemporaryTokenType
    extends PhabricatorAuthTemporaryTokenType
{

    /**
     *
     */
    const TOKENTYPE = 'login:password';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTokenTypeDisplayName()
    {
        return \Yii::t("app",'Password Reset');
    }

    /**
     * @param PhabricatorAuthTemporaryToken $token
     * @return mixed
     * @author 陈妙威
     */
    public function getTokenReadableTypeName(PhabricatorAuthTemporaryToken $token)
    {
        return \Yii::t("app",'Password Reset Token');
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
