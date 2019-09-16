<?php

namespace orangins\modules\auth\actions;

use orangins\lib\view\AphrontDialogView;

/**
 * Class PhabricatorAuthNeedsApprovalAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthNeedsApprovalAction
    extends PhabricatorAuthAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireLogin()
    {
        return false;
    }

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    public function shouldRequireEmailVerification()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireEnabledUser()
    {
        return false;
    }

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $wait_for_approval = \Yii::t("app",
            "Your account has been created, but needs to be approved by an " .
            "administrator. You'll receive an email once your account is approved.");

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->setTitle(\Yii::t("app", 'Wait for Approval'))
            ->appendChild($wait_for_approval)
            ->addCancelButton('/', \Yii::t("app", 'Wait Patiently'));

        return $this->newPage()
            ->setTitle(\Yii::t("app", 'Wait For Approval'))
            ->appendChild($dialog);

    }
}
