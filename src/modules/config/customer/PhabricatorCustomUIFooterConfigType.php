<?php

namespace orangins\modules\config\customer;

use orangins\modules\config\option\PhabricatorConfigOption;
use Exception;
use PhutilTypeSpec;
use Yii;

/**
 * Class PhabricatorCustomUIFooterConfigType
 * @package orangins\modules\config\customer
 * @author 陈妙威
 */
final class PhabricatorCustomUIFooterConfigType
    extends PhabricatorConfigJSONOptionType
{

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
                    'Footer configuration is not valid: value must be a list of ' .
                    'items.'));
        }

        foreach ($value as $idx => $item) {
            if (!is_array($item)) {
                throw new Exception(
                    Yii::t("app",
                        'Footer item with index "%s" is invalid: each item must be a ' .
                        'dictionary describing a footer item.',
                        $idx));
            }

            try {
                PhutilTypeSpec::checkMap(
                    $item,
                    array(
                        'name' => 'string',
                        'href' => 'optional string',
                    ));
            } catch (Exception $ex) {
                throw new Exception(
                    Yii::t("app",
                        'Footer item with index "%s" is invalid: %s',
                        $idx,
                        $ex->getMessage()));
            }
        }
    }


}
