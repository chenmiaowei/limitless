<?php

namespace orangins\modules\auth\actions;

use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\lib\response\AphrontRedirectResponse;

/**
 * Class PhabricatorAuthValidateAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthValidateAction
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
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPartialSessions()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowLegallyNonCompliantUsers()
    {
        return true;
    }

    /**
     * @return AphrontRedirectResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $failures = array();

        if (!strlen($request->getStr('expect'))) {
            return $this->renderErrors(
                array(
                    \Yii::t("app",
                        'Login validation is missing expected parameter ("%s").',
                        'phusr'),
                ));
        }

        $expect_phusr = $request->getStr('expect');
        $actual_phusr = $request->getCookie(PhabricatorCookies::COOKIE_USERNAME);
        if ($actual_phusr != $expect_phusr) {
            if ($actual_phusr) {
                $failures[] = \Yii::t("app",
                    "Attempted to set '{0}' cookie to '{1}', but your browser sent back " .
                    "a cookie with the value '{2}'. Clear your browser's cookies and " .
                    "try again.",
                    [
                        'phusr',
                        $expect_phusr,
                        $actual_phusr
                    ]);
            } else {
                $failures[] = \Yii::t("app",
                    "Attempted to set '{0}' cookie to '{1}', but your browser did not " .
                    "accept the cookie. Check that cookies are enabled, clear them, " .
                    "and try again.",
                    [
                        'phusr',
                        $expect_phusr
                    ]);
            }
        }

        if (!$failures) {
            if (!$viewer->getPHID()) {
                $failures[] = \Yii::t("app",
                    'Login cookie was set correctly, but your login session is not ' .
                    'valid. Try clearing cookies and logging in again.');
            }
        }

        if ($failures) {
            return $this->renderErrors($failures);
        }

        $finish_uri = $this->getApplicationURI('index/finish');
        return (new AphrontRedirectResponse())->setURI($finish_uri);
    }

    /**
     * @param array $messages
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderErrors(array $messages)
    {
        return $this->renderErrorPage(
            \Yii::t("app", 'Login Failure'),
            $messages);
    }

}
