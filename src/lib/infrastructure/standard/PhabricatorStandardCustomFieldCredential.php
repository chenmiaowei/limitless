<?php

namespace orangins\lib\infrastructure\standard;

use orangins\lib\request\httpparametertype\AphrontPHIDHTTPParameterType;
use orangins\modules\conduit\parametertype\ConduitPHIDParameterType;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorStandardCustomFieldCredential
 * @package orangins\lib\infrastructure\standard
 * @author 陈妙威
 */
final class PhabricatorStandardCustomFieldCredential
    extends PhabricatorStandardCustomField
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFieldType()
    {
        return 'credential';
    }

    /**
     * @return array|\orangins\lib\infrastructure\customfield\field\list
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function buildFieldIndexes()
    {
        $indexes = array();

        $value = $this->getFieldValue();
        if (strlen($value)) {
            $indexes[] = $this->newStringIndex($value);
        }

        return $indexes;
    }

    /**
     * @param array $handles
     * @return mixed
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function renderEditControl(array $handles)
    {
        $provides_type = $this->getFieldConfigValue('credential.provides');
        $credential_type = $this->getFieldConfigValue('credential.type');

        $all_types = PassphraseCredentialType::getAllProvidesTypes();
        if (!in_array($provides_type, $all_types)) {
            $provides_type = PassphrasePasswordCredentialType::PROVIDES_TYPE;
        }

        $credentials = (new PassphraseCredentialQuery())
            ->setViewer($this->getViewer())
            ->withIsDestroyed(false)
            ->withProvidesTypes(array($provides_type))
            ->execute();

        return (new PassphraseCredentialControl())
            ->setViewer($this->getViewer())
            ->setLabel($this->getFieldName())
            ->setName($this->getFieldKey())
            ->setCaption($this->getCaption())
            ->setAllowNull(!$this->getRequired())
            ->setCredentialType($credential_type)
            ->setValue($this->getFieldValue())
            ->setError($this->getFieldError())
            ->setOptions($credentials);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getRequiredHandlePHIDsForPropertyView()
    {
        $value = $this->getFieldValue();
        if ($value) {
            return array($value);
        }
        return array();
    }

    /**
     * @param array $handles
     * @return mixed|null
     * @author 陈妙威
     */
    public function renderPropertyViewValue(array $handles)
    {
        $value = $this->getFieldValue();
        if ($value) {
            return $handles[$value]->renderLink();
        }
        return null;
    }

    /**
     * @param PhabricatorApplicationTransactionEditor $editor
     * @param $type
     * @param array $xactions
     * @return array|\orangins\lib\infrastructure\customfield\field\list
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function validateApplicationTransactions(
        PhabricatorApplicationTransactionEditor $editor,
        $type,
        array $xactions)
    {

        $errors = parent::validateApplicationTransactions(
            $editor,
            $type,
            $xactions);

        $ok = PassphraseCredentialControl::validateTransactions(
            $this->getViewer(),
            $xactions);

        if (!$ok) {
            foreach ($xactions as $xaction) {
                $errors[] = new PhabricatorApplicationTransactionValidationError(
                    $type,
                    \Yii::t("app", 'Invalid'),
                    \Yii::t("app",
                        'The selected credential does not exist, or you do not have ' .
                        'permission to use it.'),
                    $xaction);
                $this->setFieldError(\Yii::t("app", 'Invalid'));
            }
        }

        return $errors;
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return array
     * @author 陈妙威
     * @throws \PhutilJSONParserException
     */
    public function getApplicationTransactionRequiredHandlePHIDs(
        PhabricatorApplicationTransaction $xaction)
    {
        $phids = array();
        $old = $xaction->getOldValue();
        $new = $xaction->getNewValue();
        if ($old) {
            $phids[] = $old;
        }
        if ($new) {
            $phids[] = $new;
        }
        return $phids;
    }


    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return string
     * @throws \PhutilJSONParserException
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getApplicationTransactionTitle(
        PhabricatorApplicationTransaction $xaction)
    {
        $author_phid = $xaction->getAuthorPHID();

        $old = $xaction->getOldValue();
        $new = $xaction->getNewValue();

        if ($old && !$new) {
            return \Yii::t("app",
                '{0} removed {1} as {2}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $xaction->renderHandleLink($old),
                    $this->getFieldName()
                ]);
        } else if ($new && !$old) {
            return \Yii::t("app",
                '{0} set {1} to {2}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    $xaction->renderHandleLink($new)
                ]);
        } else {
            return \Yii::t("app",
                '{0} changed {1} from {2} to {3}.',
                [
                    $xaction->renderHandleLink($author_phid),
                    $this->getFieldName(),
                    $xaction->renderHandleLink($old),
                    $xaction->renderHandleLink($new)
                ]);
        }
    }


    /**
     * @return null|AphrontPHIDHTTPParameterType
     * @author 陈妙威
     */
    protected function getHTTPParameterType()
    {
        return new AphrontPHIDHTTPParameterType();
    }

    /**
     * @return null|ConduitPHIDParameterType
     * @author 陈妙威
     */
    protected function newConduitSearchParameterType()
    {
        return new ConduitPHIDParameterType();
    }

    /**
     * @return null|ConduitPHIDParameterType
     * @author 陈妙威
     */
    protected function newConduitEditParameterType()
    {
        return new ConduitPHIDParameterType();
    }

}
