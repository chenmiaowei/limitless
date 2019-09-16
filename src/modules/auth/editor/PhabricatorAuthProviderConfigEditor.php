<?php

namespace orangins\modules\auth\editor;

use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\auth\models\PhabricatorAuthProviderConfig;
use orangins\modules\auth\models\PhabricatorAuthProviderConfigTransaction;
use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorAuthProviderConfigEditor
 * @package orangins\modules\auth\editor
 * @author 陈妙威
 */
final class PhabricatorAuthProviderConfigEditor
    extends PhabricatorApplicationTransactionEditor
{

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
        return \Yii::t("app",'Auth Providers');
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

        $types[] = PhabricatorAuthProviderConfigTransaction::TYPE_ENABLE;
        $types[] = PhabricatorAuthProviderConfigTransaction::TYPE_LOGIN;
        $types[] = PhabricatorAuthProviderConfigTransaction::TYPE_REGISTRATION;
        $types[] = PhabricatorAuthProviderConfigTransaction::TYPE_LINK;
        $types[] = PhabricatorAuthProviderConfigTransaction::TYPE_UNLINK;
        $types[] = PhabricatorAuthProviderConfigTransaction::TYPE_TRUST_EMAILS;
        $types[] = PhabricatorAuthProviderConfigTransaction::TYPE_AUTO_LOGIN;
        $types[] = PhabricatorAuthProviderConfigTransaction::TYPE_PROPERTY;

        return $types;
    }

    /**
     * @param ActiveRecordPHID|PhabricatorAuthProviderConfig  $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return int

     * @author 陈妙威
     */
    protected function getCustomTransactionOldValue(
        ActiveRecordPHID  $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorAuthProviderConfigTransaction::TYPE_ENABLE:
                if ($object->getIsEnabled() === null) {
                    return null;
                } else {
                    return (int)$object->getIsEnabled();
                }
            case PhabricatorAuthProviderConfigTransaction::TYPE_LOGIN:
                return (int)$object->getShouldAllowLogin();
            case PhabricatorAuthProviderConfigTransaction::TYPE_REGISTRATION:
                return (int)$object->getShouldAllowRegistration();
            case PhabricatorAuthProviderConfigTransaction::TYPE_LINK:
                return (int)$object->getShouldAllowLink();
            case PhabricatorAuthProviderConfigTransaction::TYPE_UNLINK:
                return (int)$object->getShouldAllowUnlink();
            case PhabricatorAuthProviderConfigTransaction::TYPE_TRUST_EMAILS:
                return (int)$object->getShouldTrustEmails();
            case PhabricatorAuthProviderConfigTransaction::TYPE_AUTO_LOGIN:
                return (int)$object->getShouldAutoLogin();
            case PhabricatorAuthProviderConfigTransaction::TYPE_PROPERTY:
                $key = $xaction->getMetadataValue(
                    PhabricatorAuthProviderConfigTransaction::PROPERTY_KEY);
                return $object->getProperty($key);
        }
    }

    /**
     * @param ActiveRecordPHID  $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return array|string|void
     * @author 陈妙威
     */
    protected function getCustomTransactionNewValue(
        ActiveRecordPHID  $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorAuthProviderConfigTransaction::TYPE_ENABLE:
            case PhabricatorAuthProviderConfigTransaction::TYPE_LOGIN:
            case PhabricatorAuthProviderConfigTransaction::TYPE_REGISTRATION:
            case PhabricatorAuthProviderConfigTransaction::TYPE_LINK:
            case PhabricatorAuthProviderConfigTransaction::TYPE_UNLINK:
            case PhabricatorAuthProviderConfigTransaction::TYPE_TRUST_EMAILS:
            case PhabricatorAuthProviderConfigTransaction::TYPE_AUTO_LOGIN:
            case PhabricatorAuthProviderConfigTransaction::TYPE_PROPERTY:
                return $xaction->getNewValue();
        }
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return

     * @author 陈妙威
     */
    protected function applyCustomInternalTransaction(
        ActiveRecordPHID  $object,
        PhabricatorApplicationTransaction $xaction)
    {
        $v = $xaction->getNewValue();
        switch ($xaction->getTransactionType()) {
            case PhabricatorAuthProviderConfigTransaction::TYPE_ENABLE:
                return $object->setIsEnabled($v);
            case PhabricatorAuthProviderConfigTransaction::TYPE_LOGIN:
                return $object->setShouldAllowLogin($v);
            case PhabricatorAuthProviderConfigTransaction::TYPE_REGISTRATION:
                return $object->setShouldAllowRegistration($v);
            case PhabricatorAuthProviderConfigTransaction::TYPE_LINK:
                return $object->setShouldAllowLink($v);
            case PhabricatorAuthProviderConfigTransaction::TYPE_UNLINK:
                return $object->setShouldAllowUnlink($v);
            case PhabricatorAuthProviderConfigTransaction::TYPE_TRUST_EMAILS:
                return $object->setShouldTrustEmails($v);
            case PhabricatorAuthProviderConfigTransaction::TYPE_AUTO_LOGIN:
                return $object->setShouldAutoLogin($v);
            case PhabricatorAuthProviderConfigTransaction::TYPE_PROPERTY:
                $key = $xaction->getMetadataValue(
                    PhabricatorAuthProviderConfigTransaction::PROPERTY_KEY);
                return $object->setProperty($key, $v);
        }
    }

    /**
     * @param ActiveRecordPHID  $object
     * @param PhabricatorApplicationTransaction $xaction
     * @author 陈妙威
     */
    protected function applyCustomExternalTransaction(
        ActiveRecordPHID  $object,
        PhabricatorApplicationTransaction $xaction)
    {
        return;
    }

    /**
     * @param PhabricatorApplicationTransaction $u
     * @param PhabricatorApplicationTransaction $v
     * @return PhabricatorApplicationTransaction|null
     * @author 陈妙威
     */
    protected function mergeTransactions(
        PhabricatorApplicationTransaction $u,
        PhabricatorApplicationTransaction $v)
    {

        $type = $u->getTransactionType();
        switch ($type) {
            case PhabricatorAuthProviderConfigTransaction::TYPE_ENABLE:
            case PhabricatorAuthProviderConfigTransaction::TYPE_LOGIN:
            case PhabricatorAuthProviderConfigTransaction::TYPE_REGISTRATION:
            case PhabricatorAuthProviderConfigTransaction::TYPE_LINK:
            case PhabricatorAuthProviderConfigTransaction::TYPE_UNLINK:
            case PhabricatorAuthProviderConfigTransaction::TYPE_TRUST_EMAILS:
            case PhabricatorAuthProviderConfigTransaction::TYPE_AUTO_LOGIN:
                // For these types, last transaction wins.
                return $v;
        }

        return parent::mergeTransactions($u, $v);
    }

}
