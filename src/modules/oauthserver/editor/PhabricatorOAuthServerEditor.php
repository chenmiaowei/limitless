<?php

namespace orangins\modules\oauthserver\editor;

use Exception;
use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\oauthserver\application\PhabricatorOAuthServerApplication;
use orangins\modules\oauthserver\models\PhabricatorOAuthServerTransaction;
use orangins\modules\oauthserver\PhabricatorOAuthServer;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\error\PhabricatorApplicationTransactionValidationError;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorOAuthServerEditor
 * @package orangins\modules\oauthserver\editor
 * @author 陈妙威
 */
final class PhabricatorOAuthServerEditor
    extends PhabricatorApplicationTransactionEditor
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorApplicationClass()
    {
        return PhabricatorOAuthServerApplication::className();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorObjectsDescription()
    {
        return pht('OAuth Applications');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTransactionTypes()
    {
        $types = parent::getTransactionTypes();

        $types[] = PhabricatorOAuthServerTransaction::TYPE_NAME;
        $types[] = PhabricatorOAuthServerTransaction::TYPE_REDIRECT_URI;
        $types[] = PhabricatorOAuthServerTransaction::TYPE_DISABLED;

        $types[] = PhabricatorTransactions::TYPE_VIEW_POLICY;
        $types[] = PhabricatorTransactions::TYPE_EDIT_POLICY;

        return $types;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @author 陈妙威
     * @return
     */
    protected function getCustomTransactionOldValue(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorOAuthServerTransaction::TYPE_NAME:
                return $object->getName();
            case PhabricatorOAuthServerTransaction::TYPE_REDIRECT_URI:
                return $object->getRedirectURI();
            case PhabricatorOAuthServerTransaction::TYPE_DISABLED:
                return $object->getIsDisabled();
        }
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return int|mixed
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    protected function getCustomTransactionNewValue(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorOAuthServerTransaction::TYPE_NAME:
            case PhabricatorOAuthServerTransaction::TYPE_REDIRECT_URI:
                return $xaction->getNewValue();
            case PhabricatorOAuthServerTransaction::TYPE_DISABLED:
                return (int)$xaction->getNewValue();
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

        switch ($xaction->getTransactionType()) {
            case PhabricatorOAuthServerTransaction::TYPE_NAME:
                $object->setName($xaction->getNewValue());
                return;
            case PhabricatorOAuthServerTransaction::TYPE_REDIRECT_URI:
                $object->setRedirectURI($xaction->getNewValue());
                return;
            case PhabricatorOAuthServerTransaction::TYPE_DISABLED:
                $object->setIsDisabled($xaction->getNewValue());
                return;
        }

        return parent::applyCustomInternalTransaction($object, $xaction);
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws \Exception
     * @author 陈妙威
     */
    protected function applyCustomExternalTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorOAuthServerTransaction::TYPE_NAME:
            case PhabricatorOAuthServerTransaction::TYPE_REDIRECT_URI:
            case PhabricatorOAuthServerTransaction::TYPE_DISABLED:
                return;
        }

        return parent::applyCustomExternalTransaction($object, $xaction);
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

        switch ($type) {
            case PhabricatorOAuthServerTransaction::TYPE_NAME:
                $missing = $this->validateIsEmptyTextField(
                    $object->getName(),
                    $xactions);

                if ($missing) {
                    $error = new PhabricatorApplicationTransactionValidationError(
                        $type,
                        pht('Required'),
                        pht('OAuth applications must have a name.'),
                        nonempty(last($xactions), null));

                    $error->setIsMissingFieldError(true);
                    $errors[] = $error;
                }
                break;
            case PhabricatorOAuthServerTransaction::TYPE_REDIRECT_URI:
                $missing = $this->validateIsEmptyTextField(
                    $object->getRedirectURI(),
                    $xactions);
                if ($missing) {
                    $error = new PhabricatorApplicationTransactionValidationError(
                        $type,
                        pht('Required'),
                        pht('OAuth applications must have a valid redirect URI.'),
                        nonempty(last($xactions), null));

                    $error->setIsMissingFieldError(true);
                    $errors[] = $error;
                } else {
                    foreach ($xactions as $xaction) {
                        $redirect_uri = $xaction->getNewValue();

                        try {
                            $server = new PhabricatorOAuthServer();
                            $server->assertValidRedirectURI($redirect_uri);
                        } catch (Exception $ex) {
                            $errors[] = new PhabricatorApplicationTransactionValidationError(
                                $type,
                                pht('Invalid'),
                                $ex->getMessage(),
                                $xaction);
                        }
                    }
                }
                break;
        }

        return $errors;
    }

}
