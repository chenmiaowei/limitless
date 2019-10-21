<?php

namespace orangins\lib\view\form\control;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\modules\file\engine\PhabricatorFileStorageEngine;
use orangins\modules\people\typeahead\PhabricatorPeopleDatasource;
use orangins\modules\settings\setting\PhabricatorMonospacedTextareasSetting;
use orangins\modules\widgets\javelin\JavelinDragAndDropTextareaAsset;
use orangins\modules\widgets\javelin\JavelinRemarkAssistAsset;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;
use PhutilInvalidStateException;
use ReflectionException;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorRemarkupControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class PhabricatorRemarkupControl extends AphrontFormTextAreaControl
{

    /**
     * @var bool
     */
    private $disableMacro = false;
    /**
     * @var bool
     */
    private $disableFullScreen = false;
    /**
     * @var
     */
    private $canPin;
    /**
     * @var bool
     */
    private $sendOnEnter = false;

    /**
     * @param $disable
     * @return $this
     * @author 陈妙威
     */
    public function setDisableMacros($disable)
    {
        $this->disableMacro = $disable;
        return $this;
    }

    /**
     * @param $disable
     * @return $this
     * @author 陈妙威
     */
    public function setDisableFullScreen($disable)
    {
        $this->disableFullScreen = $disable;
        return $this;
    }

    /**
     * @param $can_pin
     * @return $this
     * @author 陈妙威
     */
    public function setCanPin($can_pin)
    {
        $this->canPin = $can_pin;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCanPin()
    {
        return $this->canPin;
    }

    /**
     * @param $soe
     * @return $this
     * @author 陈妙威
     */
    public function setSendOnEnter($soe)
    {
        $this->sendOnEnter = $soe;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getSendOnEnter()
    {
        return $this->sendOnEnter;
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {
        $id = $this->getID();
        if (!$id) {
            $id = JavelinHtml::generateUniqueNodeId();
            $this->setID($id);
        }

        $viewer = $this->getUser();
        if (!$viewer) {
            throw new PhutilInvalidStateException('setUser');
        }

        // We need to have this if previews render images, since Ajax can not
        // currently ship JS or CSS.
//        require_celerity_resource('phui-lightbox-css');

        if (!$this->getDisabled()) {
            JavelinHtml::initBehavior(
                new JavelinDragAndDropTextareaAsset(),
                array(
                    'target' => $id,
                    'activatedClass' => 'aphront-textarea-drag-and-drop',
                    'uri' => '/file/dropupload/',
                    'chunkThreshold' => PhabricatorFileStorageEngine::getChunkThreshold(),
                ));
        }

        $root_id = JavelinHtml::generateUniqueNodeId();

        $user_datasource = new PhabricatorPeopleDatasource();
//        $emoji_datasource = new PhabricatorEmojiDatasource();
//        $proj_datasource = (new PhabricatorProjectDatasource())
//            ->setParameters(
//                array(
//                    'autocomplete' => 1,
//                ));

//        $phriction_datasource = new PhrictionDocumentDatasource();
//        $phurl_datasource = new PhabricatorPhurlURLDatasource();

        JavelinHtml::initBehavior(
            new JavelinRemarkAssistAsset(),
            array(
                'pht' => array(
                    'bold text' => Yii::t("app", 'bold text'),
                    'italic text' => Yii::t("app", 'italic text'),
                    'monospaced text' => Yii::t("app", 'monospaced text'),
                    'List Item' => Yii::t("app", 'List Item'),
                    'Quoted Text' => Yii::t("app", 'Quoted Text'),
                    'data' => Yii::t("app", 'data'),
                    'name' => Yii::t("app", 'name'),
                    'URL' => Yii::t("app", 'URL'),
                    'key-help' => Yii::t("app", 'Pin or unpin the comment form.'),
                ),
                'canPin' => $this->getCanPin(),
                'disabled' => $this->getDisabled(),
                'sendOnEnter' => $this->getSendOnEnter(),
                'rootID' => $root_id,
                'autocompleteMap' => (object)array(
                    64 => array( // "@"
                        'datasourceURI' => $user_datasource->getDatasourceURI(),
                        'headerIcon' => 'fa-user',
                        'headerText' => Yii::t("app", 'Find User:'),
                        'hintText' => $user_datasource->getPlaceholderText(),
                    ),
//                    35 => array( // "#"
//                        'datasourceURI' => $proj_datasource->getDatasourceURI(),
//                        'headerIcon' => 'fa-briefcase',
//                        'headerText' => \Yii::t("app", 'Find Project:'),
//                        'hintText' => $proj_datasource->getPlaceholderText(),
//                    ),
//                    58 => array( // ":"
//                        'datasourceURI' => $emoji_datasource->getDatasourceURI(),
//                        'headerIcon' => 'fa-smile-o',
//                        'headerText' => \Yii::t("app", 'Find Emoji:'),
//                        'hintText' => $emoji_datasource->getPlaceholderText(),
//
//                        // Cancel on emoticons like ":3".
//                        'ignore' => array(
//                            '3',
//                            ')',
//                            '(',
//                            '-',
//                            '/',
//                        ),
//                    ),
//                    91 => array( // "["
//                        'datasourceURI' => $phriction_datasource->getDatasourceURI(),
//                        'headerIcon' => 'fa-book',
//                        'headerText' => \Yii::t("app", 'Find Document:'),
//                        'hintText' => $phriction_datasource->getPlaceholderText(),
//                        'cancel' => array(
//                            ':', // Cancel on "http:" and similar.
//                            '|',
//                            ']',
//                        ),
//                        'prefix' => '^\\[',
//                    ),
//                    40 => array( // "("
//                        'datasourceURI' => $phurl_datasource->getDatasourceURI(),
//                        'headerIcon' => 'fa-compress',
//                        'headerText' => \Yii::t("app", 'Find Phurl:'),
//                        'hintText' => $phurl_datasource->getPlaceholderText(),
//                        'cancel' => array(
//                            ')',
//                        ),
//                        'prefix' => '^\\(',
//                    ),
                ),
            ));
        JavelinHtml::initBehavior(new JavelinTooltipAsset(), array());

        $actions = array(
            'fa-bold' => array(
                'tip' => Yii::t("app", 'Bold'),
                'nodevice' => true,
            ),
            'fa-italic' => array(
                'tip' => Yii::t("app", 'Italics'),
                'nodevice' => true,
            ),
            'fa-text-width' => array(
                'tip' => Yii::t("app", 'Monospaced'),
                'nodevice' => true,
            ),
            'fa-link' => array(
                'tip' => Yii::t("app", 'Link'),
                'nodevice' => true,
            ),
            array(
                'spacer' => true,
                'nodevice' => true,
            ),
            'fa-list-ul' => array(
                'tip' => Yii::t("app", 'Bulleted List'),
                'nodevice' => true,
            ),
            'fa-list-ol' => array(
                'tip' => Yii::t("app", 'Numbered List'),
                'nodevice' => true,
            ),
            'fa-code' => array(
                'tip' => Yii::t("app", 'Code Block'),
                'nodevice' => true,
            ),
            'fa-quote-right' => array(
                'tip' => Yii::t("app", 'Quote'),
                'nodevice' => true,
            ),
            'fa-table' => array(
                'tip' => Yii::t("app", 'Table'),
                'nodevice' => true,
            ),
            'fa-cloud-upload' => array(
                'tip' => Yii::t("app", 'Upload File'),
            ),
        );

//        $can_use_macros =
//            (!$this->disableMacro) &&
//            (function_exists('imagettftext'));
//
//        if ($can_use_macros) {
//            $can_use_macros = PhabricatorApplication::isClassInstalledForViewer(
//                'PhabricatorMacroApplication',
//                $viewer);
//        }
//
//        if ($can_use_macros) {
//            $actions[] = array(
//                'spacer' => true,
//            );
//            $actions['fa-meh-o'] = array(
//                'tip' => \Yii::t("app", 'Meme'),
//            );
//        }

        $actions['fa-eye'] = array(
            'tip' => Yii::t("app", 'Preview'),
            'align' => 'right',
        );

        $actions['fa-book'] = array(
            'tip' => Yii::t("app", 'Help'),
            'align' => 'right',
            'href' => PhabricatorEnv::getDoclink('Remarkup Reference'),
        );

        $mode_actions = array();

        if (!$this->disableFullScreen) {
            $mode_actions['fa-arrows-alt'] = array(
                'tip' => Yii::t("app", 'Fullscreen Mode'),
                'align' => 'right',
            );
        }

        if ($this->getCanPin()) {
            $mode_actions['fa-thumb-tack'] = array(
                'tip' => Yii::t("app", 'Pin Form On Screen'),
                'align' => 'right',
            );
        }

        if ($mode_actions) {
            $actions += $mode_actions;
        }

        $widgetColor = PhabricatorEnv::getEnvConfig("ui.widget-color");
        $buttons = array();
        foreach ($actions as $action => $spec) {

            $classes = array();

            if (ArrayHelper::getValue($spec, 'align') == 'right') {
                $classes[] = 'remarkup-assist-right';
            }

            if (ArrayHelper::getValue($spec, 'nodevice')) {
                $classes[] = 'remarkup-assist-nodevice';
            }

            if (ArrayHelper::getValue($spec, 'spacer')) {
                $classes[] = 'remarkup-assist-separator';
                $buttons[] = JavelinHtml::phutil_tag(
                    'span',
                    array(
                        'class' => implode(' ', $classes),
                    ),
                    '');
                continue;
            } else {
                $classes[] = 'remarkup-assist-button';
            }

            if ($action == 'fa-cloud-upload') {
                $classes[] = 'remarkup-assist-upload';
            }

            $href = ArrayHelper::getValue($spec, 'href', '#');
            if ($href == '#') {
                $meta = array('action' => $action);
                $mustcapture = true;
                $target = null;
            } else {
                $meta = array();
                $mustcapture = null;
                $target = '_blank';
            }

            $content = null;

            $tip = ArrayHelper::getValue($spec, 'tip');
            if ($tip) {
                $meta['tip'] = $tip;
                $content = JavelinHtml::phutil_tag(
                    'span',
                    array(
                        'aural' => true,
                    ),
                    $tip);
            }

            $sigils = array();
            $sigils[] = 'remarkup-assist';
            if (!$this->getDisabled()) {
                $sigils[] = 'has-tooltip';
            }

            $buttons[] = JavelinHtml::phutil_tag(
                'a',
                array(
                    'class' => implode(' ', $classes),
                    'href' => $href,
                    'sigil' => implode(' ', $sigils),
                    'meta' => $meta,
                    'mustcapture' => $mustcapture,
                    'target' => $target,
                    'tabindex' => -1,
                ),
                JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'class' =>
                            "text-{$widgetColor} remarkup-assist phui-icon-view fa bluegrey " . $action,
                    ),
                    $content));
        }

        $buttons = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'remarkup-assist-bar',
            ),
            $buttons);

        $use_monospaced = $viewer->compareUserSetting(
            PhabricatorMonospacedTextareasSetting::SETTINGKEY,
            PhabricatorMonospacedTextareasSetting::VALUE_TEXT_MONOSPACED);

        if ($use_monospaced) {
            $monospaced_textareas_class = 'PhabricatorMonospaced';
        } else {
            $monospaced_textareas_class = null;
        }

        $this->setCustomClass(
            'd-block w-100 remarkup-assist-textarea ' . $monospaced_textareas_class);

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'sigil' => 'remarkup-assist-control',
                'class' => $this->getDisabled() ? 'disabled-control' : null,
                'id' => $root_id,
            ),
            array(
                $buttons,
                parent::renderInput(),
            ));
    }

}
