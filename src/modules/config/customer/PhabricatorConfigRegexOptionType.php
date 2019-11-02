<?php

namespace orangins\modules\config\customer;

use orangins\modules\config\option\PhabricatorConfigOption;
use Exception;
use Yii;

/**
 * Class PhabricatorConfigRegexOptionType
 * @package orangins\modules\config\customer
 * @author 陈妙威
 */
class PhabricatorConfigRegexOptionType
    extends PhabricatorConfigJSONOptionType
{

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @author 陈妙威
     * @throws Exception
     */
    public function validateOption(PhabricatorConfigOption $option, $value)
    {
        foreach ($value as $pattern => $spec) {
            $ok = preg_match($pattern, '');
            if ($ok === false) {
                throw new Exception(
                    Yii::t("app",
                        'The following regex is malformed and cannot be used: %s',
                        $pattern));
            }
        }
    }

}
