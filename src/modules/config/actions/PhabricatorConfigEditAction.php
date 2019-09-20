<?php

namespace orangins\modules\config\actions;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormMarkupControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUITagView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\config\check\PhabricatorExtraConfigSetupCheck;
use orangins\modules\config\editor\PhabricatorConfigEditor;
use orangins\modules\config\exception\PhabricatorConfigValidationException;
use orangins\modules\config\models\PhabricatorConfigEntry;
use orangins\modules\config\models\PhabricatorConfigTransaction;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use orangins\modules\config\option\PhabricatorConfigOption;
use Exception;
use yii\helpers\Url;

/**
 * Class PhabricatorConfigEditAction
 * @package orangins\modules\config\actions
 * @author 陈妙威
 */
final class PhabricatorConfigEditAction
    extends PhabricatorConfigAction
{

    /**
     * @return AphrontRedirectResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \Throwable
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $key = $request->getURIData('key');

        $options = PhabricatorApplicationConfigOptions::loadAllOptions();
        if (empty($options[$key])) {
            $ancient = PhabricatorExtraConfigSetupCheck::getAncientConfig();
            if (isset($ancient[$key])) {
                $desc = \Yii::t("app",
                    "This configuration has been removed. You can safely delete " .
                    "it.\n\n%s",
                    $ancient[$key]);
            } else {
                $desc = \Yii::t("app",
                    'This configuration option is unknown. It may be misspelled, ' .
                    'or have existed in a previous version of Phabricator.');
            }

            // This may be a dead config entry, which existed in the past but no
            // longer exists. Allow it to be edited so it can be reviewed and
            // deleted.
            $option = (new PhabricatorConfigOption())
                ->setKey($key)
                ->setType('wild')
                ->setDefault(null)
                ->setDescription($desc);
            $group = null;
            $group_uri = $this->getApplicationURI();
        } else {
            $option = $options[$key];
            $group = $option->getGroup();
            $group_uri = $this->getApplicationURI('index/group', ['key' => $group->getKey()]);
        }

        $issue = $request->getStr('issue');
        if ($issue) {
            // If the user came here from an open setup issue, send them back.
            $done_uri = $this->getApplicationURI('index/issue', ['key' => $issue]);
        } else {
            $done_uri = $group_uri;
        }

        // Check if the config key is already stored in the database.
        // Grab the value if it is.
        $config_entry = PhabricatorConfigEntry::find()->andWhere([
            "config_key" => $key,
            "namespace" => 'default'
        ])->one();
        if (!$config_entry) {
            $config_entry = (new PhabricatorConfigEntry())
                ->setConfigKey($key)
                ->setNamespace('default')
                ->setIsDeleted(true);
        }

        $e_value = null;
        $errors = array();
        if ($request->isFormPost() && !$option->getLocked()) {

            $result = $this->readRequest(
                $option,
                $request);

            list($e_value, $value_errors, $display_value, $xaction) = $result;
            $errors = array_merge($errors, $value_errors);

            if (!$errors) {

                $editor = (new PhabricatorConfigEditor())
                    ->setActor($viewer)
                    ->setContinueOnNoEffect(true)
                    ->setContentSourceFromRequest($request);

                try {
                    $editor->applyTransactions($config_entry, array($xaction));
                    return (new AphrontRedirectResponse())->setURI($done_uri);
                } catch (PhabricatorConfigValidationException $ex) {
                    $e_value = \Yii::t("app", 'Invalid');
                    $errors[] = $ex->getMessage();
                }
            }
        } else {
            if ($config_entry->getIsDeleted()) {
                $display_value = null;
            } else {
                $display_value = $this->getDisplayValue(
                    $option,
                    $config_entry,
                    $config_entry->getValue());
            }
        }

        $form = (new AphrontFormView())
            ->setEncType('multipart/form-data');

        $error_view = null;
        if ($errors) {
            $error_view = (new PHUIInfoView())
                ->setSeverity(PHUIInfoView::SEVERITY_ERROR)
                ->setErrors($errors);
        }

        $doc_href = PhabricatorEnv::getDoclink(
            'Configuration Guide: Locked and Hidden Configuration');


        $doc_link = JavelinHtml::phutil_tag(
            'a',
            array(
                'href' => $doc_href,
                'target' => '_blank',
            ),
            \Yii::t("app", 'Learn more about locked and hidden options.'));

        $status_items = array();
        $tag = null;
        if ($option->getHidden()) {
            $tag = (new PHUITagView())
                ->setName(\Yii::t("app", 'Hidden'))
                ->setColor(PHUITagView::COLOR_GREY)
                ->setBorder(PHUITagView::BORDER_NONE)
                ->setType(PHUITagView::TYPE_SHADE);

            $message = \Yii::t("app",
                'This configuration is hidden and can not be edited or viewed from ' .
                'the web interface.');
            $status_items[] = (new PHUIInfoView())
                ->appendChild(array($message, ' ', $doc_link));
        } else if ($option->getLocked()) {
            $tag = (new PHUITagView())
                ->setName(\Yii::t("app", 'Locked'))
                ->setColor(PHUITagView::COLOR_DANGER)
                ->setBorder(PHUITagView::BORDER_NONE)
                ->setType(PHUITagView::TYPE_SHADE);

            $message = $option->getLockedMessage();
            $status_items[] = (new PHUIInfoView())
                ->appendChild(array($message, ' ', $doc_link));
        }

        if ($option->getHidden() || $option->getLocked()) {
            $controls = array();
        } else {
            $controls = $this->renderControls(
                $option,
                $display_value,
                $e_value);
        }

        $form
            ->setUser($viewer)
            ->addHiddenInput('issue', $request->getStr('issue'));

        $description = $option->newDescriptionRemarkupView($viewer);
        if ($description) {
            $form->appendChild(
                (new AphrontFormMarkupControl())
                    ->addInputClass("p-2")
                    ->setLabel(\Yii::t("app", 'Description'))
                    ->setValue($description));
        }

        if ($group) {
            $extra = $group->renderContextualDescription(
                $option,
                $request);
            if ($extra !== null) {
                $form->appendChild(
                    (new AphrontFormMarkupControl())
                        ->setValue($extra));
            }
        }

        foreach ($controls as $control) {
            $form->appendControl($control);
        }

        if (!$option->getLocked()) {
            $form->appendChild(
                (new AphrontFormSubmitControl())
                    ->addCancelButton($done_uri)
                    ->setValue(\Yii::t("app", 'Save Config Entry')));
        }

        $current_config = null;
        if (!$option->getHidden()) {
            $current_config = $this->renderDefaults($option, $config_entry);
            $current_config = $this->buildConfigBoxView(
                \Yii::t("app", 'Current Configuration'),
                $current_config);
        }

        $examples = $this->renderExamples($option);
        if ($examples) {
            $examples = $this->buildConfigBoxView(
                \Yii::t("app", 'Examples'),
                $examples);
        }

        $title = $key;

        $box_header = array();
        if ($group) {
            $box_header[] = JavelinHtml::phutil_tag(
                'a',
                array(
                    'href' => $group_uri,
                ),
                $group->getName());
            $box_header[] = " \xC2\xBB ";
        }
        $box_header[] = $key;

        $crumbs = $this->buildApplicationCrumbs();
        if ($group) {
            $crumbs->addTextCrumb($group->getName(), $group_uri);
        }
        $crumbs->addTextCrumb($key, Url::to(['/config/index/edit', "key" => $key]));
        $crumbs->setBorder(true);

        $form_box = $this->buildConfigBoxView($box_header, $form, $tag);

        $timeline = $this->buildTransactionTimeline($config_entry, PhabricatorConfigTransaction::find());
        $timeline->setShouldTerminate(true);

        $nav = $this->buildSideNavView();
        $nav->addClass("w-lg-100");
        $nav->selectFilter($group_uri);

        $header = $this->buildHeaderView($title);

        $view = (new PHUITwoColumnView())
            ->setNavigation($nav)
            ->setFixed(true)
            ->setMainColumn(array(
                $error_view,
                $form_box,
                $status_items,
                $examples,
                $current_config,
            ));

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param AphrontRequest $request
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    private function readRequest(
        PhabricatorConfigOption $option,
        AphrontRequest $request)
    {

        $type = $option->newOptionType();
        if ($type) {
            $is_set = $type->isValuePresentInRequest($option, $request);
            if ($is_set) {
                $value = $type->readValueFromRequest($option, $request);

                $errors = array();
                try {
                    $canonical_value = $type->newValueFromRequestValue(
                        $option,
                        $value);
                    $type->validateStoredValue($option, $canonical_value);
                    $xaction = $type->newTransaction($option, $canonical_value);
                } catch (PhabricatorConfigValidationException $ex) {
                    $errors[] = $ex->getMessage();
                    $xaction = null;
                } catch (Exception $ex) {
                    // NOTE: Some older validators throw bare exceptions. Purely in good
                    // taste, it would be nice to convert these at some point.
                    $errors[] = $ex->getMessage();
                    $xaction = null;
                }

                return array(
                    $errors ? \Yii::t("app", 'Invalid') : null,
                    $errors,
                    $value,
                    $xaction,
                );
            } else {
                $delete_xaction = (new PhabricatorConfigTransaction())
                    ->setTransactionType(PhabricatorConfigTransaction::TYPE_EDIT)
                    ->setNewValue(
                        array(
                            'deleted' => true,
                            'value' => null,
                        ));

                return array(
                    null,
                    array(),
                    null,
                    $delete_xaction,
                );
            }
        }

        // TODO: If we missed on the new `PhabricatorConfigType` map, fall back
        // to the old semi-modular, semi-hacky way of doing things.

        $xaction = new PhabricatorConfigTransaction();
        $xaction->setTransactionType(PhabricatorConfigTransaction::TYPE_EDIT);

        $e_value = null;
        $errors = array();

        if ($option->isCustomType()) {
            $info = $option->getCustomObject()->readRequest($option, $request);
            list($e_value, $errors, $set_value, $value) = $info;
        } else {
            throw new Exception(
                \Yii::t("app",
                    'Unknown configuration option type "{0}".', [
                        $option->getType()
                    ]));
        }

        if (!$errors) {
            $xaction->setNewValue(
                array(
                    'deleted' => false,
                    'value' => $set_value,
                ));
        } else {
            $xaction = null;
        }

        return array($e_value, $errors, $value, $xaction);
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param PhabricatorConfigEntry $entry
     * @param $value
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function getDisplayValue(
        PhabricatorConfigOption $option,
        PhabricatorConfigEntry $entry,
        $value)
    {

        $type = $option->newOptionType();
        if ($type) {
            return $type->newDisplayValue($option, $value);
        }

        if ($option->isCustomType()) {
            return $option->getCustomObject()->getDisplayValue(
                $option,
                $entry,
                $value);
        }

        throw new Exception(
            \Yii::t("app",
                'Unknown configuration option type "{0}".', [
                    $option->getType()
                ]));
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $display_value
     * @param $e_value
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function renderControls(
        PhabricatorConfigOption $option,
        $display_value,
        $e_value)
    {

        $type = $option->newOptionType();
        if ($type) {
            return $type->newControls(
                $option,
                $display_value,
                $e_value);
        }

        if ($option->isCustomType()) {
            $controls = $option
                ->getCustomObject()
                ->renderControls(
                    $option,
                    $display_value,
                    $e_value);
        } else {
            throw new Exception(
                \Yii::t("app",
                    'Unknown configuration option type "{0}".', [
                        $option->getType()
                    ]));
        }

        return $controls;
    }

    /**
     * @param PhabricatorConfigOption $option
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderExamples(PhabricatorConfigOption $option)
    {
        $examples = $option->getExamples();
        if (!$examples) {
            return null;
        }

        $table = array();
        $table[] = JavelinHtml::phutil_tag('tr', array('class' => 'column-labels'), array(
            JavelinHtml::phutil_tag('th', array(), \Yii::t("app", 'Example')),
            JavelinHtml::phutil_tag('th', array(), \Yii::t("app", 'Value')),
        ));
        foreach ($examples as $example) {
            list($value, $description) = $example;

            if ($value === null) {
                $value = JavelinHtml::phutil_tag('em', array(), \Yii::t("app", '(empty)'));
            } else {
                if (is_array($value)) {
                    $value = implode("\n", $value);
                }
            }

            $table[] = JavelinHtml::phutil_tag('tr', array('class' => 'column-labels'), array(
                JavelinHtml::phutil_tag('th', array(), $description),
                JavelinHtml::phutil_tag('td', array(), $value),
            ));
        }

        return JavelinHtml::phutil_tag(
            'table',
            array(
                'class' => 'table config-option-table',
                'cellspacing' => '0',
                'cellpadding' => '0',
            ),
            $table);
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param PhabricatorConfigEntry $entry
     * @return string
     * @throws \yii\base\Exception
     * @throws Exception
     * @author 陈妙威
     */
    private function renderDefaults(
        PhabricatorConfigOption $option,
        PhabricatorConfigEntry $entry)
    {

        $stack = PhabricatorEnv::getConfigSourceStack();
        $stack = $stack->getStack();

        $table = array();
        $table[] = JavelinHtml::phutil_tag('tr', array('class' => 'column-labels'), array(
            JavelinHtml::phutil_tag('th', array(), \Yii::t("app", 'Source')),
            JavelinHtml::phutil_tag('th', array(), \Yii::t("app", 'Value')),
        ));

        $is_effective_value = true;
        foreach ($stack as $key => $source) {
            $row_classes = array(
                'column-labels',
            );

            $value = $source->getKeys(
                array(
                    $option->getKey(),
                ));

            if (!array_key_exists($option->getKey(), $value)) {
                $value = JavelinHtml::phutil_tag('em', array(), \Yii::t("app", '(No Value Configured)'));
            } else {
                $value = $this->getDisplayValue(
                    $option,
                    $entry,
                    $value[$option->getKey()]);

                if ($is_effective_value) {
                    $is_effective_value = false;
                    $row_classes[] = 'config-options-effective-value';
                }
            }

            $table[] = JavelinHtml::phutil_tag(
                'tr',
                array(
                    'class' => implode(' ', $row_classes),
                ),
                array(
                    JavelinHtml::phutil_tag('th', array(), $source->getName()),
                    JavelinHtml::phutil_tag('td', array(), $value),
                ));
        }

        return JavelinHtml::phutil_tag(
            'table',
            array(
                'class' => 'table config-option-table',
                'cellspacing' => '0',
                'cellpadding' => '0',
            ),
            $table);
    }

}
