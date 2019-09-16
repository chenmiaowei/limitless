<?php

namespace orangins\modules\conduit\method;

use orangins\lib\PhabricatorApplication;
use orangins\modules\conduit\protocol\ConduitAPIRequest;
use orangins\modules\oauthserver\application\PhabricatorOAuthServerApplication;

/**
 * Class ConduitGetCapabilitiesConduitAPIMethod
 * @package orangins\modules\conduit\method
 * @author 陈妙威
 */
final class ConduitGetCapabilitiesConduitAPIMethod extends ConduitAPIMethod
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getAPIMethodName()
    {
        return 'conduit.getcapabilities';
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireAuthentication()
    {
        return false;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodDescription()
    {
        return \Yii::t("app",
            'List capabilities, wire formats, and authentication protocols ' .
            'available on this server.');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function defineParamTypes()
    {
        return array();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function defineReturnType()
    {
        return 'dict<string, any>';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getRequiredScope()
    {
        return self::SCOPE_ALWAYS;
    }

    /**
     * @param ConduitAPIRequest $request
     * @return array
     * @author 陈妙威
     */
    protected function execute(ConduitAPIRequest $request)
    {
        $authentication = array(
            'token',
            'asymmetric',
            'session',
            'sessionless',
        );

        $oauth_app = PhabricatorOAuthServerApplication::className();
        if (PhabricatorApplication::isClassInstalled($oauth_app)) {
            $authentication[] = 'oauth';
        }

        return array(
            'authentication' => $authentication,
            'signatures' => array(
                'consign',
            ),
            'input' => array(
                'json',
                'urlencoded',
            ),
            'output' => array(
                'json',
                'human',
            ),
        );
    }

}
