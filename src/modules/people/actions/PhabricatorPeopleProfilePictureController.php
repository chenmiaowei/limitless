<?php

namespace orangins\modules\people\actions;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormFileControl;
use orangins\lib\view\form\control\AphrontFormMarkupControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\PHUIFormLayoutView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\auth\provider\PhabricatorAuthProvider;
use orangins\modules\file\exception\PhabricatorFileUploadException;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\transform\PhabricatorFileThumbnailTransform;
use orangins\modules\file\transform\PhabricatorFileTransform;
use orangins\modules\people\engine\PhabricatorPeopleProfileMenuEngine;
use orangins\modules\people\models\PhabricatorExternalAccount;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\PhabricatorPHIDConstants;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;
use Exception;
use yii\helpers\Url;

/**
 * Class PhabricatorPeopleProfilePictureController
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleProfilePictureController
    extends PhabricatorPeopleProfileAction
{

    /**
     * @return Aphront404Response|AphrontRedirectResponse|\orangins\lib\view\page\PhabricatorStandardPageView
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
     * @throws \yii\db\IntegrityException*@throws Exception
     * @throws Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $id = $request->getURIData('id');

        $user = PhabricatorUser::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->needProfileImage(true)
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$user) {
            return new Aphront404Response();
        }

        $this->setUser($user);
        $name = $user->getUserName();

        $done_uri = Url::to(['/people/index/view', 'username' => $name]);

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
                    try {
                        $file = PhabricatorFile::newFromPHPUpload(
                            $_FILES['picture'],
                            array(
                                'authorPHID' => $viewer->getPHID(),
                                'canCDN' => true,
                            ));
                    } catch (PhabricatorFileUploadException $e) {
                        $errors[] = $e->getMessage();
                    }
                } else {
                    $e_file = \Yii::t("app", 'Required');
                    $errors[] = \Yii::t("app", 'You must choose a file when uploading a new profile picture.');
                }
            }

            if (!$errors && !$is_default) {
                if (!$file->isTransformableImage()) {
                    $e_file = \Yii::t("app", 'Not Supported');
                    $errors[] = \Yii::t("app",
                        'This server only supports these image formats: {0}.',[
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
                    $user->setProfileImagePHID(null);
                } else {
                    $user->setProfileImagePHID($xformed->getPHID());
                    $xformed->attachToObject($user->getPHID());
                }
                $user->save();
                return (new AphrontRedirectResponse())->setURI($done_uri);
            }
        }

        $title = \Yii::t("app", 'Edit Profile Picture');

        $form = (new PHUIFormLayoutView())
            ->setUser($viewer);

        $default_image = $user->getDefaultProfileImagePHID();
        if ($default_image) {
            $default_image = PhabricatorFile::find()
                ->setViewer($viewer)
                ->withPHIDs(array($default_image))
                ->executeOne();
        }

        if (!$default_image) {
            $default_image = PhabricatorFile::loadBuiltin('profile.png', $viewer);
        }

        $images = array();

        $current = $user->getProfileImagePHID();
        $has_current = false;
        if ($current) {
            $files = PhabricatorFile::find()
                ->setViewer($viewer)
                ->withPHIDs(array($current))
                ->execute();
            if ($files) {
                /** @var PhabricatorFile $file */
                $file = head($files);
                if ($file->isTransformableImage()) {
                    $has_current = true;
                    $images[$current] = array(
                        'uri' => $file->getBestURI(),
                        'tip' => \Yii::t("app", 'Current Picture'),
                    );
                }
            }
        }

        $builtins = array(
            "african.png" => "african.png",
            "bellboy.png" => "bellboy.png",
            "doctor.png" => "doctor.png",
            "hindu.png" => "hindu.png",
            "monk.png" => "monk.png",
            "nun.png" => "nun.png",
            "rapper-1.png" => "rapper-1.png",
            "waitress.png" => "waitress.png",
            "afro.png" => "afro.png",
            "bellgirl.png" => "bellgirl.png",
            "farmer.png" => "farmer.png",
            "hipster.png" => "hipster.png",
            "musician-1.png" => "musician-1.png",
            "nurse-1.png" => "nurse-1.png",
            "rapper.png" => "rapper.png",
            "woman-1.png" => "woman-1.png",
            "asian-1.png" => "asian-1.png",
            "chicken.png" => "chicken.png",
            "firefighter-1.png" => "firefighter-1.png",
            "horse.png" => "horse.png",
            "musician.png" => "musician.png",
            "nurse.png" => "nurse.png",
            "stewardess.png" => "stewardess.png",
            "woman-2.png" => "woman-2.png",
            "asian.png" => "asian.png",
            "cooker-1.png" => "cooker-1.png",
            "firefighter.png" => "firefighter.png",
            "jew.png" => "jew.png",
            "muslim-1.png" => "muslim-1.png",
            "photographer.png" => "photographer.png",
            "surgeon-1.png" => "surgeon-1.png",
            "woman.png" => "woman.png",
            "avatar-1.png" => "avatar-1.png",
            "cooker.png" => "cooker.png",
            "florist-1.png" => "florist-1.png",
            "man-1.png" => "man-1.png",
            "muslim.png" => "muslim.png",
            "pilot.png" => "pilot.png",
            "surgeon.png" => "surgeon.png",
            "avatar-2.png" => "avatar-2.png",
            "diver-1.png" => "diver-1.png",
            "florist.png" => "florist.png",
            "man.png" => "man.png",
            "nerd-1.png" => "nerd-1.png",
            "policeman.png" => "policeman.png",
            "telemarketer-1.png" => "telemarketer-1.png",
            "avatar-3.png" => "avatar-3.png",
            "diver.png" => "diver.png",
            "gentleman.png" => "gentleman.png",
            "mechanic-1.png" => "mechanic-1.png",
            "nerd.png" => "nerd.png",
            "policewoman.png" => "policewoman.png",
            "telemarketer.png" => "telemarketer.png",
            "avatar.png" => "avatar.png",
            "doctor-1.png" => "doctor-1.png",
            "hindu-1.png" => "hindu-1.png",
            "mechanic.png" => "mechanic.png",
            "ninja.png" => "ninja.png",
            "priest.png" => "priest.png",
            "waiter.png" => "waiter.png",
        );
        foreach ($builtins as $builtin) {
            $file = PhabricatorFile::loadBuiltin($builtin, $viewer);
            $images[$file->getPHID()] = array(
                'uri' => $file->getBestURI(),
                'tip' => \Yii::t("app", 'Builtin Image'),
            );
        }

        // Try to add external account images for any associated external accounts.
        /** @var PhabricatorExternalAccount[] $accounts */
        $accounts = PhabricatorExternalAccount::find()
            ->setViewer($viewer)
            ->withUserPHIDs(array($user->getPHID()))
            ->needImages(true)
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->execute();

        foreach ($accounts as $account) {
            $file = $account->getProfileImageFile();
            if ($account->getProfileImagePHID() != $file->getPHID()) {
                // This is a default image, just skip it.
                continue;
            }

            $provider = PhabricatorAuthProvider::getEnabledProviderByKey(
                $account->getProviderKey());
            if ($provider) {
                $tip = \Yii::t("app", 'Picture From %s', $provider->getProviderName());
            } else {
                $tip = \Yii::t("app", 'Picture From External Account');
            }

            if ($file->isTransformableImage()) {
                $images[$file->getPHID()] = array(
                    'uri' => $file->getBestURI(),
                    'tip' => $tip,
                );
            }
        }

        $images[PhabricatorPHIDConstants::PHID_VOID] = array(
            'uri' => $default_image->getBestURI(),
            'tip' => \Yii::t("app", 'Default Picture'),
        );

