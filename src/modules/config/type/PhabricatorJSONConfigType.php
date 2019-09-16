<?php

namespace orangins\modules\config\type;

use orangins\lib\view\form\control\AphrontFormTextAreaControl;
use orangins\modules\config\json\PhabricatorConfigJSON;
use orangins\modules\config\option\PhabricatorConfigOption;
use Exception;

/**
 * Class PhabricatorJSONConfigType
 * @package orangins\modules\config\type
 * @author 陈妙威
 */
abstract class PhabricatorJSONConfigType
    extends PhabricatorTextConfigType
{

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed|string
     * @author 陈妙威
     */
    protected function newCanonicalValue(
        PhabricatorConfigOption $option,
        $value)
    {

        try {
            $value = phutil_json_decode($value);
        } catch (Exception $ex) {
            throw $this->newException(
                \Yii::t("app",
                    'Value for option "%s" (of type "%s") must be specified in JSON, ' .
                    'but input could not be decoded: %s',
                    $option->getKey(),
                    $this->getTypeKey(),
                    $ex->getMessage()));
        }

        return $value;
    }

    /**
     * @param PhabricatorConfigOption $option
     * @return \orangins\lib\view\form\control\AphrontFormTextControl
     * @author 陈妙威
     */
    protected function newControl(PhabricatorConfigOption $option)
    {
        return (new AphrontFormTextAreaControl())
            ->setHeight(AphrontFormTextAreaControl::HEIGHT_VERY_TALL)
            ->setCustomClass('PhabricatorMonospaced')
            ->setCaption(\Yii::t("app",'Enter value in JSON.'));
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return string
     * @author 陈妙威
     */
    public function newDisplayValue(
        PhabricatorConfigOption $option,
        $value)
    {
        return PhabricatorConfigJSON::prettyPrintJSON($value);
    }

}
