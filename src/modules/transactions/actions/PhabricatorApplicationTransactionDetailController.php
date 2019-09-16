<?php
namespace orangins\modules\transactions\actions;

final class PhabricatorApplicationTransactionDetailController
  extends PhabricatorApplicationTransactionController {

  private $objectHandle;

  public function shouldAllowPublic() {
    return true;
  }

  public function run() { $request = $this->getRequest();
    // Users can end up on this page directly by following links in email,
    // so we try to make it somewhat reasonable as a standalone page.

    $viewer = $this->getViewer();
    $phid = $request->getURIData('phid');

    $xaction = (new PhabricatorObjectQuery())
      ->withPHIDs(array($phid))
      ->setViewer($viewer)
      ->executeOne();
    if (!$xaction) {
      return new Aphront404Response();
    }

    $details = $xaction->renderChangeDetails($viewer);

    $object_phid = $xaction->getObjectPHID();
    $handles = $viewer->loadHandles(array($object_phid));
    $handle = $handles[$object_phid];
    $this->objectHandle = $handle;

    $cancel_uri = $handle->getURI();

    if ($request->isAjax()) {
      $button_text = \Yii::t("app",'Done');
    } else {
      $button_text = \Yii::t("app",'Continue');
    }

    return $this->newDialog()
      ->setTitle(\Yii::t("app",'Change Details'))
      ->setWidth(AphrontDialogView::WIDTH_FORM)
      ->setClass('aphront-dialog-tab-group')
      ->appendChild($details)
      ->addCancelButton($cancel_uri, $button_text);
  }

  protected function buildApplicationCrumbs() {
    $crumbs = parent::buildApplicationCrumbs();

    $handle = $this->objectHandle;
    if ($handle) {
      $crumbs->addTextCrumb(
        $handle->getObjectName(),
        $handle->getURI());
    }

    return $crumbs;
  }


}
