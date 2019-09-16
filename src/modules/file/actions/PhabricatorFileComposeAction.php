<?php

namespace orangins\modules\file\actions;

use orangins\lib\helpers\JavelinHtml;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;

/**
 * Class PhabricatorFileComposeAction
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorFileComposeAction
    extends PhabricatorFileAction
{

    /**
     * @return \orangins\lib\view\AphrontDialogView
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $color_map = PhabricatorFilesComposeIconBuiltinFile::getAllColors();
        $icon_map = $this->getIconMap();

        if ($request->isFormPost()) {
            $project_phid = $request->getStr('projectPHID');
            if ($project_phid) {
                $project = (new PhabricatorProjectQuery())
                    ->setViewer($viewer)
                    ->withPHIDs(array($project_phid))
                    ->requireCapabilities(
                        array(
                            PhabricatorPolicyCapability::CAN_VIEW,
                            PhabricatorPolicyCapability::CAN_EDIT,
                        ))
                    ->executeOne();
                if (!$project) {
                    return new Aphront404Response();
                }
            }

            $icon = $request->getStr('icon');
            $color = $request->getStr('color');

            $composer = (new PhabricatorFilesComposeIconBuiltinFile())
                ->setIcon($icon)
                ->setColor($color);

            $data = $composer->loadBuiltinFileData();

            $file = PhabricatorFile::newFromFileData(
                $data,
                array(
                    'name' => $composer->getBuiltinDisplayName(),
                    'profile' => true,
                    'canCDN' => true,
                ));

            if ($project_phid) {
                $edit_uri = '/project/manage/' . $project->getID() . '/';

                $xactions = array();
                $xactions[] = (new PhabricatorProjectTransaction())
                    ->setTransactionType(
                        PhabricatorProjectImageTransaction::TRANSACTIONTYPE)
                    ->setNewValue($file->getPHID());

                $editor = (new PhabricatorProjectTransactionEditor())
                    ->setActor($viewer)
                    ->setContentSourceFromRequest($request)
                    ->setContinueOnMissingFields(true)
                    ->setContinueOnNoEffect(true);

                $editor->applyTransactions($project, $xactions);

                return (new AphrontRedirectResponse())->setURI($edit_uri);
            } else {
                $content = array(
                    'phid' => $file->getPHID(),
                );

                return (new AphrontAjaxResponse())->setContent($content);
            }
        }

        $value_color = head_key($color_map);
        $value_icon = head_key($icon_map);

//        require_celerity_resource('people-profile-css');

        $buttons = array();
        foreach ($color_map as $color => $info) {
            $quip = ArrayHelper::getValue($info, 'quip');

            $buttons[] = javelin_tag(
                'button',
                array(
                    'class' => 'button-grey profile-image-button',
                    'sigil' => 'has-tooltip compose-select-color',
                    'style' => 'margin: 0 8px 8px 0',
                    'meta' => array(
                        'color' => $color,
                        'tip' => $quip,
                    ),
                ),
                (new PHUIIconView())
                    ->addClass('compose-background-' . $color));
        }


        $icons = array();
        foreach ($icon_map as $icon => $spec) {
            $quip = ArrayHelper::getValue($spec, 'quip');

            $icons[] = javelin_tag(
                'button',
                array(
                    'class' => 'button-grey profile-image-button',
                    'sigil' => 'has-tooltip compose-select-icon',
                    'style' => 'margin: 0 8px 8px 0',
                    'meta' => array(
                        'icon' => $icon,
                        'tip' => $quip,
                    ),
                ),
                (new PHUIIconView())
                    ->setIcon($icon)
                    ->addClass('compose-icon-bg'));
        }

        $dialog_id = JavelinHtml::celerity_generate_unique_node_id();
        $color_input_id = JavelinHtml::celerity_generate_unique_node_id();
        $icon_input_id = JavelinHtml::celerity_generate_unique_node_id();
        $preview_id = JavelinHtml::celerity_generate_unique_node_id();

        $preview = (new PHUIIconView())
            ->setID($preview_id)
            ->addClass('compose-background-' . $value_color)
            ->setIcon($value_icon)
            ->addClass('compose-icon-bg');

        $color_input = javelin_tag(
            'input',
            array(
                'type' => 'hidden',
                'name' => 'color',
                'value' => $value_color,
                'id' => $color_input_id,
            ));

        $icon_input = javelin_tag(
            'input',
            array(
                'type' => 'hidden',
                'name' => 'icon',
                'value' => $value_icon,
                'id' => $icon_input_id,
            ));

        JavelinHtml::initBehavior(new JavelinTooltipAsset());
        Javelin::initBehavior(
            'icon-composer',
            array(
                'dialogID' => $dialog_id,
                'colorInputID' => $color_input_id,
                'iconInputID' => $icon_input_id,
                'previewID' => $preview_id,
                'defaultColor' => $value_color,
                'defaultIcon' => $value_icon,
            ));

        return $this->newDialog()
            ->setFormID($dialog_id)
            ->setClass('compose-dialog')
            ->setTitle(\Yii::t("app", 'Compose Image'))
            ->appendChild(
                phutil_tag(
                    'div',
                    array(
                        'class' => 'compose-header',
                    ),
                    \Yii::t("app", 'Choose Background Color')))
            ->appendChild($buttons)
            ->appendChild(
                phutil_tag(
                    'div',
                    array(
                        'class' => 'compose-header',
                    ),
                    \Yii::t("app", 'Choose Icon')))
            ->appendChild($icons)
            ->appendChild(
                phutil_tag(
                    'div',
                    array(
                        'class' => 'compose-header',
                    ),
                    \Yii::t("app", 'Preview')))
            ->appendChild($preview)
            ->appendChild($color_input)
            ->appendChild($icon_input)
            ->addCancelButton('/')
            ->addSubmitButton(\Yii::t("app", 'Save Image'));
    }

    /**
     * @return \dict
     * @author 陈妙威
     */
    private function getIconMap()
    {
        $icon_map = PhabricatorFilesComposeIconBuiltinFile::getAllIcons();

        $first = array(
            'fa-briefcase',
            'fa-tags',
            'fa-folder',
            'fa-group',
            'fa-bug',
            'fa-trash-o',
            'fa-calendar',
            'fa-flag-checkered',
            'fa-envelope',
            'fa-truck',
            'fa-lock',
            'fa-umbrella',
            'fa-cloud',
            'fa-building',
            'fa-credit-card',
            'fa-flask',
        );

        $icon_map = array_select_keys($icon_map, $first) + $icon_map;

        return $icon_map;
    }

}
