<?php

namespace orangins\modules\file\actions;

use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\PHUIFormFileControl;
use orangins\modules\file\models\PhabricatorFile;

/**
 * Class PhabricatorFileUploadDialogAction
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorFileUploadDialogAction
    extends PhabricatorFileAction
{

    /**
     * @return \orangins\lib\view\AphrontDialogView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
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
                $e_file = \Yii::t("app",'Required');
                $errors[] = \Yii::t("app",'You must choose a file to upload.');
            }
        }

        if ($request->getURIData('single')) {
            $allow_multiple = false;
        } else {
            $allow_multiple = true;
        }

        $form = (new AphrontFormView())
            ->appendChild(
                (new PHUIFormFileControl())
                    ->setName('filePHIDs')
                    ->setLabel(\Yii::t("app",'Upload File'))
                    ->setAllowMultiple($allow_multiple)
                    ->setError($e_file));

        return $this->newDialog()
            ->setTitle(\Yii::t("app",'File'))
            ->setErrors($errors)
            ->appendForm($form)
            ->addSubmitButton(\Yii::t("app",'Upload'))
            ->addCancelButton('/');
    }

}
