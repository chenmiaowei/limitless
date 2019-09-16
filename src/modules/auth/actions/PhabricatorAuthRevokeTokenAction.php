<?php

namespace orangins\modules\auth\actions;

use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\auth\models\PhabricatorAuthTemporaryToken;
use orangins\modules\settings\panel\PhabricatorTokensSettingsPanel;

/**
 * Class PhabricatorAuthRevokeTokenAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthRevokeTokenAction
    extends PhabricatorAuthAction
{

    /**
     * @return AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run() {$request = $this->getRequest();
        $viewer = $this->getViewer();
        $id = $request->getURIData('id');

        $is_all = ($id === 'all');


        $query = PhabricatorAuthTemporaryToken::find()
            ->setViewer($viewer)
            ->withTokenResources(array($viewer->getPHID()));
        if (!$is_all) {
            $query->withIDs(array($id));
        }

        /** @var PhabricatorAuthTemporaryToken[] $tokens */
        $tokens = $query->execute();
        foreach ($tokens as $key => $token) {
            if (!$token->isRevocable()) {
                // Don't revoke unrevocable tokens.
                unset($tokens[$key]);
            }
        }

        $panel_uri = (new PhabricatorTokensSettingsPanel())
            ->setViewer($viewer)
            ->setUser($viewer)
            ->getPanelURI();

        if (!$tokens) {
            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'No Matching Tokens'))
                ->appendParagraph(
                    \Yii::t("app", 'There are no matching tokens to revoke.'))
                ->appendParagraph(
                    \Yii::t("app",
                        '(Some types of token can not be revoked, and you can not revoke ' .
                        'tokens which have already expired.)'))
                ->addCancelButton($panel_uri);
        }

        if ($request->isDialogFormPost()) {
            foreach ($tokens as $token) {
                $token->revokeToken();
            }
            return (new AphrontRedirectResponse())->setURI($panel_uri);
        }

        if ($is_all) {
            $title = \Yii::t("app", 'Revoke Tokens?');
            $short = \Yii::t("app", 'Revoke Tokens');
            $body = \Yii::t("app",
                'Really revoke all tokens? Among other temporary authorizations, ' .
                'this will disable any outstanding password reset or account ' .
                'recovery links.');
        } else {
            $title = \Yii::t("app", 'Revoke Token?');
            $short = \Yii::t("app", 'Revoke Token');
            $body = \Yii::t("app",
                'Really revoke this token? Any temporary authorization it enables ' .
                'will be disabled.');
        }

        return $this->newDialog()
            ->setTitle($title)
            ->setShortTitle($short)
            ->appendParagraph($body)
            ->addSubmitButton(\Yii::t("app", 'Revoke'))
            ->addCancelButton($panel_uri);
    }


}
