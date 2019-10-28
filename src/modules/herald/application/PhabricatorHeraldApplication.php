<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/19
 * Time: 12:50 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\herald\application;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\PhabricatorApplication;
use orangins\modules\herald\capability\HeraldCreateWebhooksCapability;
use orangins\modules\herald\capability\HeraldManageGlobalRulesCapability;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\constants\PhabricatorPolicies;

/**
 * Class PhabricatorHeraldApplication
 * @package orangins\modules\herald\application
 * @author 陈妙威
 */
class PhabricatorHeraldApplication extends PhabricatorApplication
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\herald\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/herald/index/query';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'herald';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return \Yii::t("app", 'Herald');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getShortDescription()
    {
        return \Yii::t("app", "Create Notification Rules");
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'fa-file';
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
                'name' => pht('Herald User Guide'),
                'href' => PhabricatorEnv::getDoclink('Herald User Guide'),
            ),
            array(
                'name' => pht('User Guide: Webhooks'),
                'href' => PhabricatorEnv::getDoclink('User Guide: Webhooks'),
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
            HeraldManageGlobalRulesCapability::CAPABILITY => array(
                'caption' => pht('Global rules can bypass access controls.'),
                'default' => PhabricatorPolicies::POLICY_ADMIN,
            ),
            HeraldCreateWebhooksCapability::CAPABILITY => array(
                'default' => PhabricatorPolicies::POLICY_ADMIN,
            ),
        );
    }
}