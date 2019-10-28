<?php

namespace orangins\modules\settings\panel;

use Exception;
use orangins\lib\PhabricatorApplication;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\lib\view\form\control\AphrontFormStaticControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\settings\panelgroup\PhabricatorSettingsEmailPanelGroup;
use orangins\modules\settings\setting\PhabricatorEmailTagsSetting;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException;
use PhutilClassMapQuery;
use PhutilInvalidStateException;
use PhutilJSONParserException;
use PhutilMethodNotImplementedException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorEmailPreferencesSettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorEmailPreferencesSettingsPanel
    extends PhabricatorSettingsPanel
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelKey()
    {
        return 'emailpreferences';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return Yii::t("app", 'Email Preferences');
    }

    /**
     * @return const|string
     * @author 陈妙威
     */
    public function getPanelGroupKey()
    {
        return PhabricatorSettingsEmailPanelGroup::PANELGROUPKEY;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isManagementPanel()
    {
        if ($this->getUser()->getIsMailingList()) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isTemplatePanel()
    {
        return true;
    }

    /**
     * @param AphrontRequest $request
     * @return
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionValidationException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
     * @throws InvalidConfigException*@throws \Exception
     * @throws Exception
     * @author 陈妙威
     */
    public function processRequest(AphrontRequest $request)
    {
        $viewer = $this->getViewer();
        $user = $this->getUser();

        $preferences = $this->getPreferences();

        $value_email = PhabricatorEmailTagsSetting::VALUE_EMAIL;

        $errors = array();
        if ($request->isFormPost()) {
            $new_tags = $request->getArr('mailtags');
            $mailtags = $preferences->getPreference('mailtags', array());
            $all_tags = $this->getAllTags($user);

            foreach ($all_tags as $key => $label) {
                $mailtags[$key] = (int)ArrayHelper::getValue($new_tags, $key, $value_email);
            }

            $this->writeSetting(
                $preferences,
                PhabricatorEmailTagsSetting::SETTINGKEY,
                $mailtags);

            return (new AphrontRedirectResponse())->setURI($this->getPanelURI('?saved=true'));
        }

        $mailtags = $preferences->getSettingValue(
            PhabricatorEmailTagsSetting::SETTINGKEY);

        $form = (new AphrontFormView())
            ->setUser($viewer);

        $to = Url::to(['/herald/index/query']);
        $form->appendRemarkupInstructions(
            Yii::t("app",
                'You can adjust **Application Settings** here to customize when ' .
                'you are emailed and notified.' .
                "\n\n" .
                "| Setting | Effect\n" .
                "| ------- | -------\n" .
                "| Email | You will receive an email and a notification, but the " .
                "notification will be marked \"read\".\n" .
                "| Notify | You will receive an unread notification only.\n" .
                "| Ignore | You will receive nothing.\n" .
                "\n\n" .
                'If an update makes several changes (like adding CCs to a task, ' .
                'closing it, and adding a comment) you will receive the strongest ' .
                'notification any of the changes is configured to deliver.' .
                "\n\n" .
                'These preferences **only** apply to objects you are connected to ' .
                '(for example, Revisions where you are a reviewer or tasks you are ' .
                'CC\'d on). To receive email alerts when other objects are created, ' .
                'configure [[ {0} | Herald Rules ]].', [
                    $to
                ]));

        $editors = $this->getAllEditorsWithTags($user);

        // Find all the tags shared by more than one application, and put them
        // in a "common" group.
        $all_tags = array();
        foreach ($editors as $editor) {
            foreach ($editor->getMailTagsMap() as $tag => $name) {
                if (empty($all_tags[$tag])) {
                    $all_tags[$tag] = array(
                        'count' => 0,
                        'name' => $name,
                    );
                }
                $all_tags[$tag]['count'];
            }
        }

        $common_tags = array();
        foreach ($all_tags as $tag => $info) {
            if ($info['count'] > 1) {
                $common_tags[$tag] = $info['name'];
            }
        }

        // Build up the groups of application-specific options.
        $tag_groups = array();
        foreach ($editors as $editor) {
            $tag_groups[] = array(
                $editor->getEditorObjectsDescription(),
                array_diff_key($editor->getMailTagsMap(), $common_tags),
            );
        }

        // Sort them, then put "Common" at the top.
        $tag_groups = isort($tag_groups, 0);
        if ($common_tags) {
            array_unshift($tag_groups, array(Yii::t("app", 'Common'), $common_tags));
        }

        // Finally, build the controls.
        foreach ($tag_groups as $spec) {
            list($label, $map) = $spec;
            $control = $this->buildMailTagControl($label, $map, $mailtags);
            $form->appendChild($control);
        }

        $form
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->setValue(Yii::t("app", 'Save Preferences')));

        $form_box = (new PHUIObjectBoxView())
            ->setHeaderText(Yii::t("app", 'Email Preferences'))
            ->setFormSaved($request->getStr('saved'))
            ->setFormErrors($errors)
            ->setBackground(PHUIObjectBoxView::WHITE_CONFIG)
            ->setForm($form);

        return $form_box;
    }

    /**
     * @param PhabricatorUser|null $user
     * @return mixed
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    private function getAllEditorsWithTags(PhabricatorUser $user = null)
    {
        /** @var PhabricatorApplicationTransactionEditor[] $editors */
        $editors = (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorApplicationTransactionEditor::className())
            ->setFilterMethod('getMailTagsMap')
            ->execute();

        foreach ($editors as $key => $editor) {
            // Remove editors for applications which are not installed.
            $app = $editor->getEditorApplicationClass();
            if ($app !== null && $user !== null) {
                if (!PhabricatorApplication::isClassInstalledForViewer($app, $user)) {
                    unset($editors[$key]);
                }
            }
        }

        return $editors;
    }

    /**
     * @param PhabricatorUser|null $user
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function getAllTags(PhabricatorUser $user = null)
    {
        $tags = array();
        foreach ($this->getAllEditorsWithTags($user) as $editor) {
            $tags += $editor->getMailTagsMap();
        }
        return $tags;
    }

    /**
     * @param $control_label
     * @param array $tags
     * @param array $prefs
     * @return AphrontFormStaticControl
     * @throws Exception
     * @author 陈妙威
     */
    private function buildMailTagControl(
        $control_label,
        array $tags,
        array $prefs)
    {

        $value_email = PhabricatorEmailTagsSetting::VALUE_EMAIL;
        $value_notify = PhabricatorEmailTagsSetting::VALUE_NOTIFY;
        $value_ignore = PhabricatorEmailTagsSetting::VALUE_IGNORE;

        $content = array();
        foreach ($tags as $key => $label) {
            $select = AphrontFormSelectControl::renderSelectTag(
                (int)ArrayHelper::getValue($prefs, $key, $value_email),
                array(
                    $value_email => Yii::t("app", "\xE2\x9A\xAB Email"),
                    $value_notify => Yii::t("app", "\xE2\x97\x90 Notify"),
                    $value_ignore => Yii::t("app", "\xE2\x9A\xAA Ignore"),
                ),
                array(
                    'name' => 'mailtags[' . $key . ']',
                ));

            $content[] = phutil_tag(
                'div',
                array(
                    'class' => 'psb',
                ),
                array(
                    $select,
                    ' ',
                    $label,
                ));
        }

        $control = new AphrontFormStaticControl();
        $control->setLabel($control_label);
        $control->setValue($content);

        return $control;
    }

}
