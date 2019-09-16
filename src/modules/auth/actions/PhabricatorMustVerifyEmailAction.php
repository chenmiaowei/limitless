<?php

namespace orangins\modules\auth\actions;

use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\phui\PHUIInfoView;

/**
 * Class PhabricatorMustVerifyEmailAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorMustVerifyEmailAction
    extends PhabricatorAuthAction
{

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    public function shouldRequireEmailVerification()
    {
        // NOTE: We don't technically need this since PhabricatorController forces
        // us here in either case, but it's more consistent with intent.
        return false;
    }

    /**
     * @return AphrontRedirectResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \AphrontQueryException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $email = $viewer->loadPrimaryEmail();

        if ($viewer->getIsEmailVerified()) {
            return (new AphrontRedirectResponse())->setURI('/');
        }

        $email_address = $email->getAddress();

        $sent = null;
        if ($request->isFormPost()) {
            $email->sendVerificationEmail($viewer);
            $sent = new PHUIInfoView();
            $sent->setSeverity(PHUIInfoView::SEVERITY_NOTICE);
            $sent->setTitle(\Yii::t("app", 'Email Sent'));
            $sent->appendChild(
                \Yii::t("app",
                    'Another verification email was sent to %s.',
                    phutil_tag('strong', array(), $email_address)));
        }

        $must_verify = \Yii::t("app",
            'You must verify your email address to log in. You should have a ' .
            'new email message from Phabricator with verification instructions ' .
            'in your inbox (%s).',
            phutil_tag('strong', array(), $email_address));

        $send_again = \Yii::t("app",
            'If you did not receive an email, you can click the button below ' .
            'to try sending another one.');

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->setTitle(\Yii::t("app", 'Check Your Email'))
            ->appendParagraph($must_verify)
            ->appendParagraph($send_again)
            ->addSubmitButton(\Yii::t("app", 'Send Another Email'));

        $view = array(
            $sent,
            $dialog,
        );

        return $this->newPage()
            ->setTitle(\Yii::t("app", 'Must Verify Email'))
            ->appendChild($view);

    }

}
