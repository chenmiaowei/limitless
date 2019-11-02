<?php

namespace orangins\modules\file\keyring;

use Exception;
use orangins\modules\config\customer\PhabricatorConfigJSONOptionType;
use orangins\modules\config\option\PhabricatorConfigOption;
use PhutilNumber;
use PhutilTypeSpec;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorKeyringConfigOptionType
 * @package orangins\modules\file\keyring
 * @author 陈妙威
 */
final class PhabricatorKeyringConfigOptionType
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
                    'Keyring configuration is not valid: value must be a ' .
                    'list of encryption keys.'));
        }

        foreach ($value as $index => $spec) {
            if (!is_array($spec)) {
                throw new Exception(
                    Yii::t("app",
                        'Keyring configuration is not valid: each entry in the list must ' .
                        'be a dictionary describing an encryption key, but the value ' .
                        'with index "%s" is not a dictionary.',
                        $index));
            }
        }


        $map = array();
        $defaults = array();
        foreach ($value as $index => $spec) {
            try {
                PhutilTypeSpec::checkMap(
                    $spec,
                    array(
                        'name' => 'string',
                        'type' => 'string',
                        'material.base64' => 'string',
                        'default' => 'optional bool',
                    ));
            } catch (Exception $ex) {
                throw new Exception(
                    Yii::t("app",
                        'Keyring configuration has an invalid key specification (at ' .
                        'index "%s"): %s.',
                        $index,
                        $ex->getMessage()));
            }

            $name = $spec['name'];
            if (isset($map[$name])) {
                throw new Exception(
                    Yii::t("app",
                        'Keyring configuration is invalid: it describes multiple keys ' .
                        'with the same name ("%s"). Each key must have a unique name.',
                        $name));
            }
            $map[$name] = true;

            if (ArrayHelper::getValue($spec, 'default')) {
                $defaults[] = $name;
            }

            $type = $spec['type'];
            switch ($type) {
                case 'aes-256-cbc':
                    if (!function_exists('openssl_encrypt')) {
                        throw new Exception(
                            Yii::t("app",
                                'Keyring is configured with a "%s" key, but the PHP OpenSSL ' .
                                'extension is not installed. Install the OpenSSL extension ' .
                                'to enable encryption.',
                                $type));
                    }

                    $material = $spec['material.base64'];
                    $material = base64_decode($material, true);
                    if ($material === false) {
                        throw new Exception(
                            Yii::t("app",
                                'Keyring specifies an invalid key ("%s"): key material ' .
                                'should be base64 encoded.',
                                $name));
                    }

                    if (strlen($material) != 32) {
                        throw new Exception(
                            Yii::t("app",
                                'Keyring specifies an invalid key ("%s"): key material ' .
                                'should be 32 bytes (256 bits) but has length %s.',
                                $name,
                                new PhutilNumber(strlen($material))));
                    }
                    break;
                default:
                    throw new Exception(
                        Yii::t("app",
                            'Keyring configuration is invalid: it describes a key with ' .
                            'type "%s", but this type is unknown.',
                            $type));
            }
        }

        if (count($defaults) > 1) {
            throw new Exception(
                Yii::t("app",
                    'Keyring configuration is invalid: it describes multiple default ' .
                    'encryption keys. No more than one key may be the default key. ' .
                    'Keys currently configured as defaults: %s.',
                    implode(', ', $defaults)));
        }
    }

}
