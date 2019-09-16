<?php

namespace orangins\modules\config\customer;

use orangins\lib\helpers\OranginsUtil;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\control\AphrontFormTextAreaControl;
use orangins\modules\config\option\PhabricatorConfigOption;
use Exception;

/**
 * Class PhabricatorConfigJSONOptionType
 * @package orangins\modules\config\customer
 * @author 陈妙威
 */
abstract class PhabricatorConfigJSONOptionType
    extends PhabricatorConfigOptionType
{

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

        if (strlen($display_value)) {
            try {
                $storage_value = phutil_json_decode($display_value);
                $this->validateOption($option, $storage_value);
            } catch (Exception $ex) {
                $e_value = \Yii::t("app",'Invalid');
                $errors[] = $ex->getMessage();
            }
        } else {
            $storage_value = null;
        }

        return array($e_value, $errors, $storage_value, $display_value);
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $display_value
     * @param $e_value
     * @return AphrontFormTextAreaControl|\orangins\lib\view\form\control\AphrontFormTextControl
     * @author 陈妙威
     */
    public function renderControl(
        PhabricatorConfigOption $option,
        $display_value,
        $e_value)
    {

        return (new AphrontFormTextAreaControl())
            ->setHeight(AphrontFormTextAreaControl::HEIGHT_VERY_TALL)
            ->setName('value')
            ->setLabel(\Yii::t("app",'Value'))
            ->setValue($display_value)
            ->setError($e_value);
    }
}
