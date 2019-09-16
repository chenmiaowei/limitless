<?php

namespace orangins\modules\spaces\application;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\PhabricatorApplication;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\spaces\capability\PhabricatorSpacesCapabilityCreateSpaces;
use orangins\modules\spaces\capability\PhabricatorSpacesCapabilityDefaultEdit;
use orangins\modules\spaces\capability\PhabricatorSpacesCapabilityDefaultView;
use orangins\modules\spaces\phid\PhabricatorSpacesNamespacePHIDType;
use orangins\modules\spaces\remarkup\PhabricatorSpacesRemarkupRule;

/**
 * Class PhabricatorSpacesApplication
 * @package orangins\modules\spaces\application
 * @author 陈妙威
 */
final class PhabricatorSpacesApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'spaces';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\spaces\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/spaces/index/query';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBaseURI()
    {
        return '/spaces/';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return pht('Spaces');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return pht('Policy Namespaces');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-th-large';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTitleGlyph()
    {
        return "\xE2\x97\x8B";
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getFlavorText()
    {
        return pht('Control access to groups of objects.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationGroup()
    {
        return self::GROUP_UTILITIES;
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
     * @param PhabricatorUser $viewer
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    public function getHelpDocumentationArticles(PhabricatorUser $viewer)
    {
        return array(
            array(
                'name' => pht('Spaces User Guide'),
                'href' => PhabricatorEnv::getDoclink('Spaces User Guide'),
            ),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getRemarkupRules()
    {
        return array(
            new PhabricatorSpacesRemarkupRule(),
        );
    }


    /**
     * @return array
     * @author 陈妙威
     */
    protected function getCustomCapabilities()
    {
        return array(
            PhabricatorSpacesCapabilityCreateSpaces::CAPABILITY => array(
                'default' => PhabricatorPolicies::POLICY_ADMIN,
            ),
            PhabricatorSpacesCapabilityDefaultView::CAPABILITY => array(
                'caption' => pht('Default view policy for newly created spaces.'),
                'template' => PhabricatorSpacesNamespacePHIDType::TYPECONST,
                'capability' => PhabricatorPolicyCapability::CAN_VIEW,
            ),
            PhabricatorSpacesCapabilityDefaultEdit::CAPABILITY => array(
                'caption' => pht('Default edit policy for newly created spaces.'),
                'default' => PhabricatorPolicies::POLICY_ADMIN,
                'template' => PhabricatorSpacesNamespacePHIDType::TYPECONST,
                'capability' => PhabricatorPolicyCapability::CAN_EDIT,
            ),
        );
    }

}
