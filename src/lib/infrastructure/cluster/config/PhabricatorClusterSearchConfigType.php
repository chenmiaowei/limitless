<?php

namespace orangins\lib\infrastructure\cluster\config;

use Exception;
use orangins\lib\infrastructure\cluster\search\PhabricatorSearchService;
use orangins\modules\config\option\PhabricatorConfigOption;
use orangins\modules\config\type\PhabricatorJSONConfigType;
use PhutilTypeSpec;

/**
 * Class PhabricatorClusterSearchConfigType
 * @package orangins\lib\infrastructure\cluster\config
 * @author 陈妙威
 */
final class PhabricatorClusterSearchConfigType
    extends PhabricatorJSONConfigType
{

    /**
     *
     */
    const TYPEKEY = 'cluster.search';

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @return mixed|void
     * @throws Exception
     * @author 陈妙威
     */
    public function validateStoredValue(
        PhabricatorConfigOption $option,
        $value)
    {
        self::validateValue($value);
    }

    /**
     * @param $value
     * @throws Exception
     * @author 陈妙威
     */
    public static function validateValue($value)
    {
        $engines = PhabricatorSearchService::loadAllFulltextStorageEngines();

        foreach ($value as $index => $spec) {
            if (!is_array($spec)) {
                throw new Exception(
                    pht(
                        'Search cluster configuration is not valid: each entry in the ' .
                        'list must be a dictionary describing a search service, but ' .
                        'the value with index "%s" is not a dictionary.',
                        $index));
            }

            try {
                PhutilTypeSpec::checkMap(
                    $spec,
                    array(
                        'type' => 'string',
                        'hosts' => 'optional list<map<string, wild>>',
                        'roles' => 'optional map<string, wild>',
                        'port' => 'optional int',
                        'protocol' => 'optional string',
                        'path' => 'optional string',
                        'version' => 'optional int',
                    ));
            } catch (Exception $ex) {
                throw new Exception(
                    pht(
                        'Search engine configuration has an invalid service ' .
                        'specification (at index "%s"): %s.',
                        $index,
                        $ex->getMessage()));
            }

            if (!array_key_exists($spec['type'], $engines)) {
                throw new Exception(
                    pht(
                        'Invalid search engine type: %s. Valid types are: %s.',
                        $spec['type'],
                        implode(', ', array_keys($engines))));
            }

            if (isset($spec['hosts'])) {
                foreach ($spec['hosts'] as $hostindex => $host) {
                    try {
                        PhutilTypeSpec::checkMap(
                            $host,
                            array(
                                'host' => 'string',
                                'roles' => 'optional map<string, wild>',
                                'port' => 'optional int',
                                'protocol' => 'optional string',
                                'path' => 'optional string',
                                'version' => 'optional int',
                            ));
                    } catch (Exception $ex) {
                        throw new Exception(
                            pht(
                                'Search cluster configuration has an invalid host ' .
                                'specification (at index "%s"): %s.',
                                $hostindex,
                                $ex->getMessage()));
                    }
                }
            }
        }
    }
}
