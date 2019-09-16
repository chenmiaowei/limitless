<?php

namespace orangins\modules\search\editors;

use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\search\application\PhabricatorSearchApplication;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfigurationTransaction;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;

/**
 * Class PhabricatorProfileMenuEditor
 * @package orangins\modules\search\editors
 * @author 陈妙威
 */
final class PhabricatorProfileMenuEditor extends PhabricatorApplicationTransactionEditor
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorApplicationClass()
    {
        return PhabricatorSearchApplication::class;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorObjectsDescription()
    {
        return \Yii::t("app", 'Profile Menu Items');
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

        $types[] =
            PhabricatorProfileMenuItemConfigurationTransaction::TYPE_PROPERTY;
        $types[] =
            PhabricatorProfileMenuItemConfigurationTransaction::TYPE_ORDER;
        $types[] =
            PhabricatorProfileMenuItemConfigurationTransaction::TYPE_VISIBILITY;

        return $types;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @author 陈妙威
     * @return

     */
    protected function getCustomTransactionOldValue(ActiveRecordPHID $object, PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorProfileMenuItemConfigurationTransaction::TYPE_PROPERTY:
                $key = $xaction->getMetadataValue('property.key');
                return $object->getMenuItemProperty($key, null);
            case PhabricatorProfileMenuItemConfigurationTransaction::TYPE_ORDER:
                return $object->getMenuItemOrder();
            case PhabricatorProfileMenuItemConfigurationTransaction::TYPE_VISIBILITY:
                return $object->getVisibility();
        }
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return array|int|string|void
     * @author 陈妙威
     */
    protected function getCustomTransactionNewValue(ActiveRecordPHID $object, PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorProfileMenuItemConfigurationTransaction::TYPE_PROPERTY:
            case PhabricatorProfileMenuItemConfigurationTransaction::TYPE_VISIBILITY:
                return $xaction->getNewValue();
            case PhabricatorProfileMenuItemConfigurationTransaction::TYPE_ORDER:
                return (int)$xaction->getNewValue();
        }
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws \yii\base\Exception

     * @author 陈妙威
     */
    protected function applyCustomInternalTransaction(ActiveRecordPHID $object, PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorProfileMenuItemConfigurationTransaction::TYPE_PROPERTY:
                $key = $xaction->getMetadataValue('property.key');
                $value = $xaction->getNewValue();
                $object->setMenuItemProperty($key, $value);
                return;
            case PhabricatorProfileMenuItemConfigurationTransaction::TYPE_ORDER:
                $object->setMenuItemOrder($xaction->getNewValue());
                return;
            case PhabricatorProfileMenuItemConfigurationTransaction::TYPE_VISIBILITY:
                $object->setVisibility($xaction->getNewValue());
                return;
        }

        return parent::applyCustomInternalTransaction($object, $xaction);
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function applyCustomExternalTransaction(ActiveRecordPHID $object, PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorProfileMenuItemConfigurationTransaction::TYPE_PROPERTY:
            case PhabricatorProfileMenuItemConfigurationTransaction::TYPE_ORDER:
            case PhabricatorProfileMenuItemConfigurationTransaction::TYPE_VISIBILITY:
                return;
        }

        return parent::applyCustomExternalTransaction($object, $xaction);
    }

    /**
     * @param ActiveRecordPHID $object
     * @param $type
     * @param array $xactions
     * @return array
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception

     * @author 陈妙威
     */
    protected function validateTransaction(ActiveRecordPHID $object, $type, array $xactions)
    {

        $errors = parent::validateTransaction($object, $type, $xactions);

        $actor = $this->getActor();
        $menu_item = $object->getMenuItem();
        $menu_item->setViewer($actor);

        switch ($type) {
            case PhabricatorProfileMenuItemConfigurationTransaction::TYPE_PROPERTY:
                $key_map = array();
                foreach ($xactions as $xaction) {
                    $xaction_key = $xaction->getMetadataValue('property.key');
                    $old = $this->getCustomTransactionOldValue($object, $xaction);
                    $new = $xaction->getNewValue();
                    $key_map[$xaction_key][] = array(
                        'xaction' => $xaction,
                        'old' => $old,
                        'new' => $new,
                    );
                }

                foreach ($object->validateTransactions($key_map) as $error) {
                    $errors[] = $error;
                }
                break;
        }

        return $errors;
    }


}
