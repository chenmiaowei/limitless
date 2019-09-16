<?php

namespace orangins\modules\search\management;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\cluster\config\PhabricatorClusterSearchConfigType;
use orangins\lib\infrastructure\management\PhabricatorManagementWorkflow;
use PhutilArgumentUsageException;

/**
 * Class PhabricatorSearchManagementWorkflow
 * @package orangins\modules\search\management
 * @author 陈妙威
 */
abstract class PhabricatorSearchManagementWorkflow
    extends PhabricatorManagementWorkflow
{

    /**
     * @throws PhutilArgumentUsageException
     * @author 陈妙威
     */
    protected function validateClusterSearchConfig()
    {
        // Configuration is normally validated by setup self-checks on the web
        // workflow, but users may reasonably run `bin/search` commands after
        // making manual edits to "local.json". Re-verify configuration here before
        // continuing.

        $config_key = 'cluster.search';
        $config_value = PhabricatorEnv::getEnvConfig($config_key);

        try {
            PhabricatorClusterSearchConfigType::validateValue($config_value);
        } catch (Exception $ex) {
            throw new PhutilArgumentUsageException(
                pht(
                    'Setting "%s" is misconfigured: %s',
                    $config_key,
                    $ex->getMessage()));
        }
    }

}
