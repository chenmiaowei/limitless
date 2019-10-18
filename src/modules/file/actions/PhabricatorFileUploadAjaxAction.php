<?php

namespace orangins\modules\file\actions;

use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\PHUIFormFileAjaxControl;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\widgets\form\control\PhabricatorCKEditorControl;

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
        if ($request->isDialogFormPost()) {
            $file_phids = $request->getStrList('filePHIDs');
            if ($file_phids) {
                $files = PhabricatorFile::find()
                    ->setViewer($viewer)
                    ->withPHIDs($file_phids)
                    ->setRaisePolicyExceptions(true)
                    ->execute();
            } else {
                $files = array();
            }

            if ($files) {
                $results = array();
                foreach ($files as $file) {
                    $results[] = $file->getDragAndDropDictionary();
                }

                $content = array(
                    'files' => $results,
                );

                return (new AphrontAjaxResponse())->setContent($content);
            } else {
                $e_file = \Yii::t("app", 'Required');
                $errors[] = \Yii::t("app", 'You must choose a file to upload.');
            }
        }

        $form = (new AphrontFormView())
            ->appendChild(
                (new PHUIFormFileAjaxControl())
                    ->setName('filePHID')
                    ->setLabel(\Yii::t("app", 'Upload File'))
                    ->setAllowMultiple(false)
                    ->setError($e_file))
             ->appendChild(
                (new PHUIFormFileAjaxControl())
                    ->setName('filePHIDs')
                    ->setLabel(\Yii::t("app", 'Upload File'))
                    ->setAllowMultiple(true)
                    ->setError($e_file))
            ->appendChild(
                (new PhabricatorCKEditorControl())
                    ->setName('content')
                    ->setLabel(\Yii::t("app", 'Content')));

        return $this->newDialog()
            ->setTitle(\Yii::t("app", 'File'))
            ->setErrors($errors)
            ->appendForm($form)
            ->addSubmitButton(\Yii::t("app", 'Upload'))
            ->addCancelButton('/');
    }
}
