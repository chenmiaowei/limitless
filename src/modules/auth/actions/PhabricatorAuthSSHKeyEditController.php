<?php
namespace orangins\modules\auth\actions;

use orangins\modules\auth\models\PhabricatorAuthSSHKeyTransaction;
use orangins\modules\transactions\constants\PhabricatorTransactions;

final class PhabricatorAuthSSHKeyEditController
  extends PhabricatorAuthSSHKeyAction {

  public function run() { $request = $this->getRequest();
    $viewer = $this->getViewer();
    $id = $request->getURIData('id');

    if ($id) {
      $key = PhabricatorAuthSSHKey::find()
        ->setViewer($viewer)
        ->withIDs(array($id))
        ->requireCapabilities(
          array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
          ))
        ->executeOne();
      if (!$key) {
        return new Aphront404Response();
      }

      $is_new = false;
    } else {
      $key = $this->newKeyForObjectPHID($request->getStr('objectPHID'));
      if (!$key) {
        return new Aphront404Response();
      }
      $is_new = true;
    }

    $cancel_uri = $key->getObject()->getSSHPublicKeyManagementURI($viewer);

    if ($key->getIsTrusted()) {
      $id = $key->getID();

      return $this->newDialog()
        ->setTitle(\Yii::t("app",'Can Not Edit Trusted Key'))
        ->appendParagraph(
          \Yii::t("app",
            'This key is trusted. Trusted keys can not be edited. '.
            'Use %s to revoke trust before editing the key.',
            phutil_tag(
              'tt',
              array(),
              "bin/almanac untrust-key --id {$id}")))
        ->addCancelButton($cancel_uri, \Yii::t("app",'Okay'));
    }

    $token = (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
      $viewer,
      $request,
      $cancel_uri);

    $v_name = $key->getName();
    $e_name = strlen($v_name) ? null : true;

    $v_key = $key->getEntireKey();
    $e_key = strlen($v_key) ? null : true;

    $validation_exception = null;
    if ($request->isFormPost()) {
      $type_create = PhabricatorTransactions::TYPE_CREATE;
      $type_name = PhabricatorAuthSSHKeyTransaction::TYPE_NAME;
      $type_key = PhabricatorAuthSSHKeyTransaction::TYPE_KEY;

      $e_name = null;
      $e_key = null;

      $v_name = $request->getStr('name');
      $v_key = $request->getStr('key');

      $xactions = array();

      if (!$key->getID()) {
        $xactions[] = (new PhabricatorAuthSSHKeyTransaction())
          ->setTransactionType(PhabricatorTransactions::TYPE_CREATE);
      }

      $xactions[] = (new PhabricatorAuthSSHKeyTransaction())
        ->setTransactionType($type_name)
        ->setNewValue($v_name);

      $xactions[] = (new PhabricatorAuthSSHKeyTransaction())
        ->setTransactionType($type_key)
        ->setNewValue($v_key);

      $editor = (new PhabricatorAuthSSHKeyEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnNoEffect(true);

      try {
        $editor->applyTransactions($key, $xactions);
        return (new AphrontRedirectResponse())->setURI($cancel_uri);
      } catch (PhabricatorApplicationTransactionValidationException $ex) {
        $validation_exception = $ex;
        $e_name = $ex->getShortMessage($type_name);
        $e_key = $ex->getShortMessage($type_key);
      }
    }

    $form = (new AphrontFormView())
      ->setUser($viewer)
      ->appendChild(
        (new AphrontFormTextControl())
          ->setLabel(\Yii::t("app",'Name'))
          ->setName('name')
          ->setError($e_name)
          ->setValue($v_name))
      ->appendChild(
        (new AphrontFormTextAreaControl())
          ->setLabel(\Yii::t("app",'Public Key'))
          ->setName('key')
          ->setValue($v_key)
          ->setError($e_key));

    if ($is_new) {
      $title = \Yii::t("app",'Upload SSH Public Key');
      $save_button = \Yii::t("app",'Upload Public Key');
      $form->addHiddenInput('objectPHID', $key->getObject()->getPHID());
    } else {
      $title = \Yii::t("app",'Edit SSH Public Key');
      $save_button = \Yii::t("app",'Save Changes');
    }

    return $this->newDialog()
      ->setTitle($title)
      ->setWidth(AphrontDialogView::WIDTH_FORM)
      ->setValidationException($validation_exception)
      ->appendForm($form)
      ->addSubmitButton($save_button)
      ->addCancelButton($cancel_uri);
  }

}
