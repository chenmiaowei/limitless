<?php

namespace orangins\modules\config\type;

use orangins\modules\config\option\PhabricatorConfigOption;

/**
 * Class PhabricatorRegexListConfigType
 * @package orangins\modules\config\type
 * @author 陈妙威
 */
final class PhabricatorRegexListConfigType
    extends PhabricatorTextListConfigType
{

    /**
     *
     */
    const TYPEKEY = 'list<regex>';

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @author 陈妙威
     */
    protected function validateStoredItem(
        PhabricatorConfigOption $option,
        $value)
    {

        $ok = @preg_match($value, '');
        if ($ok === false) {
            throw $this->newException(
                \Yii::t("app",
                    'Option "%s" is of type "%s" and must be set to a list of valid ' .
                    'regular expressions, but "%s" is not a valid regular expression.',
                    $option->getKey(),
                    $this->getTypeKey(),
                    $value));
        }
    }
}
