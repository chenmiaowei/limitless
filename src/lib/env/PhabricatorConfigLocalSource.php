<?php

namespace orangins\lib\env;

use PhutilJSON;
use PhutilJSONParserException;
use PhutilProxyException;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\file\FilesystemException;
use orangins\modules\file\helpers\FileSystemHelper;

/**
 * Class OranginsConfigLocalSource
 * @package orangins\lib\env
 */
final class PhabricatorConfigLocalSource extends PhabricatorConfigProxySource
{

    /**
     * OranginsConfigLocalSource constructor.
     * @throws PhutilProxyException
     */
    public function __construct()
    {
        $config = $this->loadConfig();
        $this->setSource(new PhabricatorConfigDictionarySource($config));
    }

    /**
     * @param array $keys
     * @return PhabricatorConfigProxySource|void
     * @throws FilesystemException
     * @throws \yii\base\Exception
     */
    public function setKeys(array $keys)
    {
        $result = parent::setKeys($keys);
        $this->saveConfig();
        return $result;
    }

    /**
     * @param array $keys
     * @return PhabricatorConfigProxySource|void
     * @throws FilesystemException
     */
    public function deleteKeys(array $keys)
    {
        $result = parent::deleteKeys($keys);
        $this->saveConfig();
        return parent::deleteKeys($keys);
    }

    /**
     * @return array|mixed
     * @throws PhutilProxyException
     */
    private function loadConfig()
    {
        $path = $this->getConfigPath();

        if (!FilesystemHelper::pathExists($path)) {
            return array();
        }

        try {
            $data = FilesystemHelper::readFile($path);
        } catch (FilesystemException $ex) {
            throw new PhutilProxyException(
                \Yii::t('app',
                    'Configuration file "{0}" exists, but could not be read.',
                    [
                        $path
                    ]),
                $ex);
        }

        try {
            $result = OranginsUtil::phutil_json_decode($data);
        } catch (PhutilJSONParserException $ex) {
            throw new PhutilProxyException(
                \Yii::t('app',
                    'Configuration file "{0}" exists and is readable, but the content ' .
                    'is not valid JSON. You may have edited this file manually and ' .
                    'introduced a syntax error by mistake. Correct the file syntax ' .
                    'to continue.',
                    [
                        $path
                    ]),
                $ex);
        }

        return $result;
    }

    /**
     * @throws FilesystemException
     * @throws \yii\base\Exception
     */
    private function saveConfig()
    {
        $config = $this->getSource()->getAllKeys();
        $json = new PhutilJSON();
        $data = $json->encodeFormatted($config);
        FilesystemHelper::writeFile($this->getConfigPath(), $data);
    }

    /**
     * @return bool|string
     */
    private function getConfigPath()
    {
        $root = dirname(phutil_get_library_root('orangins'));
        $path = $root . '/config/local/local.json';
        return $path;
    }
}
