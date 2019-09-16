<?php

namespace orangins\modules\settings\editors;

use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\cache\PhabricatorCaches;
use orangins\modules\people\cache\PhabricatorUserPreferencesCacheType;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserCache;
use orangins\modules\settings\application\PhabricatorSettingsApplication;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use orangins\modules\settings\models\PhabricatorUserPreferencesTransaction;
use orangins\modules\settings\setting\PhabricatorSetting;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\error\PhabricatorApplicationTransactionValidationError;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorUserPreferencesEditor
 * @package orangins\modules\settings\editors
 * @author 陈妙威
 */
final class PhabricatorUserPreferencesEditor
    extends PhabricatorApplicationTransactionEditor
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorApplicationClass()
    {
        return PhabricatorSettingsApplication::className();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorObjectsDescription()
    {
        return \Yii::t("app",'Settings');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTransactionTypes()
    {
        $types = parent::getTransactionTypes();

        $types[] = PhabricatorUserPreferencesTransaction::TYPE_SETTING;

        return $types;
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return array
     * @throws \PhutilJSONParserException

     * @author 陈妙威
     */
    protected function expandTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        $setting_key = $xaction->getMetadataValue(
            PhabricatorUserPreferencesTransaction::PROPERTY_SETTING);

        $settings = $this->getSettings();
        $setting = ArrayHelper::getValue($settings, $setting_key);
        if ($setting) {
            return $setting->expandSettingTransaction($object, $xaction);
        }

        return parent::expandTransaction($object, $xaction);
    }


    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return mixed
     * @throws \PhutilJSONParserException
     * @throws Exception
     * @author 陈妙威
     */
    protected function getCustomTransactionOldValue(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        $setting_key = $xaction->getMetadataValue(
            PhabricatorUserPreferencesTransaction::PROPERTY_SETTING);

        switch ($xaction->getTransactionType()) {
            case PhabricatorUserPreferencesTransaction::TYPE_SETTING:
                return $object->getPreference($setting_key);
        }

        return parent::getCustomTransactionOldValue($object, $xaction);
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @return mixed
     * @throws Exception
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    protected function getCustomTransactionNewValue(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        $actor = $this->getActor();

        $setting_key = $xaction->getMetadataValue(
            PhabricatorUserPreferencesTransaction::PROPERTY_SETTING);

        $settings = PhabricatorSetting::getAllEnabledSettings($actor);
        $setting = $settings[$setting_key];

        switch ($xaction->getTransactionType()) {
            case PhabricatorUserPreferencesTransaction::TYPE_SETTING:
                $value = $xaction->getNewValue();
                $value = $setting->getTransactionNewValue($value);
                return $value;
        }

        return parent::getCustomTransactionNewValue($object, $xaction);
    }

    /**
     * @param ActiveRecordPHID|PhabricatorUserPreferences $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws \PhutilJSONParserException
     * @throws Exception
     * @author 陈妙威
     */
    protected function applyCustomInternalTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        $setting_key = $xaction->getMetadataValue(
            PhabricatorUserPreferencesTransaction::PROPERTY_SETTING);

        switch ($xaction->getTransactionType()) {
            case PhabricatorUserPreferencesTransaction::TYPE_SETTING:
                $new_value = $xaction->getNewValue();
                if ($new_value === null) {
                    $object->unsetPreference($setting_key);
                } else {
                    $object->setPreference($setting_key, $new_value);
                }
                return;
        }

        return parent::applyCustomInternalTransaction($object, $xaction);
    }

    /**
     * @param ActiveRecordPHID $object
     * @param PhabricatorApplicationTransaction $xaction
     * @throws Exception
     * @author 陈妙威
     */
    protected function applyCustomExternalTransaction(
        ActiveRecordPHID $object,
        PhabricatorApplicationTransaction $xaction)
    {

        switch ($xaction->getTransactionType()) {
            case PhabricatorUserPreferencesTransaction::TYPE_SETTING:
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
        $settings = $this->getSettings();

        switch ($type) {
            case PhabricatorUserPreferencesTransaction::TYPE_SETTING:
                foreach ($xactions as $xaction) {
                    $setting_key = $xaction->getMetadataValue(
                        PhabricatorUserPreferencesTransaction::PROPERTY_SETTING);

                    $setting = ArrayHelper::getValue($settings, $setting_key);
                    if (!$setting) {
                        $errors[] = new PhabricatorApplicationTransactionValidationError(
                            $type,
                            \Yii::t("app",'Invalid'),
                            \Yii::t("app",
                                'There is no known application setting with key "%s".',
                                $setting_key),
                            $xaction);
                        continue;
                    }

                    try {
                        $setting->validateTransactionValue($xaction->getNewValue());
                    } catch (Exception $ex) {
                        $errors[] = new PhabricatorApplicationTransactionValidationError(
                            $type,
                            \Yii::t("app",'Invalid'),
                            $ex->getMessage(),
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
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    protected function applyFinalEffects(
        ActiveRecordPHID $object,
        array $xactions)
    {

        $user_phid = $object->getUserPHID();
        if ($user_phid) {
            PhabricatorUserCache::clearCache(PhabricatorUserPreferencesCacheType::KEY_PREFERENCES, $user_phid);
        } else {
            $cache = PhabricatorCaches::getMutableStructureCache();
            $cache->deleteKey(PhabricatorUser::getGlobalSettingsCacheKey());

            PhabricatorUserCache::clearCacheForAllUsers(PhabricatorUserPreferencesCacheType::KEY_PREFERENCES);
        }

        return $xactions;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getSettings()
    {
        $actor = $this->getActor();
        $settings = PhabricatorSetting::getAllEnabledSettings($actor);

        foreach ($settings as $key => $setting) {
            $setting = clone $setting;
            $setting->setViewer($actor);
            $settings[$key] = $setting;
        }

        return $settings;
    }

}
