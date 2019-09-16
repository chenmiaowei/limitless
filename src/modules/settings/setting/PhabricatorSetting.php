<?php

namespace orangins\modules\settings\setting;

use orangins\modules\settings\models\PhabricatorUserPreferences;
use orangins\modules\settings\models\PhabricatorUserPreferencesTransaction;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use PhutilClassMapQuery;
use PhutilInvalidStateException;
use PhutilSortVector;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use orangins\lib\OranginsObject;

/**
 * Class PhabricatorSetting
 * @package orangins\modules\settings\setting
 * @author 陈妙威
 */
abstract class PhabricatorSetting extends OranginsObject
{
    /**
     * @var PhabricatorUser
     */
    private $viewer = false;

    /**
     * @param PhabricatorUser|null $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer = null)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return PhabricatorUser
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function getViewer()
    {
        if ($this->viewer === false) {
            throw new PhutilInvalidStateException('setViewer');
        }
        return $this->viewer;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getSettingName();

    /**
     * @return null
     * @author 陈妙威
     */
    public function getSettingPanelKey()
    {
        return null;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    protected function getSettingOrder()
    {
        return 1000;
    }

    /**
     * @return PhutilSortVector
     * @author 陈妙威
     * @throws \Exception
     */
    public function getSettingOrderVector()
    {
        return (new PhutilSortVector())
            ->addInt($this->getSettingOrder())
            ->addString($this->getSettingName());
    }

    /**
     * @return null
     * @author 陈妙威
     */
    protected function getControlInstructions()
    {
        return null;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return bool
     * @author 陈妙威
     */
    protected function isEnabledForViewer(PhabricatorUser $viewer)
    {
        return true;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getSettingDefaultValue()
    {
        return null;
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public function getSettingKey()
    {
        return $this->getPhobjectClassConstant('SETTINGKEY');
    }


    /**
     * @param PhabricatorUser $viewer
     * @return PhabricatorSetting[]
     * @author 陈妙威
     */
    public static function getAllEnabledSettings(PhabricatorUser $viewer)
    {
        $settings = self::getAllSettings();
        foreach ($settings as $key => $setting) {
            if (!$setting->isEnabledForViewer($viewer)) {
                unset($settings[$key]);
            }
        }
        return $settings;
    }

    /**
     * @param $object
     * @return array
     * @author 陈妙威
     */
    final public function newCustomEditFields($object)
    {
        $fields = array();

        $field = $this->newCustomEditField($object);
        if ($field) {
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * @param $object
     * @return null
     * @author 陈妙威
     */
    protected function newCustomEditField($object)
    {
        return null;
    }

    /**
     * @param PhabricatorUserPreferences $object
     * @param PhabricatorEditField $template
     * @return PhabricatorEditField
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function newEditField($object, PhabricatorEditField $template)
    {
        $setting_property = PhabricatorUserPreferencesTransaction::PROPERTY_SETTING;
        $setting_key = $this->getSettingKey();
        $value = $object->getPreference($setting_key);
        $xaction_type = PhabricatorUserPreferencesTransaction::TYPE_SETTING;
        $label = $this->getSettingName();

        $template
            ->setKey($setting_key)
            ->setLabel($label)
            ->setValue($value)
            ->setTransactionType($xaction_type)
            ->setMetadataValue($setting_property, $setting_key);

        $instructions = $this->getControlInstructions();
        if (strlen($instructions)) {
            $template->setControlInstructions($instructions);
        }

        return $template;
    }

    /**
     * @param $value
     * @author 陈妙威
     */
    public function validateTransactionValue($value)
    {
        return;
    }

    /**
     * @param $value
     * @author 陈妙威
     */
    public function assertValidValue($value)
    {
        $this->validateTransactionValue($value);
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function getTransactionNewValue($value)
    {
        return $value;
    }

    /**
     * @param $object
     * @param $xaction
     * @return array
     * @author 陈妙威
     */
    public function expandSettingTransaction($object, $xaction)
    {
        return array($xaction);
    }

    /**
     * @param PhabricatorApplicationTransactionInterface $object
     * @param $key
     * @param $value
     * @return mixed
     * @throws \PhutilJSONParserException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function newSettingTransaction($object, $key, $value)
    {
        $setting_property = PhabricatorUserPreferencesTransaction::PROPERTY_SETTING;
        $xaction_type = PhabricatorUserPreferencesTransaction::TYPE_SETTING;

        $applicationTransactionTemplate = clone $object->getApplicationTransactionTemplate();
        return $applicationTransactionTemplate
            ->setTransactionType($xaction_type)
            ->setMetadataValue($setting_property, $key)
            ->setNewValue($value);
    }

    /**
     * @return PhabricatorSetting[]
     * @author 陈妙威
     */
    public static function getAllSettings()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getSettingKey')
            ->execute();
    }
}
