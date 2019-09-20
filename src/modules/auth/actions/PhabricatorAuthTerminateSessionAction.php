<?php

namespace orangins\modules\auth\actions;

use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\modules\auth\models\PhabricatorAuthSession;

final class PhabricatorAuthTerminateSessionAction
    extends PhabricatorAuthAction
{

    /**
     * @return AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $id = $request->getURIData('id');

        $is_all = ($id === 'all');

        $query = PhabricatorAuthSession::find()
            ->setViewer($viewer)
            ->withIdentityPHIDs(array($viewer->getPHID()));
        if (!$is_all) {
            $query->withIDs(array($id));
        }

        $current_key = PhabricatorHash::weakDigest(
            $request->getCookie(PhabricatorCookies::COOKIE_SESSION));

        $sessions = $query->execute();
        foreach ($sessions as $key => $session) {
            $is_current = phutil_hashes_are_identical(
                $session->getSessionKey(),
                $current_key);
            if ($is_current) {
                // Don't terminate the current login session.
                unset($sessions[$key]);
            }
        }

        $panel_uri = '/settings/panel/sessions/';

        if (!$sessions) {
            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'No Matching Sessions'))
                ->appendParagraph(
                    \Yii::t("app", 'There are no matching sessions to terminate.'))
                ->appendParagraph(
                    \Yii::t("app",
                        '(You can not terminate your current login session. To ' .
                        'terminate it, log out.)'))
                ->addCancelButton($panel_uri);
        }

        if ($request->isDialogFormPost()) {
            foreach ($sessions as $session) {
                $session->delete();
            }
            return (new AphrontRedirectResponse())->setURI($panel_uri);
        }

        if ($is_all) {
            $title = \Yii::t("app", 'Terminate Sessions?');
            $short = \Yii::t("app", 'Terminate Sessions');
            $body = \Yii::t("app",
                'Really terminate all sessions? (Your current login session will ' .
                'not be terminated.)');
        } else {
            $title = \Yii::t("app", 'Terminate Session?');
            $short = \Yii::t("app", 'Terminate Session');
            $body = \Yii::t("app",
                'Really terminate session %s?',
                phutil_tag('strong', array(), substr($session->getSessionKey(), 0, 6)));
        }

        return $this->newDialog()
            ->setTitle($title)
            ->setShortTitle($short)
            ->appendParagraph($body)
            ->addSubmitButton(\Yii::t("app", 'Terminate'))
            ->addCancelButton($panel_uri);
    }


}
