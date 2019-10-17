<?php

namespace orangins\modules\config\customer;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\control\AphrontFormCheckboxControl;
use orangins\lib\view\form\control\AphrontFormFileControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\config\option\PhabricatorConfigOption;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\policy\constants\PhabricatorPolicies;
use Exception;
use PhutilTypeSpec;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorCustomLogoConfigType
 * @package orangins\modules\config\customer
 * @author 陈妙威
 */
final class PhabricatorCustomLogoConfigType
    extends PhabricatorConfigOptionType
{

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public static function getLogoImagePHID()
    {
        $logo = PhabricatorEnv::getEnvConfig('ui.logo');
        return ArrayHelper::getValue($logo, 'logoImagePHID');
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public static function getLogoWordmark()
    {
        $logo = PhabricatorEnv::getEnvConfig('ui.logo');
        return ArrayHelper::getValue($logo, 'wordmarkText');
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @throws Exception
     * @author 陈妙威
     */
    public function validateOption(PhabricatorConfigOption $option, $value)
    {
        if (!is_array($value)) {
            throw new Exception(
                Yii::t("app",
                    'Logo configuration is not valid: value must be a dictionary.'));
        }

        PhutilTypeSpec::checkMap(
            $value,
            array(
                'logoImagePHID' => 'optional string|null',
                'wordmarkText' => 'optional string|null',
            ));
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param AphrontRequest $request
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function readRequest(
        PhabricatorConfigOption $option,
        AphrontRequest $request)
    {

        $viewer = $request->getViewer();
        $view_policy = PhabricatorPolicies::POLICY_PUBLIC;

        if ($request->getBool('removeLogo')) {
            $logo_image_phid = null;
        } else if ($request->getFileExists('logoImage')) {
            $logo_image = PhabricatorFile::newFromPHPUpload(
                ArrayHelper::getValue($_FILES, 'logoImage'),
                array(
                    'name' => 'logo',
                    'authorPHID' => $viewer->getPHID(),
                    'viewPolicy' => $view_policy,
                    'canCDN' => true,
                    'isExplicitUpload' => true,
                ));
            $logo_image_phid = $logo_image->getPHID();
        } else {
            $logo_image_phid = self::getLogoImagePHID();
        }

        $wordmark_text = $request->getStr('wordmarkText');

        $value = array(
            'logoImagePHID' => $logo_image_phid,
            'wordmarkText' => $wordmark_text,
        );

        $errors = array();
        $e_value = null;

        try {
            $this->validateOption($option, $value);
        } catch (Exception $ex) {
            $e_value = Yii::t("app", 'Invalid');
            $errors[] = $ex->getMessage();
            $value = array();
        }

        return array($e_value, $errors, $value, phutil_json_encode($value));
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $display_value
     * @param $e_value
     * @return array
     * @author 陈妙威
     */
    public function renderControls(
        PhabricatorConfigOption $option,
        $display_value,
        $e_value)
    {

        try {
            $value = phutil_json_decode($display_value);
        } catch (Exception $ex) {
            $value = array();
        }

        $logo_image_phid = ArrayHelper::getValue($value, 'logoImagePHID');
        $wordmark_text = ArrayHelper::getValue($value, 'wordmarkText');

        $controls = array();

        // TODO: This should be a PHUIFormFileControl, but that currently only
        // works in "workflow" forms. It isn't trivial to convert this form into
        // a workflow form, nor is it trivial to make the newer control work
        // in non-workflow forms.
        $controls[] = (new AphrontFormFileControl())
            ->setName('logoImage')
            ->setLabel(Yii::t("app", 'Logo Image'));

        if ($logo_image_phid) {
            $controls[] = (new AphrontFormCheckboxControl())
                ->addCheckbox(
                    'removeLogo',
                    1,
                    Yii::t("app", 'Remove Custom Logo'));
        }

        $controls[] = (new AphrontFormTextControl())
            ->setName('wordmarkText')
            ->setLabel(Yii::t("app", 'Wordmark'))
            ->setPlaceholder(Yii::t("app", 'Phabricator'))
            ->setValue($wordmark_text);

        return $controls;
    }


}
