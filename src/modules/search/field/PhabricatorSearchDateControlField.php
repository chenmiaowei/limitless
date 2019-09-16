<?php

namespace orangins\modules\search\field;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\control\AphrontFormDateControl;
use orangins\lib\view\form\control\AphrontFormDateControlValue;

/**
 * Class PhabricatorSearchDateControlField
 * @package orangins\modules\search\field
 * @author 陈妙威
 */
final class PhabricatorSearchDateControlField extends PhabricatorSearchField
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
     */
    protected function getValueFromRequest(AphrontRequest $request, $key)
    {
        $value = AphrontFormDateControlValue::newFromRequest($request, $key);
        $value->setOptional(true);
        return $value->getDictionary();
    }

    /**
     * @author 陈妙威
     */
    protected function newControl()
    {
        return (new AphrontFormDateControl())
            ->setIsTimeDisabled($this->bool)
            ->setAllowNull(true);
    }

    /**
     * @author 陈妙威
     * @param $bool
     * @return PhabricatorSearchDateControlField
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

        if ($value instanceof AphrontFormDateControlValue && $value->getEpoch()) {
            return $value->setOptional(true);
        }

        $value = AphrontFormDateControlValue::newFromWild(
            $this->getViewer(),
            $value);
        return $value->setOptional(true);
    }

}
