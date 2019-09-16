<?php

namespace orangins\modules\file\actions;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormFileControl;
use orangins\lib\view\form\control\AphrontFormMarkupControl;
use orangins\lib\view\form\control\AphrontFormPolicyControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\view\PhabricatorGlobalUploadTargetView;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\models\PhabricatorPolicy;
use orangins\modules\policy\models\PhabricatorPolicyQuery;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorFileUploadAction
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorFileUploadAction extends PhabricatorFileAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isGlobalDragAndDropUploadEnabled()
    {
        return true;
    }

    /**
     * @return AphrontRedirectResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \AphrontQueryException
     * @throws \FilesystemException
     * @throws \PhutilAggregateException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\exception\ActiveRecordException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \orangins\modules\file\exception\PhabricatorFileStorageConfigurationException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\IntegrityException*@throws \PhutilMethodNotImplementedException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $file = PhabricatorFile::initializeNewFile();

        $e_file = true;
        $errors = array();
        if ($request->isFormPost()) {
            $view_policy = $request->getStr('viewPolicy');

            if (!$request->getFileExists('file')) {
                $e_file = \Yii::t("app",'Required');
                $errors[] = \Yii::t("app",'You must select a file to upload.');
            } else {
                $file = PhabricatorFile::newFromPHPUpload(
                    ArrayHelper::getValue($_FILES, 'file'),
                    array(
                        'name' => $request->getStr('name'),
                        'authorPHID' => $viewer->getPHID(),
                        'viewPolicy' => $view_policy,
                        'isExplicitUpload' => true,
                    ));
            }

            if (!$errors) {
                return (new AphrontRedirectResponse())->setURI($file->getInfoURI());
            }

            $file->setViewPolicy($view_policy);
        }

        $support_id = JavelinHtml::generateUniqueNodeId();
        $instructions = (new AphrontFormMarkupControl())
            ->setControlID($support_id)
            ->setControlStyle('display: none')
            ->setValue(hsprintf(
                '<br /><br /><strong>%s</strong> %s<br /><br />',
                \Yii::t("app",'Drag and Drop:'),
                \Yii::t("app",
                    'You can also upload files by dragging and dropping them from your ' .
                    'desktop onto this page or the Phabricator home page.')));

        $policies = PhabricatorPolicy::find()
            ->setViewer($viewer)
            ->setObject($file)
            ->execute();

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->setEncType('multipart/form-data')
            ->appendChild(
                (new AphrontFormFileControl())
                    ->setLabel(\Yii::t("app",'File'))
                    ->setName('file')
                    ->setError($e_file))
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app",'Name'))
                    ->setName('name')
                    ->setValue($request->getStr('name')))
            ->appendChild(
                (new AphrontFormPolicyControl())
                    ->setUser($viewer)
                    ->setCapability(PhabricatorPolicyCapability::CAN_VIEW)
                    ->setPolicyObject($file)
                    ->setPolicies($policies)
                    ->setName('viewPolicy'))
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->setValue(\Yii::t("app",'Upload'))
                    ->addCancelButton('/file/'))
            ->appendChild($instructions);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app",'Upload'), $request->getRequestURI());
        $crumbs->setBorder(true);

        $title = \Yii::t("app",'Upload File');

        $global_upload = (new PhabricatorGlobalUploadTargetView())
            ->setUser($viewer)
            ->setShowIfSupportedID($support_id);

        $form_box = (new PHUIObjectBoxView())
            ->setHeaderText($title)
            ->setFormErrors($errors)
            ->setBackground(PHUIObjectBoxView::WHITE_CONFIG)
            ->setForm($form);

        $view = (new PHUITwoColumnView())
            ->setFooter(array(
                $form_box,
                $global_upload,
            ));

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

}
