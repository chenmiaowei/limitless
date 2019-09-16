<?php
namespace orangins\modules\file\actions;

use orangins\lib\response\Aphront403Response;
use orangins\modules\file\editors\PhabricatorFileEditor;
use orangins\modules\file\models\PhabricatorFileTransaction;
use orangins\modules\file\xaction\PhabricatorFileDeleteTransaction;

final class PhabricatorFileDeleteAction extends PhabricatorFileAction {

  public function run() { $request = $this->getRequest();
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $file = PhabricatorFile::find()
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->withIsDeleted(false)
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_VIEW,
          PhabricatorPolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$file) {
      return new Aphront404Response();
    }

    if (($viewer->getPHID() != $file->getAuthorPHID()) &&
        (!$viewer->getIsAdmin())) {
      return new Aphront403Response();
    }

    if ($request->isFormPost()) {
      $xactions = array();

      $xactions[] = (new PhabricatorFileTransaction())
        ->setTransactionType(PhabricatorFileDeleteTransaction::TRANSACTIONTYPE)
        ->setNewValue(true);

      (new PhabricatorFileEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->applyTransactions($file, $xactions);

      return (new AphrontRedirectResponse())->setURI('/file/');
    }

    return $this->newDialog()
      ->setTitle(\Yii::t("app",'Really delete file?'))
      ->appendChild(hsprintf(
      '<p>%s</p>',
      \Yii::t("app",
        'Permanently delete "%s"? This action can not be undone.',
        $file->getName())))
        ->addSubmitButton(\Yii::t("app",'Delete'))
        ->addCancelButton($file->getInfoURI());
  }
}
