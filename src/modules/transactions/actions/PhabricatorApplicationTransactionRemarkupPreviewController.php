<?php
namespace orangins\modules\transactions\actions;

final class PhabricatorApplicationTransactionRemarkupPreviewController
  extends PhabricatorApplicationTransactionController {

  public function shouldAllowPublic() {
    return true;
  }

  public function run() { $request = $this->getRequest();
    $viewer = $this->getViewer();

    $text = $request->getStr('text');

    $remarkup = new PHUIRemarkupView($viewer, $text);

    $content = array(
      'content' => hsprintf('%s', $remarkup),
    );

    return (new AphrontAjaxResponse())
      ->setContent($content);
  }

}
