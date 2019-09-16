<?php

namespace orangins\modules\search\field;

use orangins\lib\request\AphrontRequest;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\conduit\parametertype\ConduitEpochParameterType;
use orangins\lib\view\form\control\AphrontFormTextControl;

/**
 * Class PhabricatorSearchDateField
 * @package orangins\modules\search\field
 * @author 陈妙威
 */
final class PhabricatorSearchDateField
    extends PhabricatorSearchField
{

    /**
     * @return
     * @author 陈妙威
     */
    protected function newControl()
    {
        return (new AphrontFormTextControl())
            ->setPlaceholder(\Yii::t("app",'"2022-12-25" or "7 days ago"...'));
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return mixed|null|string
     * @author 陈妙威
     */
    protected function getValueFromRequest(AphrontRequest $request, $key)
    {
        return $request->getStr($key);
    }

    /**
     * @param $value
     * @return int|mixed|null
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getValueForQuery($value)
    {
        return $this->parseDateTime($value);
    }

    /**
     * @param $value
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function validateControlValue($value)
    {
        if (!strlen($value)) {
            return;
        }

        $epoch = $this->parseDateTime($value);
        if ($epoch) {
            return;
        }

        $this->addError(
            \Yii::t("app",'Invalid'),
            \Yii::t("app",'Date value for "%s" can not be parsed.', $this->getLabel()));
    }

    /**
     * @param $value
     * @return int|null
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function parseDateTime($value)
    {
        if (!strlen($value)) {
            return null;
        }

        // If this appears to be an epoch timestamp, just return it unmodified.
        // This assumes values like "2016" or "20160101" are "Ymd".
        if (is_int($value) || ctype_digit($value)) {
            if ((int)$value > 30000000) {
                return (int)$value;
            }
        }

        return PhabricatorTime::parseLocalTime($value, $this->getViewer());
    }

    /**
     * @return ConduitEpochParameterType|null
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitEpochParameterType();
    }

}
