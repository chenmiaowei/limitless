<?php

namespace orangins\modules\auth\guidance;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use PhutilSafeHTML;
use orangins\modules\guides\guidance\PhabricatorGuidanceContext;
use orangins\modules\guides\guidance\PhabricatorGuidanceEngineExtension;
use Yii;
use yii\helpers\Url;

/**
 * Class PhabricatorAuthProvidersGuidanceEngineExtension
 * @package orangins\modules\auth\guidance
 * @author 陈妙威
 */
final class PhabricatorAuthProvidersGuidanceEngineExtension extends PhabricatorGuidanceEngineExtension
{

    /**
     *
     */
    const GUIDANCEKEY = 'core.auth.providers';

    /**
     * @param PhabricatorGuidanceContext $context
     * @return bool|mixed
     * @author 陈妙威
     */
    public function canGenerateGuidance(PhabricatorGuidanceContext $context)
    {
        return ($context instanceof PhabricatorAuthProvidersGuidanceContext);
    }

    /**
     * @param PhabricatorGuidanceContext $context
     * @return array|mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function generateGuidance(PhabricatorGuidanceContext $context)
    {
        $domains_key = 'auth.email-domains';
        $domains_link = $this->renderConfigLink($domains_key);
        $domains_value = PhabricatorEnv::getEnvConfig($domains_key);

        $approval_key = 'auth.require-approval';
        $approval_link = $this->renderConfigLink($approval_key);
        $approval_value = PhabricatorEnv::getEnvConfig($approval_key);

        $results = array();

        if ($domains_value) {
            $message = Yii::t("app",
                'Phabricator is configured with an email domain whitelist (in {0}), so ' .
                'only users with a verified email address at one of these {1} ' .
                'allowed domain(s) will be able to register an account: {2}', [
                    $domains_link,
                    OranginsUtil::phutil_count($domains_value),
                    JavelinHtml::phutil_tag('strong', array(), implode(', ', $domains_value))
                ]);

            $results[] = $this->newGuidance('core.auth.email-domains.on')
                ->setMessage($message);
        } else {
            $message = Yii::t("app",
                'Anyone who can browse to this Phabricator install will be able to ' .
                'register an account. To add email domain restrictions, configure ' .
                '{0}.', [
                    $domains_link
                ]);

            $results[] = $this->newGuidance('core.auth.email-domains.off')
                ->setMessage($message);
        }

        if ($approval_value) {
            $message = new PhutilSafeHTML(Yii::t("app",
                'Administrative approvals are enabled (in {0}), so all new users must ' .
                'have their accounts approved by an administrator.', [
                    $approval_link
                ]));

            $results[] = $this->newGuidance('core.auth.require-approval.on')
                ->setMessage($message);
        } else {
            $message = Yii::t("app",
                'Administrative approvals are disabled, so users who register will ' .
                'be able to use their accounts immediately. To enable approvals, ' .
                'configure %s.',
                $approval_link);

            $results[] = $this->newGuidance('core.auth.require-approval.off')
                ->setMessage($message);
        }

        if (!$domains_value && !$approval_value) {
            $message = Yii::t("app",
                'You can safely ignore these warnings if the install itself has ' .
                'access controls (for example, it is deployed on a VPN) or if all of ' .
                'the configured providers have access controls (for example, they are ' .
                'all private LDAP or OAuth servers).');

            $results[] = $this->newWarning('core.auth.warning')
                ->setMessage($message);
        }

        return $results;
    }

    /**
     * @param $key
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function renderConfigLink($key)
    {
        $config_href = Url::to(['/config/index/edit', 'key' => $key]);

        return JavelinHtml::phutil_tag(
            'a',
            array(
                'href' => $config_href,
                'target' => '_blank',
            ),
            $key);
    }

}
