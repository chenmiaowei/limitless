<?php

namespace orangins\lib\view\form\control;

use Exception;
use orangins\lib\OranginsObject;
use orangins\lib\request\AphrontRequest;
use orangins\modules\people\models\PhabricatorUser;
use yii\helpers\ArrayHelper;

/**
 * Class AphrontFormDateRangeControlValue
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class AphrontFormDateRangeControlValue extends OranginsObject
{
    /**
     * @var
     */
    public $valueEnabled;
    /**
     * @var AphrontFormDateControlValue
     */
    private $startValue;

    /**
     * @var AphrontFormDateControlValue
     */
    private $endValue;


    /**
     * @return AphrontFormDateControlValue
     */
    public function getStartValue()
    {
        return $this->startValue;
    }

    /**
     * @param AphrontFormDateControlValue $startValue
     * @return self
     */
    public function setStartValue($startValue)
    {
        $this->startValue = $startValue;
        return $this;
    }

    /**
     * @return AphrontFormDateControlValue
     */
    public function getEndValue()
    {
        return $this->endValue;
    }

    /**
     * @param AphrontFormDateControlValue $endValue
     * @return self
     */
    public function setEndValue($endValue)
    {
        $this->endValue = $endValue;
        return $this;
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @author 陈妙威
     * @return AphrontFormDateRangeControlValue
     * @throws \ReflectionException
     */
    public static function newFromRequest(AphrontRequest $request, $key)
    {
        $str = $request->getStr($key . '_d');

        $value = new AphrontFormDateRangeControlValue();
        $explode = explode(" - ", $str);
        $value->setStartValue(AphrontFormDateControlValue::newFromEpoch($request->getViewer(), strtotime($explode[0])));
        $value->setEndValue(AphrontFormDateControlValue::newFromEpoch($request->getViewer(), strtotime($explode[1]) + (24 * 3600 - 1)));
        $value->valueEnabled = $request->getStr($key . '_e');
        return $value;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getDictionary()
    {
        return array(
            'd' => $this->startValue->getValueDate() . " - " . $this->endValue->getValueDate(),
            'e' => $this->valueEnabled,
        );
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $wild
     * @return
     * @throws Exception
     * @author 陈妙威
     */
    public static function newFromWild(PhabricatorUser $viewer, $wild)
    {
        if (is_array($wild)) {
            $value = new AphrontFormDateRangeControlValue();
            $str = ArrayHelper::getValue($wild, 'd');
            $explode = explode(" - ", $str);
            $value->setStartValue(AphrontFormDateControlValue::newFromEpoch($viewer, strtotime($explode[0])));
            $value->setEndValue(AphrontFormDateControlValue::newFromEpoch($viewer, strtotime($explode[1]) + (24 * 3600 - 1)));

            $value->valueEnabled = ArrayHelper::getValue($wild, 'e');
            return $value;
        } else {
            throw new Exception(
                \Yii::t("app",
                    'Unable to construct a date value from value of type "{0}".', [
                        gettype($wild)
                    ]));
        }
    }
}
