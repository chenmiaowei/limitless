<?php

namespace orangins\modules\auth\application;

use orangins\modules\auth\capability\AuthManageProvidersCapability;
use orangins\modules\auth\guidance\PhabricatorAuthProvidersGuidanceEngineExtension;
use orangins\modules\auth\phid\PhabricatorAuthAuthProviderPHIDType;
use orangins\modules\auth\provider\PhabricatorPasswordAuthProvider;
use orangins\lib\PhabricatorApplication;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;

/**
 * Class PhabricatorAuthApplication
 * @package orangins\modules\file\application
 * @author 陈妙威
 */
final class PhabricatorAuthApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'auth';
    }
    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\auth\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/auth/index/index';
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function canUninstall()
    {
        return false;
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-key';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return \Yii::t("app",'Auth');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return \Yii::t("app",'Login/Registration');
    }

    /**
     * @param PhabricatorUser $viewer
     * @return array
     * @author 陈妙威
     */
    public function getHelpDocumentationArticles(PhabricatorUser $viewer)
    {
        // NOTE: Although reasonable help exists for this in "Configuring Accounts
        // and Registration", specifying help items here means we get the menu
        // item in all the login/link interfaces, which is confusing and not
        // helpful.

        // TODO: Special case this, or split the auth and auth administration
        // applications?

        return array();
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
     * @return array
     * @author 陈妙威
     */
    protected function getCustomCapabilities()
    {
        return array(
            AuthManageProvidersCapability::CAPABILITY => array(
                'default' => PhabricatorPolicies::POLICY_ADMIN,
            ),
        );
    }
}
