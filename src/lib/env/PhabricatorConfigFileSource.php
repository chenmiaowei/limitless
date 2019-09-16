<?php

namespace orangins\lib\env;

/**
 * Configuration source which reads from a configuration file on disk (a
 * PHP file in the `config/` directory).
 */
final class PhabricatorConfigFileSource extends PhabricatorConfigProxySource
{

    /**
     * @phutil-external-symbol function orangins_read_config_file
     * @param $config
     * @throws \Exception
     */
    public function __construct($config)
    {
        $root = dirname(phutil_get_library_root('orangins'));
        require_once $root . '/config/__init_conf__.php';

        $dictionary = orangins_read_config_file($config);
        $dictionary['orangins.env'] = $config;

        $this->setSource(new PhabricatorConfigDictionarySource($dictionary));
    }
}
