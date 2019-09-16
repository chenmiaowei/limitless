<?php

namespace orangins\modules\oauthserver\application;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\PhabricatorApplication;
use orangins\modules\oauthserver\capability\PhabricatorOAuthServerCreateClientsCapability;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\constants\PhabricatorPolicies;

/**
 * Class PhabricatorOAuthServerApplication
 * @package orangins\modules\oauthserver\application
 * @author 陈妙威
 */
final class PhabricatorOAuthServerApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\oauthserver\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/oauthserver/index/query';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'oauthserver';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return pht('OAuth Server');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBaseURI()
    {
        return '/oauthserver/';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return pht('OAuth Login Provider');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-hotel';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTitleGlyph()
    {
        return "\xE2\x99\x86";
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getFlavorText()
    {
        return pht('Log In with Phabricator');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationGroup()
    {
        return self::GROUP_ADMIN;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
//    public function isPrototype()
//    {
//        return true;
//    }

    /**
     * @param PhabricatorUser $viewer
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    public function getHelpDocumentationArticles(PhabricatorUser $viewer)
    {
        return array(
            array(
                'name' => pht('Using the Phabricator OAuth Server'),
                'href' => PhabricatorEnv::getDoclink(
                    'Using the Phabricator OAuth Server'),
            ),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getCustomCapabilities()
    {
        return array(
            PhabricatorOAuthServerCreateClientsCapability::CAPABILITY => array(
                'default' => PhabricatorPolicies::POLICY_ADMIN,
            ),
        );
    }

}
