<?php
namespace orangins\modules\conduit\actions;

final class PhabricatorConduitTokenHandshakeController
  extends PhabricatorConduitController {

  public function run() { $request = $this->getRequest();
    $viewer = $request->getViewer();

    (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
      $viewer,
      $request,
      '/');

    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
      $token = PhabricatorConduitToken::initializeNewToken(
        $viewer->getPHID(),
        PhabricatorConduitToken::TYPE_COMMANDLINE);
      $token->save();
    unset($unguarded);

    $form = (new AphrontFormView())
      ->setUser($viewer)
      ->appendRemarkupInstructions(
        \Yii::t("app",
          'Copy-paste the API Token below to grant access to your account.'))
      ->appendChild(
        (new AphrontFormTextControl())
          ->setLabel(\Yii::t("app",'API Token'))
          ->setValue($token->getToken()))
      ->appendRemarkupInstructions(
        \Yii::t("app",
          'This will authorize the requesting script to act on your behalf '.
          'permanently, like giving the script your account password.'))
      ->appendRemarkupInstructions(
        \Yii::t("app",
          'If you change your mind, you can revoke this token later in '.
          '{nav icon=wrench,name=Settings > Conduit API Tokens}.'));

    return $this->newDialog()
      ->setTitle(\Yii::t("app",'Grant Account Access'))
      ->setWidth(AphrontDialogView::WIDTH_FULL)
      ->appendForm($form)
      ->addCancelButton('/');
  }

}
