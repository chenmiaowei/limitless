<?php

namespace orangins\modules\config\editor;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\modules\config\check\PhabricatorSetupCheck;
use orangins\modules\config\models\PhabricatorConfigEntry;
use orangins\modules\config\models\PhabricatorConfigTransaction;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorConfigEditor
 * @package orangins\modules\config\editor
 * @author 陈妙威
 */
final class PhabricatorConfigEditor
    extends PhabricatorApplicationTransactionEditor
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorApplicationClass()
    {
        return 'PhabricatorConfigApplication';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorObjectsDescription()
    {
        return \Yii::t("app",'Phabricator Configuration');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTransactionTypes()
    {
        $types = parent::getTransactionTypes();

        $types[] = PhabricatorConfigTransaction::TYPE_EDIT;

        return $types;
    }

    /**
     * @param ActiveRecordPHID|PhabricatorConfigEntry $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return array
     * @author 陈妙威
     */
    protected function getCustomTransactionOldValue(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorConfigTransaction::TYPE_EDIT:
                return array(
                    'deleted' => (int)$object->getIsDeleted(),
                    'value' => $object->getValue(),
                );
        }
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return mixed
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    protected function getCustomTransactionNewValue(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorConfigTransaction::TYPE_EDIT:
                return $xaction->getNewValue();
        }
    }

    /**
     * @param ActiveRecordPHID|PhabricatorConfigEntry $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws \PhutilJSONParserException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function applyCustomInternalTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorConfigTransaction::TYPE_EDIT:
                $v = $xaction->getNewValue();

                // If this is a defined configuration option (vs a straggler from an
                // old version of Phabricator or a configuration file misspelling)
                // submit it to the validation gauntlet.
                $key = $object->getConfigKey();
                $all_options = PhabricatorApplicationConfigOptions::loadAllOptions();
                $option = ArrayHelper::getValue($all_options, $key);
                if ($option) {
                    $option->getGroup()->validateOption(
                        $option,
                        $v['value']);
                }

                $object->setIsDeleted((int)$v['deleted']);
                $object->setValue($v['value']);
                break;
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
     * @param PhabricatorApplicationTransaction $u
     * @param PhabricatorApplicationTransaction $v
     * @return null|PhabricatorApplicationTransaction

     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    protected function mergeTransactions(
        PhabricatorApplicationTransaction $u,
        PhabricatorApplicationTransaction $v)
    {

        $type = $u->getTransactionType();
        switch ($type) {
            case PhabricatorConfigTransaction::TYPE_EDIT:
                return $v;
        }

        return parent::mergeTransactions($u, $v);
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return bool
     * @throws \PhutilJSONParserException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function transactionHasEffect(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        $old = $xaction->getOldValue();
        $new = $xaction->getNewValue();

        $type = $xaction->getTransactionType();
        switch ($type) {
            case PhabricatorConfigTransaction::TYPE_EDIT:
                // If an edit deletes an already-deleted entry, no-op it.
                if (ArrayHelper::getValue($old, 'deleted') && ArrayHelper::getValue($new, 'deleted')) {
                    return false;
                }
                break;
        }

        return parent::transactionHasEffect($object, $xaction);
    }

    /**
     * @param $object
     * @param array $xactions
     * @return array
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    protected function didApplyTransactions($object, array $xactions)
    {
        // Force all the setup checks to run on the next page load.
        PhabricatorSetupCheck::deleteSetupCheckCache();

        return $xactions;
    }

    /**
     * @param PhabricatorUser $user
     * @param PhabricatorConfigEntry $config_entry
     * @param $value
     * @param PhabricatorContentSource $source
     * @param null $acting_as_phid
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException

     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilJSONParserException
     * @throws \Exception
     * @author 陈妙威
     */
    public static function storeNewValue(
        PhabricatorUser $user,
        PhabricatorConfigEntry $config_entry,
        $value,
        PhabricatorContentSource $source,
        $acting_as_phid = null)
    {

        $xaction = (new PhabricatorConfigTransaction())
            ->setTransactionType(PhabricatorConfigTransaction::TYPE_EDIT)
            ->setNewValue(
                array(
                    'deleted' => false,
                    'value' => $value,
                ));

        $editor = (new PhabricatorConfigEditor())
            ->setActor($user)
            ->setContinueOnNoEffect(true)
            ->setContentSource($source);

        if ($acting_as_phid) {
            $editor->setActingAsPHID($acting_as_phid);
        }

        $editor->applyTransactions($config_entry, array($xaction));
    }

    /**
     * @param PhabricatorUser $user
     * @param PhabricatorConfigEntry $config_entry
     * @param PhabricatorContentSource $source
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException

     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilJSONParserException
     * @throws \Exception
     * @author 陈妙威
     */
    public static function deleteConfig(
        PhabricatorUser $user,
        PhabricatorConfigEntry $config_entry,
        PhabricatorContentSource $source)
    {

        $xaction = (new PhabricatorConfigTransaction())
            ->setTransactionType(PhabricatorConfigTransaction::TYPE_EDIT)
            ->setNewValue(
                array(
                    'deleted' => true,
                    'value' => null,
                ));

        $editor = (new PhabricatorConfigEditor())
            ->setActor($user)
            ->setContinueOnNoEffect(true)
            ->setContentSource($source);

        $editor->applyTransactions($config_entry, array($xaction));
    }

}
