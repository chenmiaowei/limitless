<?php

namespace orangins\modules\file\actions;

use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\lib\view\form\control\AphrontFormSwitchControl;
use orangins\lib\view\form\control\PHUIFormFileAjaxControl;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\widgets\form\control\PhabricatorCKEditorControl;
use Yii;

/**
 * Class PhabricatorFileUploadDialogAction
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorFileUploadAjaxAction
    extends PhabricatorFileAction
{

    /**
     * @return AphrontAjaxResponse|\orangins\lib\view\AphrontDialogView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $e_file = true;
        $errors = array();
        if ($request->isFormPost()) {
//            $errors[] = "Switch value is: " . ($request->getSwitchValue('switch') ? 'true' : 'false');
        }

        $form = (new AphrontFormView())
            ->appendChild(
                (new AphrontFormSwitchControl())
                    ->setOptions([
                        '否',
                        '是',
                    ])
                    ->setName('switch')
                    ->setValue($request->getSwitchValue('switch'))
                    ->setLabel(Yii::t("app", 'Switch')))
            ->appendChild(
                (new AphrontFormSelectControl())
                    ->setOptions([
                        '否',
                        '是',
                    ])
                    ->setValue($request->getValue('select'))
                    ->setName('select')
                    ->setLabel(Yii::t("app", 'Select')))
            ->appendChild(
                (new PHUIFormFileAjaxControl())
                    ->setValue($request->getStr('filePHID'))
                    ->setName('filePHID')
                    ->setLabel(Yii::t("app", 'Upload File'))
                    ->setAllowMultiple(false)
                    ->setError($e_file))
            ->appendChild(
                (new PHUIFormFileAjaxControl())
                    ->setValue($request->getArr('filePHIDs'))
                    ->setName('filePHIDs')
                    ->setLabel(Yii::t("app", 'Upload File'))
                    ->setAllowMultiple(true)
                    ->setError($e_file))
            ->appendChild(
                (new PhabricatorCKEditorControl())
                    ->setValue($request->getValue('content'))
                    ->setName('content')
                    ->setLabel(Yii::t("app", 'Content')));

        return $this->newDialog()
            ->setTitle(Yii::t("app", 'File'))
            ->setErrors($errors)
            ->appendForm($form)
            ->addSubmitButton(Yii::t("app", 'Upload'))
            ->addCancelButton('/');
    }
}
