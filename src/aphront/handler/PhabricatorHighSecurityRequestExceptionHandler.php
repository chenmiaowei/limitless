<?php

namespace orangins\aphront\handler;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\auth\exception\PhabricatorAuthHighSecurityRequiredException;

/**
 * Class PhabricatorHighSecurityRequestExceptionHandler
 * @package orangins\aphront\handler
 * @author 陈妙威
 */
final class PhabricatorHighSecurityRequestExceptionHandler
    extends PhabricatorRequestExceptionHandler
{

    /**
     * @return int|mixed
     * @author 陈妙威
     */
    public function getRequestExceptionHandlerPriority()
    {
        return 310000;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getRequestExceptionHandlerDescription()
    {
        return pht(
            'Handles high security exceptions which occur when a user needs ' .
            'to present MFA credentials to take an action.');
    }

    /**
     * @param AphrontRequest $request
     * @param $throwable
     * @return bool|mixed
     * @author 陈妙威
     */
    public function canHandleRequestThrowable(
        AphrontRequest $request,
        $throwable)
    {

        if (!$this->isPhabricatorSite($request)) {
            return false;
        }

        return ($throwable instanceof PhabricatorAuthHighSecurityRequiredException);
    }

    /**
     * @param AphrontRequest $request
     * @param $throwable
     * @return mixed|AphrontDialogView
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function handleRequestThrowable(
        AphrontRequest $request,
        $throwable)
    {

        $viewer = $this->getViewer($request);
        $results = $throwable->getFactorValidationResults();

        $form = (new PhabricatorAuthSessionEngine())->renderHighSecurityForm(
            $throwable->getFactors(),
            $results,
            $viewer,
            $request);

        $is_wait = false;
        $is_continue = false;
        foreach ($results as $result) {
            if ($result->getIsWait()) {
                $is_wait = true;
            }

            if ($result->getIsContinue()) {
                $is_continue = true;
            }
        }

        $is_upgrade = $throwable->getIsSessionUpgrade();

        if ($is_upgrade) {
            $title = pht('Enter High Security');
        } else {
            $title = pht('Provide MFA Credentials');
        }

        if ($is_wait) {
            $submit = pht('Wait Patiently');
        } else if ($is_upgrade && !$is_continue) {
            $submit = pht('Enter High Security');
        } else {
            $submit = pht('Continue');
        }

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->setTitle($title)
            ->setShortTitle(pht('Security Checkpoint'))
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->addHiddenInput(AphrontRequest::TYPE_HISEC, true)
            ->setSubmitURI($request->getPath())
            ->addCancelButton($throwable->getCancelURI())
            ->addSubmitButton($submit);

        $form_layout = $form->buildLayoutView();

        if ($is_upgrade) {
            $message = pht(
                'You are taking an action which requires you to enter ' .
                'high security.');

            $info_view = (new PHUIInfoView())
                ->setSeverity(PHUIInfoView::SEVERITY_MFA)
                ->setErrors(array($message));

            $dialog
                ->appendChild($info_view)
                ->appendParagraph(
                    pht(
                        'To enter high security mode, confirm your credentials:'))
                ->appendChild($form_layout)
                ->appendParagraph(
                    pht(
                        'Your account will remain in high security mode for a short ' .
                        'period of time. When you are finished taking sensitive ' .
                        'actions, you should leave high security.'));
        } else {
            $message = pht(
                'You are taking an action which requires you to provide ' .
                'multi-factor credentials.');

            $info_view = (new PHUIInfoView())
                ->setSeverity(PHUIInfoView::SEVERITY_MFA)
                ->setErrors(array($message));

            $dialog
                ->appendChild($info_view)
                ->setErrors(
                    array())
                ->appendChild($form_layout);
        }

        $request_parameters = $request->getPassthroughRequestParameters(
            $respect_quicksand = true);
        foreach ($request_parameters as $key => $value) {
            $dialog->addHiddenInput($key, $value);
        }

        return $dialog;
    }

}
