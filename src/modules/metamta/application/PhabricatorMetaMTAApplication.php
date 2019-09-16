<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/27
 * Time: 10:13 PM
 */

namespace orangins\modules\metamta\application;

use orangins\lib\PhabricatorApplication;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use orangins\modules\metamta\option\PhabricatorMetaMTAConfigOptions;
use orangins\modules\metamta\phid\PhabricatorMetaMTAMailPHIDType;
use orangins\modules\metamta\settings\panel\PhabricatorEmailAddressesSettingsPanel;
use orangins\modules\metamta\settings\panel\PhabricatorEmailDeliverySettingsPanel;
use orangins\modules\metamta\settings\panel\PhabricatorEmailFormatSettingsPanel;
use orangins\modules\metamta\settings\panel\PhabricatorEmailPreferencesSettingsPanel;
use orangins\modules\metamta\settings\panelgroup\PhabricatorSettingsEmailPanelGroup;
use orangins\modules\metamta\settings\setting\PhabricatorEmailFormatSetting;
use orangins\modules\metamta\settings\setting\PhabricatorEmailNotificationsSetting;
use orangins\modules\metamta\settings\setting\PhabricatorEmailRePrefixSetting;
use orangins\modules\metamta\settings\setting\PhabricatorEmailSelfActionsSetting;
use orangins\modules\metamta\settings\setting\PhabricatorEmailStampsSetting;
use orangins\modules\metamta\settings\setting\PhabricatorEmailTagsSetting;
use orangins\modules\metamta\settings\setting\PhabricatorEmailVarySubjectsSetting;
use orangins\modules\metamta\typeahead\PhabricatorMetaMTAApplicationEmailDatasource;
use orangins\modules\metamta\typeahead\PhabricatorMetaMTAMailableDatasource;
use orangins\modules\metamta\typeahead\PhabricatorMetaMTAMailableFunctionDatasource;
use orangins\modules\metamta\workers\PhabricatorMetaMTAWorker;

/**
 * Class OranginsMetaMTAApplication
 * @package orangins\modules\metamta\application
 * @author 陈妙威
 */
class PhabricatorMetaMTAApplication extends PhabricatorApplication
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function applicationId()
    {
        return 'metamta';
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function controllerNamespace()
    {
        return 'orangins\modules\metamta\controllers';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function defaultRoute()
    {
        return '/metamta/index/query';
    }


    /**
     * @return string
     */
    public function getIcon()
    {
        return 'fa-send';
    }


    /**
     * @return string
     */
    public function getName()
    {
        return \Yii::t("app", 'Mail');
    }

    /**
     * @return string
     */
    public function getShortDescription()
    {
        return \Yii::t("app", 'Send and Receive Mail');
    }


    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getWorkers()
    {
        return [
            'PhabricatorMetaMTAWorker' => PhabricatorMetaMTAWorker::class,
        ];
    }

    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getSettings()
    {
        return [
            // 邮箱
            PhabricatorEmailFormatSetting::class,
            PhabricatorEmailNotificationsSetting::class,
            PhabricatorEmailRePrefixSetting::class,
            PhabricatorEmailSelfActionsSetting::class,
            PhabricatorEmailStampsSetting::class,
            PhabricatorEmailTagsSetting::class,
            PhabricatorEmailVarySubjectsSetting::class,
        ];
    }

    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getSettingPanels()
    {
        return [
            PhabricatorEmailAddressesSettingsPanel::class,
            PhabricatorEmailDeliverySettingsPanel::class,
            PhabricatorEmailFormatSettingsPanel::class,
            PhabricatorEmailPreferencesSettingsPanel::class,
        ];
    }

    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getSettingPanelGroups()
    {
        return [
            PhabricatorSettingsEmailPanelGroup::class,
        ];
    }


}