<?php

namespace orangins\modules\config\customer;

use orangins\lib\OranginsObject;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\modules\config\json\PhabricatorConfigJSON;
use orangins\modules\config\models\PhabricatorConfigEntry;
use orangins\modules\config\option\PhabricatorConfigOption;

/**
 * Class PhabricatorConfigOptionType
 * @package orangins\modules\config\customer
 * @author 陈妙威
 */
abstract class PhabricatorConfigOptionType extends OranginsObject
{

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @author 陈妙威
     */
    public function validateOption(PhabricatorConfigOption $option, $value)
    {
        return;
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param AphrontRequest $request
     * @return array
     * @author 陈妙威
     */
    public function readRequest(
        PhabricatorConfigOption $option,
        AphrontRequest $request)
    {

        $e_value = null;
        $errors = array();
        $storage_value = $request->getStr('value');
        $display_value = $request->getStr('value');

        return array($e_value, $errors, $storage_value, $display_value);
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param PhabricatorConfigEntry $entry
     * @param $value
     * @return string
     * @author 陈妙威
     */
    public function getDisplayValue(
        PhabricatorConfigOption $option,
        PhabricatorConfigEntry $entry,
        $value)
    {

        if (is_array($value)) {
            return PhabricatorConfigJSON::prettyPrintJSON($value);
        } else {
            return $value;
        }

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

        $control = $this->renderControl($option, $display_value, $e_value);

        return array($control);
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $display_value
     * @param $e_value
     * @return AphrontFormTextControl
     * @author 陈妙威
     */
    public function renderControl(
        PhabricatorConfigOption $option,
        $display_value,
        $e_value)
    {

        return (new AphrontFormTextControl())
            ->setName('value')
            ->setLabel(\Yii::t("app",'Value'))
            ->setValue($display_value)
            ->setError($e_value);
    }
}
