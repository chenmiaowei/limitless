<?php
namespace orangins\modules\transactions\actions;

final class PhabricatorApplicationTransactionCommentHistoryController
  extends PhabricatorApplicationTransactionController {

  public function shouldAllowPublic() {
    return true;
  }

  public function run() { $request = $this->getRequest();
    $viewer = $this->getViewer();
    $phid = $request->getURIData('phid');

    $xaction = (new PhabricatorObjectQuery())
      ->withPHIDs(array($phid))
      ->setViewer($viewer)
      ->executeOne();

    if (!$xaction) {
      return new Aphront404Response();
    }

    if (!$xaction->getComment()) {
      // You can't view history of a transaction with no comments.
      return new Aphront404Response();
    }

    if ($xaction->getComment()->getIsRemoved()) {
      // You can't view history of a transaction with a removed comment.
      return new Aphront400Response();
    }

    $comments = (new PhabricatorApplicationTransactionTemplatedCommentQuery())
      ->setViewer($viewer)
      ->setTemplate($xaction->getApplicationTransactionCommentObject())
      ->withTransactionPHIDs(array($xaction->getPHID()))
      ->execute();

    if (!$comments) {
      return new Aphront404Response();
    }

    $comments = msort($comments, 'getCommentVersion');

    $xactions = array();
    foreach ($comments as $comment) {
      $xactions[] = (clone $xaction)
        ->makeEphemeral()
        ->setCommentVersion($comment->getCommentVersion())
        ->setContentSource($comment->getContentSource())
        ->setDateCreated($comment->created_at)
        ->attachComment($comment);
    }

    $obj_phid = $xaction->getObjectPHID();
    $obj_handle = (new PhabricatorHandleQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($obj_phid))
      ->executeOne();

    $view = (new PhabricatorApplicationTransactionView())
      ->setUser($viewer)
      ->setObjectPHID($obj_phid)
      ->setTransactions($xactions)
      ->setShowEditActions(false)
      ->setHideCommentOptions(true);

    $dialog = (new AphrontDialogView())
      ->setUser($viewer)
      ->setWidth(AphrontDialogView::WIDTH_FULL)
      ->setFlush(true)
      ->setTitle(\Yii::t("app",'Comment History'));

    $dialog->appendChild($view);

    $dialog
      ->addCancelButton($obj_handle->getURI());

    return (new AphrontDialogResponse())->setDialog($dialog);
  }

}
