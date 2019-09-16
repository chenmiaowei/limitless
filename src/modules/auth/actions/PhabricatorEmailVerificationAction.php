<?php

namespace orangins\modules\auth\actions;

use orangins\lib\response\Aphront400Response;
use orangins\lib\view\AphrontDialogView;
use orangins\modules\people\editors\PhabricatorUserEditor;
use orangins\modules\people\models\PhabricatorUserEmail;

/**
 * Class PhabricatorEmailVerificationAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorEmailVerificationAction
    extends PhabricatorAuthAction
{

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    public function shouldRequireEmailVerification()
    {
        // Since users need to be able to hit this endpoint in order to verify
        // email, we can't ever require email verification here.
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireEnabledUser()
    {
        // Unapproved users are allowed to verify their email addresses. We'll kick
        // disabled users out later.
        return false;
    }

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView|Aphront400Response
     * @throws \AphrontObjectMissingQueryException
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $code = $request->getURIData('code');

        if ($viewer->getIsDisabled()) {
            // We allowed unapproved and disabled users to hit this controller, but
            // want to kick out disabled users now.
            return new Aphront400Response();
        }

        $email = PhabricatorUserEmail::find()->andWhere([
            'user_phid' => $viewer->getPHID(),
            'verification_code' => $code
        ])->one();

        $submit = null;

        if (!$email) {
            $title = \Yii::t("app", 'Unable to Verify Email');
            $content = \Yii::t("app",
                'The verification code you provided is incorrect, or the email ' .
                'address has been removed, or the email address is owned by another ' .
                'user. Make sure you followed the link in the email correctly and are ' .
                'logged in with the user account associated with the email address.');
            $continue = \Yii::t("app", 'Rats!');
        } else if ($email->getIsVerified() && $viewer->getIsEmailVerified()) {
            $title = \Yii::t("app", 'Address Already Verified');
            $content = \Yii::t("app",
                'This email address has already been verified.');
            $continue = \Yii::t("app", 'Continue to Phabricator');
        } else if ($request->isFormPost()) {

            (new PhabricatorUserEditor())
                ->setActor($viewer)
                ->verifyEmail($viewer, $email);

            $title = \Yii::t("app", 'Address Verified');
            $content = \Yii::t("app",
                'The email address %s is now verified.',
                phutil_tag('strong', array(), $email->getAddress()));
            $continue = \Yii::t("app", 'Continue to Phabricator');
        } else {
            $title = \Yii::t("app", 'Verify Email Address');
            $content = \Yii::t("app",
                'Verify this email address (%s) and attach it to your account?',
                phutil_tag('strong', array(), $email->getAddress()));
            $continue = \Yii::t("app", 'Cancel');
            $submit = \Yii::t("app", 'Verify %s', $email->getAddress());
        }

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->setTitle($title)
            ->addCancelButton('/', $continue)
            ->appendChild($content);

        if ($submit) {
            $dialog->addSubmitButton($submit);
        }

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'Verify Email'));
        $crumbs->setBorder(true);

        return $this->newPage()
            ->setTitle(\Yii::t("app", 'Verify Email'))
            ->setCrumbs($crumbs)
            ->appendChild($dialog);

    }

}
