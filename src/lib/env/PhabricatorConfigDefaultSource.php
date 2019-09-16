<?php

namespace orangins\lib\env;

use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use orangins\lib\helpers\OranginsUtil;

/**
 * Configuration source which reads from defaults defined in the authoritative
 * configuration definitions.
 */
final class PhabricatorConfigDefaultSource extends PhabricatorConfigProxySource
{

    /**
     * OranginsConfigDefaultSource constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $options = PhabricatorApplicationConfigOptions::loadAllOptions();
        $options = OranginsUtil::mpull($options, 'getDefault');
        $this->setSource(new PhabricatorConfigDictionarySource($options));
    }

    /**
     *
     * @throws \Exception
     */
    public function loadExternalOptions()
    {
        $options = PhabricatorApplicationConfigOptions::loadAllOptions(true);
        $options = OranginsUtil::mpull($options, 'getDefault');
        $this->setKeys($options);
    }
}
