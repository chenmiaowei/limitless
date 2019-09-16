<?php

namespace orangins\modules\auth\actions;

use orangins\lib\response\Aphront400Response;
use orangins\lib\response\AphrontResponse;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\modules\settings\panel\PhabricatorMultiFactorSettingsPanel;

/**
 * Class PhabricatorAuthNeedsMultiFactorAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthNeedsMultiFactorAction
    extends PhabricatorAuthAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireMultiFactorEnrollment()
    {
        // Users need access to this controller in order to enroll in multi-factor
        // auth.
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireEnabledUser()
    {
        // Users who haven't been approved yet are allowed to enroll in MFA. We'll
        // kick disabled users out later.
        return false;
    }

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    public function shouldRequireEmailVerification()
    {
        // Users who haven't verified their email addresses yet can still enroll
        // in MFA.
        return false;
    }

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView|Aphront400Response
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        if ($viewer->getIsDisabled()) {
            // We allowed unapproved and disabled users to hit this controller, but
            // want to kick out disabled users now.
            return new Aphront400Response();
        }

        $panel = (new PhabricatorMultiFactorSettingsPanel())
            ->setUser($viewer)
            ->setViewer($viewer)
            ->setOverrideURI($this->getApplicationURI('/multifactor/'))
            ->processRequest($request);

        if ($panel instanceof AphrontResponse) {
            return $panel;
        }

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'Add Multi-Factor Auth'));

        $viewer->updateMultiFactorEnrollment();

        if (!$viewer->getIsEnrolledInMultiFactor()) {
            $help = (new PHUIInfoView())
                ->setTitle(\Yii::t("app", 'Add Multi-Factor Authentication To Your Account'))
                ->setSeverity(PHUIInfoView::SEVERITY_WARNING)
                ->setErrors(
                    array(
                        \Yii::t("app",
                            'Before you can use Phabricator, you need to add multi-factor ' .
                            'authentication to your account.'),
                        \Yii::t("app",
                            'Multi-factor authentication helps secure your account by ' .
                            'making it more difficult for attackers to gain access or ' .
                            'take sensitive actions.'),
                        \Yii::t("app",
                            'To learn more about multi-factor authentication, click the ' .
                            '%s button below.',
                            phutil_tag('strong', array(), \Yii::t("app", 'Help'))),
                        \Yii::t("app",
                            'To add an authentication factor, click the %s button below.',
                            phutil_tag('strong', array(), \Yii::t("app", 'Add Authentication Factor'))),
                        \Yii::t("app",
                            'To continue, add at least one authentication factor to your ' .
                            'account.'),
                    ));
        } else {
            $help = (new PHUIInfoView())
                ->setTitle(\Yii::t("app", 'Multi-Factor Authentication Configured'))
                ->setSeverity(PHUIInfoView::SEVERITY_NOTICE)
                ->setErrors(
                    array(
                        \Yii::t("app",
                            'You have successfully configured multi-factor authentication ' .
                            'for your account.'),
                        \Yii::t("app",
                            'You can make adjustments from the Settings panel later.'),
                        \Yii::t("app",
                            'When you are ready, %s.',
                            phutil_tag(
                                'strong',
                                array(),
                                phutil_tag(
                                    'a',
                                    array(
                                        'href' => '/',
                                    ),
                                    \Yii::t("app", 'continue to Phabricator')))),
                    ));
        }

        $view = array(
            $help,
            $panel,
        );

        return $this->newPage()
            ->setTitle(\Yii::t("app", 'Add Multi-Factor Authentication'))
            ->setCrumbs($crumbs)
            ->appendChild($view);

    }

}
