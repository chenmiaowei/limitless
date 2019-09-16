<?php

namespace orangins\modules\settings\panel;

use orangins\lib\request\AphrontRequest;
use orangins\modules\settings\editors\PhabricatorSettingsEditEngine;
use orangins\modules\settings\setting\PhabricatorSetting;
use orangins\modules\transactions\editengine\PhabricatorEditPage;

/**
 * Class PhabricatorEditEngineSettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
abstract class PhabricatorEditEngineSettingsPanel
    extends PhabricatorSettingsPanel
{

    /**
     * @param AphrontRequest $request
     * @return wild
     * @throws \AphrontDuplicateKeyQueryException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException

     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    final public function processRequest(AphrontRequest $request)
    {
        $viewer = $this->getViewer();
        $user = $this->getUser();

        if ($user && ($user->getPHID() === $viewer->getPHID())) {
            $is_self = true;
        } else {
            $is_self = false;
        }

        if ($user && $user->getPHID()) {
            $profile_uri = '/people/manage/' . $user->getID() . '/';
        } else {
            $profile_uri = null;
        }

        $engine = (new PhabricatorSettingsEditEngine())
            ->setAction($this->getAction())
            ->setNavigation($this->getNavigation())
            ->setIsSelfEdit($is_self)
            ->setProfileURI($profile_uri);

        $preferences = $this->getPreferences();

        $engine->setTargetObject($preferences);
        return $engine->buildResponse();
    }

    /**
     * @return bool
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final public function isEnabled()
    {
        // Only enable the panel if it has any fields.
        $field_keys = $this->getPanelSettingsKeys();
        return (bool)$field_keys;
    }

    /**
     * @return null
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final public function newEditEnginePage()
    {
        $field_keys = $this->getPanelSettingsKeys();
        if (!$field_keys) {
            return null;
        }

        $key = $this->getPanelKey();
        $label = $this->getPanelName();
        $panel_uri = $this->getPanelURI();

        return (new PhabricatorEditPage())
            ->setKey($key)
            ->setLabel($label)
            ->setViewURI($panel_uri)
            ->setFieldKeys($field_keys);
    }

    /**
     * @return \dict
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final public function getPanelSettingsKeys()
    {
        $viewer = $this->getViewer();
        $settings = PhabricatorSetting::getAllEnabledSettings($viewer);

        $this_key = $this->getPanelKey();

        $panel_settings = array();
        foreach ($settings as $setting) {
            if ($setting->getSettingPanelKey() == $this_key) {
                $panel_settings[] = $setting;
            }
        }

        return mpull($panel_settings, 'getSettingKey');
    }
}
