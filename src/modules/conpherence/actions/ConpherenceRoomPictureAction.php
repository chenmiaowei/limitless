<?php

namespace orangins\modules\conpherence\actions;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\control\AphrontFormFileControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\PHUIFormLayoutView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\modules\conpherence\models\ConpherenceTransaction;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\transform\PhabricatorFileThumbnailTransform;
use orangins\modules\file\transform\PhabricatorFileTransform;
use orangins\modules\phid\PhabricatorPHIDConstants;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;

final class ConpherenceRoomPictureAction
    extends ConpherenceAction
{

    /**
     * @return Aphront404Response|AphrontRedirectResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \AphrontQueryException
     * @throws \FilesystemException
     * @throws \PhutilAggregateException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\exception\ActiveRecordException
     * @throws \orangins\modules\file\FilesystemException
     * @throws \orangins\modules\file\exception\PhabricatorFileStorageConfigurationException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @throws \yii\db\IntegrityException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');

        $conpherence = ConpherenceThread::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->needProfileImage(true)
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$conpherence) {
            return new Aphront404Response();
        }

        $monogram = $conpherence->getMonogram();

        $supported_formats = PhabricatorFile::getTransformableImageFormats();
        $e_file = true;
        $errors = array();

        if ($request->isFormPost()) {
            $phid = $request->getStr('phid');
            $is_default = false;
            if ($phid == PhabricatorPHIDConstants::PHID_VOID) {
                $phid = null;
                $is_default = true;
            } else if ($phid) {
                $file = PhabricatorFile::find()
                    ->setViewer($viewer)
                    ->withPHIDs(array($phid))
                    ->executeOne();
            } else {
                if ($request->getFileExists('picture')) {
                    $file = PhabricatorFile::newFromPHPUpload(
                        $_FILES['picture'],
                        array(
                            'authorPHID' => $viewer->getPHID(),
                            'canCDN' => true,
                        ));
                } else {
                    $e_file = \Yii::t("app", 'Required');
                    $errors[] = \Yii::t("app",
                        'You must choose a file when uploading a new room picture.');
                }
            }

            if (!$errors && !$is_default) {
                if (!$file->isTransformableImage()) {
                    $e_file = \Yii::t("app", 'Not Supported');
                    $errors[] = \Yii::t("app",
                        'This server only supports these image formats: {0}.', [
                            implode(', ', $supported_formats)
                        ]);
                } else {
                    $xform = PhabricatorFileTransform::getTransformByKey(
                        PhabricatorFileThumbnailTransform::TRANSFORM_PROFILE);
                    $xformed = $xform->executeTransform($file);
                }
            }

            if (!$errors) {
                if ($is_default) {
                    $new_value = null;
                } else {
                    $xformed->attachToObject($conpherence->getPHID());
                    $new_value = $xformed->getPHID();
                }

                $xactions = array();
                $xactions[] = (new ConpherenceTransaction())
                    ->setTransactionType(
                        ConpherenceThreadPictureTransaction::TRANSACTIONTYPE)
                    ->setNewValue($new_value);

                $editor = (new ConpherenceEditor())
                    ->setActor($viewer)
                    ->setContentSourceFromRequest($request)
                    ->setContinueOnMissingFields(true)
                    ->setContinueOnNoEffect(true);

                $editor->applyTransactions($conpherence, $xactions);

                return (new AphrontRedirectResponse())->setURI('/' . $monogram);
            }
        }

        $title = \Yii::t("app", 'Edit Room Picture');

        $form = (new PHUIFormLayoutView())
            ->setUser($viewer);

        $default_image = PhabricatorFile::loadBuiltin('conpherence.png', $viewer);

        $images = array();

        $current = $conpherence->getProfileImagePHID();
        $has_current = false;
        if ($current) {
            $file = PhabricatorFile::find()
                ->setViewer($viewer)
                ->withPHIDs(array($current))
                ->executeOne();
            if ($file) {
                if ($file->isTransformableImage()) {
                    $has_current = true;
                    $images[$current] = array(
                        'uri' => $file->getBestURI(),
                        'tip' => \Yii::t("app", 'Current Picture'),
                    );
                }
            }
        }

        $images[PhabricatorPHIDConstants::PHID_VOID] = array(
            'uri' => $default_image->getBestURI(),
            'tip' => \Yii::t("app", 'Default Picture'),
        );

//    require_celerity_resource('people-profile-css');
        JavelinHtml::initBehavior(new JavelinTooltipAsset());

        $buttons = array();
        foreach ($images as $phid => $spec) {
            $button = javelin_tag(
                'button',
                array(
                    'class' => 'button-grey profile-image-button',
                    'sigil' => 'has-tooltip',
                    'meta' => array(
                        'tip' => $spec['tip'],
                        'size' => 300,
                    ),
                ),
                phutil_tag(
                    'img',
                    array(
                        'height' => 50,
                        'width' => 50,
                        'src' => $spec['uri'],
                    )));

            $button = array(
                phutil_tag(
                    'input',
                    array(
                        'type' => 'hidden',
                        'name' => 'phid',
                        'value' => $phid,
                    )),
                $button,
            );

            $button = phabricator_form(
                $viewer,
                array(
                    'class' => 'profile-image-form',
                    'method' => 'POST',
                ),
                $button);

            $buttons[] = $button;
        }

        if ($has_current) {
            $form->appendChild(
                (new AphrontFormMarkupControl())
                    ->setLabel(\Yii::t("app", 'Current Picture'))
                    ->setValue(array_shift($buttons)));
        }

        $form->appendChild(
            (new AphrontFormMarkupControl())
                ->setLabel(\Yii::t("app", 'Use Picture'))
                ->setValue($buttons));

        $form_box = (new PHUIObjectBoxView())
            ->setHeaderText($title)
            ->setFormErrors($errors)
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setForm($form);

        $upload_form = (new AphrontFormView())
            ->setUser($viewer)
            ->setEncType('multipart/form-data')
            ->appendChild(
                (new AphrontFormFileControl())
                    ->setName('picture')
                    ->setLabel(\Yii::t("app", 'Upload Picture'))
                    ->setError($e_file)
                    ->setCaption(
                        \Yii::t("app", 'Supported formats: {0}', [
                            implode(', ', $supported_formats)
                        ])))
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->addCancelButton('/' . $monogram)
                    ->setValue(\Yii::t("app", 'Upload Picture')));

        $upload_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Upload New Picture'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setForm($upload_form);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($conpherence->getTitle(), '/' . $monogram);
        $crumbs->addTextCrumb(\Yii::t("app", 'Room Picture'));
        $crumbs->setBorder(true);

        $header = (new PHUIHeaderView())
            ->setHeader(\Yii::t("app", 'Edit Room Picture'))
            ->setHeaderIcon('fa-camera');

        $view = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setFooter(array(
                $form_box,
                $upload_box,
            ));

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild(
                array(
                    $view,
                ));

    }
}
