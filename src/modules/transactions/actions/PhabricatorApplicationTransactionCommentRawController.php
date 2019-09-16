<?php
namespace orangins\modules\transactions\actions;

final class PhabricatorApplicationTransactionCommentRawController
  extends PhabricatorApplicationTransactionController {

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
      // You can't view a raw comment if there is no comment.
      return new Aphront404Response();
    }

    if ($xaction->getComment()->getIsRemoved()) {
      // You can't view a raw comment if the comment is deleted.
      return new Aphront400Response();
    }

    $obj_phid = $xaction->getObjectPHID();
    $obj_handle = (new PhabricatorHandleQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($obj_phid))
      ->executeOne();

    $title = \Yii::t("app",'Raw Comment');
    $body = $xaction->getComment()->getContent();
    $addendum = null;
    if ($request->getExists('email')) {
      $content_source = $xaction->getContentSource();
      $source_email = PhabricatorEmailContentSource::SOURCECONST;
      if ($content_source->getSource() == $source_email) {
        $source_id = $content_source->getContentSourceParameter('id');
        if ($source_id) {
          $message = (new PhabricatorMetaMTAReceivedMail())->loadOneWhere(
            'id = %d',
            $source_id);
          if ($message) {
            $title = \Yii::t("app",'Email Body Text');
            $body = $message->getRawTextBody();
            $details_text = \Yii::t("app",
              'For full details, run `/bin/mail show-inbound --id %d`',
              $source_id);
            $addendum = new PHUIRemarkupView($viewer, $details_text);
          }
        }
      }
    }
    $dialog = (new AphrontDialogView())
      ->setUser($viewer)
      ->addCancelButton($obj_handle->getURI())
      ->setTitle($title);

    $dialog
      ->addHiddenInput('anchor', $request->getStr('anchor'))
      ->appendChild(
        (new PHUIFormLayoutView())
        ->setFullWidth(true)
        ->appendChild(
          (new AphrontFormTextAreaControl())
          ->setReadOnly(true)
          ->setValue($body)));
    if ($addendum) {
      $dialog->appendParagraph($addendum);
    }

    return (new AphrontDialogResponse())->setDialog($dialog);
  }

  public function shouldAllowPublic() {
    return true;
  }

}
