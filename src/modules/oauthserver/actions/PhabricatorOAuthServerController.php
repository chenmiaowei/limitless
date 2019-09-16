<?php

namespace orangins\modules\oauthserver\actions;

use orangins\lib\actions\PhabricatorAction;

/**
 * Class PhabricatorOAuthServerController
 * @package orangins\modules\oauthserver\actions
 * @author 陈妙威
 */
abstract class PhabricatorOAuthServerController extends PhabricatorAction
{

    /**
     *
     */
    const CONTEXT_AUTHORIZE = 'oauthserver.authorize';

}
