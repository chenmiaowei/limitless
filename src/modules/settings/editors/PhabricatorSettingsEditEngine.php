<?php

namespace orangins\modules\settings\editors;

use orangins\lib\view\phui\PHUIHeaderView;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\settings\application\PhabricatorSettingsApplication;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use orangins\modules\settings\models\PhabricatorUserPreferencesTransaction;
use orangins\modules\settings\panel\PhabricatorEditEngineSettingsPanel;
use orangins\modules\settings\panel\PhabricatorSettingsPanel;
use orangins\modules\settings\query\PhabricatorUserPreferencesQuery;
use orangins\modules\settings\setting\PhabricatorSetting;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editengine\PhabricatorEditPage;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException;
use PhutilMethodNotImplementedException;

/**
 * Class PhabricatorSettingsEditEngine
 * @package orangins\modules\settings\editors
 * @author 陈妙威
 */
final class PhabricatorSettingsEditEngine
    extends PhabricatorEditEngine
{

    /**
     *
     */
    const ENGINECONST = 'settings.settings';

    /**
     * @var
     */
    private $isSelfEdit;
    /**
     * @var
     */
    private $profileURI;

    /**
     * @param $is_self_edit
     * @return $this
     * @author 陈妙威
     */
    public function setIsSelfEdit($is_self_edit)
    {
        $this->isSelfEdit = $is_self_edit;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsSelfEdit()
    {
        return $this->isSelfEdit;
    }

    /**
     * @param $profile_uri
     * @return $this
     * @author 陈妙威
     */
    public function setProfileURI($profile_uri)
    {
        $this->profileURI = $profile_uri;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getProfileURI()
    {
        return $this->profileURI;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isEngineConfigurable()
    {
        return false;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEngineName()
    {
        return \Yii::t("app",'Settings');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryHeader()
    {
        return \Yii::t("app",'Edit Settings Configurations');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryText()
    {
        return \Yii::t("app",'This engine is used to edit settings.');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getEngineApplicationClass()
    {
        return PhabricatorSettingsApplication::className();
    }

    /**
     * @return PhabricatorUserPreferences|\orangins\modules\transactions\editengine\PhabricatorEditEngineSubtypeInterface
     * @author 陈妙威
     */
    protected function newEditableObject()
    {
        return new PhabricatorUserPreferences();
    }

    /**
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|PhabricatorUserPreferencesQuery
     * @author 陈妙威
     */
    protected function newObjectQuery()
    {
        return new PhabricatorUserPreferencesQuery();
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateTitleText($object)
    {
        return \Yii::t("app",'Create Settings');
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateButtonText($object)
    {
        return \Yii::t("app",'Create Settings');
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectEditTitleText($object)
    {
        $page = $this->getSelectedPage();

        if ($page) {
            return $page->getLabel();
        }

        return \Yii::t("app",'Settings');
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectEditShortText($object)
    {
        if (!$object->getUser()) {
            return \Yii::t("app",'Global Defaults');
        } else {
            if ($this->getIsSelfEdit()) {
                return \Yii::t("app",'Personal Settings');
            } else {
                return \Yii::t("app",'Account Settings');
            }
        }
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateShortText()
    {
        return \Yii::t("app",'Create Settings');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getObjectName()
    {
        $page = $this->getSelectedPage();

        if ($page) {
            return $page->getLabel();
        }

        return \Yii::t("app",'Settings');
    }

    /**
     * @param $object
     * @return null
     * @author 陈妙威
     */
    protected function getPageHeader($object)
    {
        $user = $object->getUser();
        if ($user) {
            $text = \Yii::t("app",'Edit Settings ({0})', [$user->getUserName()]);
        } else {
            $text = \Yii::t("app",'Edit Global Settings');
        }

        $header = (new PHUIHeaderView())
            ->setHeader($text);

        return $header;
    }

    /**
     * @return string|void
     * @author 陈妙威
     */
    protected function getEditorURI()
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateCancelURI($object)
    {
        return '/settings/';
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectViewURI($object)
    {
        return $object->getEditURI();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getCreateNewObjectPolicy()
    {
        return PhabricatorPolicies::POLICY_ADMIN;
    }

    /**
     * @param $object
     * @return mixed|string
     * @author 陈妙威
     */
    public function getEffectiveObjectEditDoneURI($object)
    {
        return parent::getEffectiveObjectViewURI($object) . 'saved/';
    }

    /**
     * @param $object
     * @return mixed|null|string
     * @author 陈妙威
     */
    public function getEffectiveObjectEditCancelURI($object)
    {
        if (!$object->getUser()) {
            return '/settings/';
        }

        if ($this->getIsSelfEdit()) {
            return null;
        }

        if ($this->getProfileURI()) {
            return $this->getProfileURI();
        }

        return parent::getEffectiveObjectEditCancelURI($object);
    }

    /**
     * @param $object
     * @return array
     * @author 陈妙威
     * @throws \Exception
     */
    protected function newPages($object)
    {
        $viewer = $this->getViewer();
        $user = $object->getUser();

        $panels = PhabricatorSettingsPanel::getAllPanels();

        foreach ($panels as $key => $panel) {
            if (!($panel instanceof PhabricatorEditEngineSettingsPanel)) {
                unset($panels[$key]);
                continue;
            }

            $panel->setViewer($viewer);
            if ($user) {
                $panel->setUser($user);
            }
        }

        $pages = array();
        $uris = array();
        foreach ($panels as $key => $panel) {
            $uris[$key] = $panel->getPanelURI();

            $page = $panel->newEditEnginePage();
            if (!$page) {
                continue;
            }
            $pages[] = $page;
        }

        $more_pages = array(
            (new PhabricatorEditPage())
                ->setKey('extra')
                ->setLabel(\Yii::t("app",'Extra Settings'))
                ->setIsDefault(true),
        );

        foreach ($more_pages as $page) {
            $pages[] = $page;
        }

        return $pages;
    }

    /**
     * @param $object
     * @return array|\orangins\modules\transactions\editfield\PhabricatorEditField[]
     * @throws \Exception
     * @author 陈妙威
     */
    protected function buildCustomEditFields($object)
    {
        $viewer = $this->getViewer();
        $settings = PhabricatorSetting::getAllEnabledSettings($viewer);

        foreach ($settings as $key => $setting) {
            $setting = clone $setting;
            $setting->setViewer($viewer);
            $settings[$key] = $setting;
        }

        $settings = msortv($settings, 'getSettingOrderVector');

        $fields = array();
        foreach ($settings as $setting) {
            foreach ($setting->newCustomEditFields($object) as $field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * @param PhabricatorApplicationTransactionValidationException $ex
     * @param PhabricatorEditField $field
     * @return null
     * @author 陈妙威
     */
    protected function getValidationExceptionShortMessage(
        PhabricatorApplicationTransactionValidationException $ex,
        PhabricatorEditField $field)
    {

        // Settings fields all have the same transaction type so we need to make
        // sure the transaction is changing the same setting before matching an
        // error to a given field.
        $xaction_type = $field->getTransactionType();
        if ($xaction_type == PhabricatorUserPreferencesTransaction::TYPE_SETTING) {
            $property = PhabricatorUserPreferencesTransaction::PROPERTY_SETTING;

            $field_setting = ArrayHelper::getValue($field->getMetadata(), $property);
            foreach ($ex->getErrors() as $error) {
                if ($error->getType() !== $xaction_type) {
                    continue;
                }

                $xaction = $error->getTransaction();
                if (!$xaction) {
                    continue;
                }

                $xaction_setting = $xaction->getMetadataValue($property);
                if ($xaction_setting != $field_setting) {
                    continue;
                }

                $short_message = $error->getShortMessage();
                if ($short_message !== null) {
                    return $short_message;
                }
            }

            return null;
        }

        return parent::getValidationExceptionShortMessage($ex, $field);
    }

}