//        require_celerity_resource('people-profile-css');
        JavelinHtml::initBehavior(new JavelinTooltipAsset(), array());

        $buttons = array();
        foreach ($images as $phid => $spec) {
            $style = null;
            if (isset($spec['style'])) {
                $style = $spec['style'];
            }
            
            $button = JavelinHtml::phutil_tag(
                'button',
                array(
                    'class' => 'button-grey profile-image-button',
                    'sigil' => 'has-tooltip',
                    'meta' => array(
                        'tip' => $spec['tip'],
                        'size' => 300,
                    ),
                ),
                JavelinHtml::phutil_tag(
                    'img',
                    array(
                        'height' => 50,
                        'width' => 50,
                        'src' => $spec['uri'],
                    )));

            $button = array(
                JavelinHtml::phutil_tag(
                    'input',
                    array(
                        'type' => 'hidden',
                        'name' => 'phid',
                        'value' => $phid,
                    )),
                $button,
            );

            $button = JavelinHtml::phabricator_form(
                $viewer,
                array(
                    'class' => 'd-inline-block m-1 profile-image-form',
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
                        \Yii::t("app", 'Supported formats: {0}', [implode(', ', $supported_formats)])))
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->addCancelButton($done_uri)
                    ->setValue(\Yii::t("app", 'Upload Picture')));

        $upload_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Upload New Picture'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setForm($upload_form);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'Edit Profile Picture'));
        $crumbs->setBorder(true);

        $nav = $this->buildNavigation(
            $user,
            PhabricatorPeopleProfileMenuEngine::ITEM_MANAGE);

        $header = $this->buildProfileHeader();

        $view = (new PHUITwoColumnView())
            ->addClass('project-view-home')
            ->addClass('project-view-people-home')
            ->setFooter(array(
                $form_box,
                $upload_box,
            ));

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->setNavigation($nav)
            ->appendChild($view);
    }
}
