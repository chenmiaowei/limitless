<?php

namespace orangins\modules\auth\actions;

use Exception;
use orangins\lib\infrastructure\util\PhabricatorSSHKeyGenerator;
use orangins\modules\auth\editor\PhabricatorAuthSSHKeyEditor;
use orangins\modules\auth\models\PhabricatorAuthSSHKeyTransaction;
use orangins\modules\transactions\constants\PhabricatorTransactions;

final class PhabricatorAuthSSHKeyGenerateController
    extends PhabricatorAuthSSHKeyAction
{

    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $key = $this->newKeyForObjectPHID($request->getStr('objectPHID'));
        if (!$key) {
            return new Aphront404Response();
        }

        $cancel_uri = $key->getObject()->getSSHPublicKeyManagementURI($viewer);

        $token = (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $viewer,
            $request,
            $cancel_uri);

        if ($request->isFormPost()) {
            $default_name = $key->getObject()->getSSHKeyDefaultName();

            $keys = PhabricatorSSHKeyGenerator::generateKeypair();
            list($public_key, $private_key) = $keys;

            $key_name = $default_name . '.key';

            $file = PhabricatorFile::newFromFileData(
                $private_key,
                array(
                    'name' => $key_name,
                    'ttl.relative' => phutil_units('10 minutes in seconds'),
                    'viewPolicy' => $viewer->getPHID(),
                ));

            $public_key = PhabricatorAuthSSHPublicKey::newFromRawKey($public_key);

            $type = $public_key->getType();
            $body = $public_key->getBody();
            $comment = \Yii::t("app", 'Generated');

            $entire_key = "{$type} {$body} {$comment}";

            $type_create = PhabricatorTransactions::TYPE_CREATE;
            $type_name = PhabricatorAuthSSHKeyTransaction::TYPE_NAME;
            $type_key = PhabricatorAuthSSHKeyTransaction::TYPE_KEY;

            $xactions = array();

            $xactions[] = (new PhabricatorAuthSSHKeyTransaction())
                ->setTransactionType(PhabricatorTransactions::TYPE_CREATE);

            $xactions[] = (new PhabricatorAuthSSHKeyTransaction())
                ->setTransactionType($type_name)
                ->setNewValue($default_name);

            $xactions[] = (new PhabricatorAuthSSHKeyTransaction())
                ->setTransactionType($type_key)
                ->setNewValue($entire_key);

            $editor = (new PhabricatorAuthSSHKeyEditor())
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->applyTransactions($key, $xactions);

            $download_link = phutil_tag(
                'a',
                array(
                    'href' => $file->getDownloadURI(),
                ),
                array(
                    (new PHUIIconView())->setIcon('fa-download'),
                    ' ',
                    \Yii::t("app", 'Download Private Key (%s)', $key_name),
                ));
            $download_link = phutil_tag('strong', array(), $download_link);

            // NOTE: We're disabling workflow on cancel so the page reloads, showing
            // the new key.

            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'Download Private Key'))
                ->appendParagraph(
                    \Yii::t("app",
                        'A keypair has been generated, and the public key has been ' .
                        'added as a recognized key.'))
                ->appendParagraph($download_link)
                ->appendParagraph(
                    \Yii::t("app",
                        'After you download the private key, it will be destroyed. ' .
                        'You will not be able to retrieve it if you lose your copy.'))
                ->setDisableWorkflowOnCancel(true)
                ->addCancelButton($cancel_uri, \Yii::t("app", 'Done'));
        }

        try {
            PhabricatorSSHKeyGenerator::assertCanGenerateKeypair();

            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'Generate New Keypair'))
                ->addHiddenInput('objectPHID', $key->getObject()->getPHID())
                ->appendParagraph(
                    \Yii::t("app",
                        'This workflow will generate a new SSH keypair, add the public ' .
                        'key, and let you download the private key.'))
                ->appendParagraph(
                    \Yii::t("app", 'Phabricator will not retain a copy of the private key.'))
                ->addSubmitButton(\Yii::t("app", 'Generate New Keypair'))
                ->addCancelButton($cancel_uri);
        } catch (Exception $ex) {
            return $this->newDialog()
                ->setTitle(\Yii::t("app", 'Unable to Generate Keys'))
                ->appendParagraph($ex->getMessage())
                ->addCancelButton($cancel_uri);
        }
    }

}
