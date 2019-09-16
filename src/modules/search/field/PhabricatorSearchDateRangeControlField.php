<?php

namespace orangins\modules\search\field;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\control\AphrontFormDateRangeControl;
use orangins\lib\view\form\control\AphrontFormDateRangeControlValue;

/**
 * Class PhabricatorSearchDateControlField
 * @package orangins\modules\search\field
 * @author 陈妙威
 */
final class PhabricatorSearchDateRangeControlField extends PhabricatorSearchField
{
    private $bool;

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return bool
     * @author 陈妙威
     */
    protected function getValueExistsInRequest(AphrontRequest $request, $key)
    {
        // The control doesn't actually submit a value with the same name as the
        // key, so look for the "_d" value instead, which has the date part of the
        // control value.
        return $request->getExists($key . '_d');
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return mixed
     * @author 陈妙威
     * @throws \ReflectionException
     */
    protected function getValueFromRequest(AphrontRequest $request, $key)
    {
        $value = AphrontFormDateRangeControlValue::newFromRequest($request, $key);
        return $value->getDictionary();
    }

    /**
     * @author 陈妙威
     */
    protected function newControl()
    {
        return (new AphrontFormDateRangeControl())
            ->setAllowNull(true);
    }

    /**
     * @author 陈妙威
     * @param $bool
     * @return PhabricatorSearchDateRangeControlField
     */
    public function setTimeDisabled($bool)
    {
        $this->bool = $bool;
        return $this;
    }

    /**
     * @param $value
     * @return mixed|null
     * @throws \Exception
     * @author 陈妙威
     */
    protected function didReadValueFromSavedQuery($value)
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof AphrontFormDateRangeControlValue) {
            return $value;
        }

        $value = AphrontFormDateRangeControlValue::newFromWild($this->getViewer(), $value);
        return $value;
    }
}
