<?php
namespace orangins\modules\auth\actions;

use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;

/**
 * Class PhabricatorAuthDowngradeSessionAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthDowngradeSessionAction
  extends PhabricatorAuthAction {

    /**
     * @return AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView
     * @throws \PhutilMethodNotImplementedException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \Exception
     * @author 陈妙威
     */public function run() { $request = $this->getRequest();
    $viewer = $this->getViewer();

    $panel_uri = '/settings/panel/sessions/';

    $session = $viewer->getSession();
    if ($session->getHighSecurityUntil() < time()) {
      return $this->newDialog()
        ->setTitle(\Yii::t("app",'Normal Security Restored'))
        ->appendParagraph(
          \Yii::t("app",'Your session is no longer in high security.'))
        ->addCancelButton($panel_uri, \Yii::t("app",'Continue'));
    }

    if ($request->isFormPost()) {

      (new PhabricatorAuthSessionEngine())
        ->exitHighSecurity($viewer, $session);

      return (new AphrontRedirectResponse())
        ->setURI($this->getApplicationURI('session/downgrade/'));
    }

    return $this->newDialog()
      ->setTitle(\Yii::t("app",'Leaving High Security'))
      ->appendParagraph(
        \Yii::t("app",
          'Leave high security and return your session to normal '.
          'security levels?'))
      ->appendParagraph(
        \Yii::t("app",
          'If you leave high security, you will need to authenticate '.
          'again the next time you try to take a high security action.'))
      ->appendParagraph(
        \Yii::t("app",
          'On the plus side, that purple notification bubble will '.
          'disappear.'))
      ->addSubmitButton(\Yii::t("app",'Leave High Security'))
      ->addCancelButton($panel_uri, \Yii::t("app",'Stay'));
  }


}
