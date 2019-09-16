<?php

namespace orangins\modules\auth\editor;

use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\auth\models\PhabricatorAuthSSHKeyTransaction;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\env\PhabricatorEnv;
use orangins\modules\auth\query\PhabricatorAuthSSHKeyQuery;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\auth\sshkey\PhabricatorAuthSSHPublicKey;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\error\PhabricatorApplicationTransactionValidationError;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use Exception;

/**
 * Class PhabricatorAuthSSHKeyEditor
 * @package orangins\modules\auth\editor
 * @author 陈妙威
 */
final class PhabricatorAuthSSHKeyEditor
    extends PhabricatorApplicationTransactionEditor
{

    /**
     * @var
     */
    private $isAdministrativeEdit;

    /**
     * @param $is_administrative_edit
     * @return $this
     * @author 陈妙威
     */
    public function setIsAdministrativeEdit($is_administrative_edit)
    {
        $this->isAdministrativeEdit = $is_administrative_edit;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsAdministrativeEdit()
    {
        return $this->isAdministrativeEdit;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorApplicationClass()
    {
        return PhabricatorAuthApplication::className();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorObjectsDescription()
    {
        return \Yii::t("app",'SSH Keys');
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getTransactionTypes()
    {
        $types = parent::getTransactionTypes();

        $types[] = PhabricatorAuthSSHKeyTransaction::TYPE_NAME;
        $types[] = PhabricatorAuthSSHKeyTransaction::TYPE_KEY;
        $types[] = PhabricatorAuthSSHKeyTransaction::TYPE_DEACTIVATE;

        return $types;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return bool
     * @author 陈妙威
     */
    protected function getCustomTransactionOldValue(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorAuthSSHKeyTransaction::TYPE_NAME:
                return $object->getName();
            case PhabricatorAuthSSHKeyTransaction::TYPE_KEY:
                return $object->getEntireKey();
            case PhabricatorAuthSSHKeyTransaction::TYPE_DEACTIVATE:
                return !$object->getIsActive();
        }

    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return array|bool|string
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    protected function getCustomTransactionNewValue(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorAuthSSHKeyTransaction::TYPE_NAME:
            case PhabricatorAuthSSHKeyTransaction::TYPE_KEY:
                return $xaction->getNewValue();
            case PhabricatorAuthSSHKeyTransaction::TYPE_DEACTIVATE:
                return (bool)$xaction->getNewValue();
        }
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    protected function applyCustomInternalTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        $value = $xaction->getNewValue();
        switch ($xaction->getTransactionType()) {
            case PhabricatorAuthSSHKeyTransaction::TYPE_NAME:
                $object->setName($value);
                return;
            case PhabricatorAuthSSHKeyTransaction::TYPE_KEY:
                $public_key = PhabricatorAuthSSHPublicKey::newFromRawKey($value);

                $type = $public_key->getType();
                $body = $public_key->getBody();
                $comment = $public_key->getComment();

                $object->setKeyType($type);
                $object->setKeyBody($body);
                $object->setKeyComment($comment);
                return;
            case PhabricatorAuthSSHKeyTransaction::TYPE_DEACTIVATE:
                if ($value) {
                    $new = null;
                } else {
                    $new = 1;
                }

                $object->setIsActive($new);
                return;
        }
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @author 陈妙威
     */
    protected function applyCustomExternalTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {
        return;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param $type
     * @param array $xactions
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function validateTransaction(
        ActiveRecordPHID $object,
        $type,
        array $xactions)
    {

        $errors = parent::validateTransaction($object, $type, $xactions);
        $viewer = $this->requireActor();

        switch ($type) {
            case PhabricatorAuthSSHKeyTransaction::TYPE_NAME:
                $missing = $this->validateIsEmptyTextField(
                    $object->getName(),
                    $xactions);

                if ($missing) {
                    $error = new PhabricatorApplicationTransactionValidationError(
                        $type,
                        \Yii::t("app",'Required'),
                        \Yii::t("app",'SSH key name is required.'),
                        nonempty(last($xactions), null));

                    $error->setIsMissingFieldError(true);
                    $errors[] = $error;
                }
                break;

            case PhabricatorAuthSSHKeyTransaction::TYPE_KEY;
                $missing = $this->validateIsEmptyTextField(
                    $object->getName(),
                    $xactions);

                if ($missing) {
                    $error = new PhabricatorApplicationTransactionValidationError(
                        $type,
                        \Yii::t("app",'Required'),
                        \Yii::t("app",'SSH key material is required.'),
                        nonempty(last($xactions), null));

                    $error->setIsMissingFieldError(true);
                    $errors[] = $error;
                } else {
                    foreach ($xactions as $xaction) {
                        $new = $xaction->getNewValue();

                        try {
                            $public_key = PhabricatorAuthSSHPublicKey::newFromRawKey($new);
                        } catch (Exception $ex) {
                            $errors[] = new PhabricatorApplicationTransactionValidationError(
                                $type,
                                \Yii::t("app",'Invalid'),
                                $ex->getMessage(),
                                $xaction);
                            continue;
                        }

                        // The database does not have a unique key on just the <keyBody>
                        // column because we allow multiple accounts to revoke the same
                        // key, so we can't rely on database constraints to prevent users
                        // from adding keys that are on the revocation list back to their
                        // accounts. Explicitly check for a revoked copy of the key.

                        $revoked_keys = PhabricatorAuthSSHKey::find()
                            ->setViewer($viewer)
                            ->withObjectPHIDs(array($object->getObjectPHID()))
                            ->withIsActive(0)
                            ->withKeys(array($public_key))
                            ->execute();
                        if ($revoked_keys) {
                            $errors[] = new PhabricatorApplicationTransactionValidationError(
                                $type,
                                \Yii::t("app",'Revoked'),
                                \Yii::t("app",
                                    'This key has been revoked. Choose or generate a new, ' .
                                    'unique key.'),
                                $xaction);
                            continue;
                        }
                    }
                }
                break;

            case PhabricatorAuthSSHKeyTransaction::TYPE_DEACTIVATE:
                foreach ($xactions as $xaction) {
                    if (!$xaction->getNewValue()) {
                        $errors[] = new PhabricatorApplicationTransactionValidationError(
                            $type,
                            \Yii::t("app",'Invalid'),
                            \Yii::t("app",'SSH keys can not be reactivated.'),
                            $xaction);
                    }
                }
                break;
        }

        return $errors;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @param Exception $ex
     * @author 陈妙威
     */
    protected function didCatchDuplicateKeyException(
        ActiveRecordPHID $object,
        array $xactions,
        Exception $ex)
    {

        $errors = array();
        $errors[] = new PhabricatorApplicationTransactionValidationError(
            PhabricatorAuthSSHKeyTransaction::TYPE_KEY,
            \Yii::t("app",'Duplicate'),
            \Yii::t("app",
                'This public key is already associated with another user or device. ' .
                'Each key must unambiguously identify a single unique owner.'),
            null);

        throw new PhabricatorApplicationTransactionValidationException($errors);
    }


    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return bool
     * @author 陈妙威
     */
    protected function shouldSendMail(
        ActiveRecordPHID $object,
        array $xactions)
    {
        return true;
    }

    /**
     * @author 陈妙威
     */
    protected function getMailSubjectPrefix()
    {
        return \Yii::t("app",'[SSH Key]');
    }

    /**
     * @param ActiveRecordPHID $object
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getMailThreadID(ActiveRecordPHID $object)
    {
        return 'ssh-key-' . $object->getPHID();
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return array
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function applyFinalEffects(
        ActiveRecordPHID $object,
        array $xactions)
    {

        // After making any change to an SSH key, drop the authfile cache so it
        // is regenerated the next time anyone authenticates.
        PhabricatorAuthSSHKeyQuery::deleteSSHKeyCache();

        return $xactions;
    }


    /**
     * @param ActiveRecordPHID $object
     * @author 陈妙威
     * @return
     */
    protected function getMailTo(ActiveRecordPHID $object)
    {
        return $object->getObject()->getSSHKeyNotifyPHIDs();
    }

    /**
     * @param ActiveRecordPHID $object
     * @return array|mixed[]
     * @author 陈妙威
     */
    protected function getMailCC(ActiveRecordPHID $object)
    {
        return array();
    }

    /**
     * @param ActiveRecordPHID $object
     * @author 陈妙威
     * @return
     */
    protected function buildReplyHandler(ActiveRecordPHID $object)
    {
        return (new PhabricatorAuthSSHKeyReplyHandler())
            ->setMailReceiver($object);
    }

    /**
     * @param ActiveRecordPHID $object
     * @return PhabricatorMetaMTAMail
     * @throws Exception
     * @author 陈妙威
     */
    protected function buildMailTemplate(ActiveRecordPHID $object)
    {
        $id = $object->getID();
        $name = $object->getName();

        $mail = (new PhabricatorMetaMTAMail())
            ->setSubject(\Yii::t("app",'SSH Key %d: %s', $id, $name));

        // The primary value of this mail is alerting users to account compromises,
        // so force delivery. In particular, this mail should still be delivered
        // even if "self mail" is disabled.
        $mail->setForceDelivery(true);

        return $mail;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    protected function buildMailBody(
        ActiveRecordPHID $object,
        array $xactions)
    {

        $body = parent::buildMailBody($object, $xactions);

        if (!$this->getIsAdministrativeEdit()) {
            $body->addTextSection(
                \Yii::t("app",'SECURITY WARNING'),
                \Yii::t("app",
                    'If you do not recognize this change, it may indicate your account ' .
                    'has been compromised.'));
        }

        $detail_uri = $object->getURI();
        $detail_uri = PhabricatorEnv::getProductionURI($detail_uri);

        $body->addLinkSection(\Yii::t("app",'SSH KEY DETAIL'), $detail_uri);

        return $body;
    }


    /**
     * @return array
     * @author 陈妙威
     */
    protected function getCustomWorkerState()
    {
        return array(
            'isAdministrativeEdit' => $this->isAdministrativeEdit,
        );
    }

    /**
     * @param array $state
     * @return $this|PhabricatorApplicationTransactionEditor
     * @author 陈妙威
     */
    protected function loadCustomWorkerState(array $state)
    {
        $this->isAdministrativeEdit = ArrayHelper::getValue($state, 'isAdministrativeEdit');
        return $this;
    }


}
