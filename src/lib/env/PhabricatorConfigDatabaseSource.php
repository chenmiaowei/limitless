<?php

namespace orangins\lib\env;

use orangins\lib\helpers\OranginsUtil;
use orangins\modules\config\models\PhabricatorConfigEntry;

/**
 * Class PhabricatorConfigDatabaseSource
 * @package orangins\lib\env
 */
final class PhabricatorConfigDatabaseSource extends PhabricatorConfigProxySource
{

    /**
     * PhabricatorConfigDatabaseSource constructor.
     * @param $namespace
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct($namespace)
    {
        $config = $this->loadConfig($namespace);
        $this->setSource(new PhabricatorConfigDictionarySource($config));
    }

    /**
     * @return bool
     */
    public function isWritable()
    {
        // While this is writable, writes occur through the Config application.
        return false;
    }

    /**
     * @param $namespace
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    private function loadConfig($namespace)
    {
        $objects = PhabricatorConfigEntry::find()->where(['namespace' => $namespace, 'is_deleted' => 0])->all();
        return OranginsUtil::mpull($objects, 'getValue', 'getConfigKey');
    }
}
