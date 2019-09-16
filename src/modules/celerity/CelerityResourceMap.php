<?php

namespace orangins\modules\celerity;

use orangins\lib\OranginsObject;
use orangins\modules\celerity\resources\CelerityPhysicalResources;
use orangins\modules\celerity\resources\CelerityResources;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Interface to the static resource map, which is a graph of available
 * resources, resource dependencies, and packaging information. You generally do
 * not need to invoke it directly; instead, you call higher-level Celerity APIs
 * and it uses the resource map to satisfy your requests.
 */
final class CelerityResourceMap extends OranginsObject
{

    /**
     * @var array
     */
    private static $instances = array();

    /**
     * @var CelerityResources
     */
    private $resources;
    /**
     * @var
     */
    private $symbolMap;
    /**
     * @var
     */
    private $requiresMap;
    /**
     * @var
     */
    private $packageMap;
    /**
     * @var
     */
    private $nameMap;
    /**
     * @var array|null
     */
    private $hashMap;
    /**
     * @var array
     */
    private $componentMap;

    /**
     * CelerityResourceMap constructor.
     * @param CelerityResources $resources
     */
    public function __construct(CelerityResources $resources)
    {
        $this->resources = $resources;

        $map = $resources->loadMap();
        $this->symbolMap = ArrayHelper::getValue($map, 'symbols', array());
        $this->requiresMap = ArrayHelper::getValue($map, 'requires', array());
        $this->packageMap = ArrayHelper::getValue($map, 'packages', array());
        $this->nameMap = ArrayHelper::getValue($map, 'names', array());

        // We derive these reverse maps at runtime.

        $this->hashMap = array_flip($this->nameMap);
        $this->componentMap = array();
        foreach ($this->packageMap as $package_name => $symbols) {
            foreach ($symbols as $symbol) {
                $this->componentMap[$symbol] = $package_name;
            }
        }
    }

    /**
     * @param $name
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     * @throws \ReflectionException
     */
    public static function getNamedInstance($name)
    {
        if (empty(self::$instances[$name])) {
            $resources_list = CelerityPhysicalResources::getAll();
            if (empty($resources_list[$name])) {
                throw new Exception(
                    \Yii::t("app",
                        'No resource source exists with name "{0}"!',
                        [
                            $name
                        ]));
            }

            $instance = new CelerityResourceMap($resources_list[$name]);
            self::$instances[$name] = $instance;
        }

        return self::$instances[$name];
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getNameMap()
    {
        return $this->nameMap;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSymbolMap()
    {
        return $this->symbolMap;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRequiresMap()
    {
        return $this->requiresMap;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPackageMap()
    {
        return $this->packageMap;
    }

    /**
     * @param array $symbols
     * @return array
     * @author 陈妙威
     * @throws Exception
     */
    public function getPackagedNamesForSymbols(array $symbols)
    {
        $resolved = $this->resolveResources($symbols);
        return $this->packageResources($resolved);
    }

    /**
     * @param array $symbols
     * @return array
     * @author 陈妙威
     * @throws Exception
     */
    private function resolveResources(array $symbols)
    {
        $map = array();
        foreach ($symbols as $symbol) {
            if (!empty($map[$symbol])) {
                continue;
            }
            $this->resolveResource($map, $symbol);
        }

        return $map;
    }

    /**
     * @param array $map
     * @param $symbol
     * @author 陈妙威
     * @throws Exception
     */
    private function resolveResource(array &$map, $symbol)
    {
        if (empty($this->symbolMap[$symbol])) {
            throw new Exception(
                \Yii::t("app",
                    'Attempting to resolve unknown resource, "{0}".', [
                        $symbol
                    ]));
        }

        $hash = $this->symbolMap[$symbol];

        $map[$symbol] = $hash;

        if (isset($this->requiresMap[$hash])) {
            $requires = $this->requiresMap[$hash];
        } else {
            $requires = array();
        }

        foreach ($requires as $required_symbol) {
            if (!empty($map[$required_symbol])) {
                continue;
            }
            $this->resolveResource($map, $required_symbol);
        }
    }

    /**
     * @param array $resolved_map
     * @return array
     * @author 陈妙威
     */
    private function packageResources(array $resolved_map)
    {
        $packaged = array();
        $handled = array();
        foreach ($resolved_map as $symbol => $hash) {
            if (isset($handled[$symbol])) {
                continue;
            }

            if (empty($this->componentMap[$symbol])) {
                $packaged[] = $this->hashMap[$hash];
            } else {
                $package_name = $this->componentMap[$symbol];
                $packaged[] = $package_name;

                $package_symbols = $this->packageMap[$package_name];
                foreach ($package_symbols as $package_symbol) {
                    $handled[$package_symbol] = true;
                }
            }
        }

        return $packaged;
    }

    /**
     * @param $resource_name
     * @return mixed
     * @author 陈妙威
     */
    public function getResourceDataForName($resource_name)
    {
        return $this->resources->getResourceData($resource_name);
    }

    /**
     * @param $package_name
     * @return array|null
     * @author 陈妙威
     */
    public function getResourceNamesForPackageName($package_name)
    {
        $package_symbols = ArrayHelper::getValue($this->packageMap, $package_name);
        if (!$package_symbols) {
            return null;
        }

        $resource_names = array();
        foreach ($package_symbols as $symbol) {
            $resource_names[] = $this->hashMap[$this->symbolMap[$symbol]];
        }

        return $resource_names;
    }


    /**
     * Get the epoch timestamp of the last modification time of a symbol.
     *
     * @param string Resource symbol to lookup.
     * @return int Epoch timestamp of last resource modification.
     */
    public function getModifiedTimeForName($name)
    {
        if ($this->isPackageResource($name)) {
            $names = array();
            foreach ($this->packageMap[$name] as $symbol) {
                $names[] = $this->getResourceNameForSymbol($symbol);
            }
        } else {
            $names = array($name);
        }

        $mtime = 0;
        foreach ($names as $name) {
            $mtime = max($mtime, $this->resources->getResourceModifiedTime($name));
        }

        return $mtime;
    }


    /**
     * Return the absolute URI for the resource associated with a symbol. This
     * method is fairly low-level and ignores packaging.
     *
     * @param string Resource symbol to lookup.
     * @return string|null Resource URI, or null if the symbol is unknown.
     */
    public function getURIForSymbol($symbol)
    {
        $hash = ArrayHelper::getValue($this->symbolMap, $symbol);
        return $this->getURIForHash($hash);
    }


    /**
     * Return the absolute URI for the resource associated with a resource name.
     * This method is fairly low-level and ignores packaging.
     *
     * @param string Resource name to lookup.
     * @return string|null  Resource URI, or null if the name is unknown.
     */
    public function getURIForName($name)
    {
        $hash = ArrayHelper::getValue($this->nameMap, $name);
        return $this->getURIForHash($hash);
    }


    /**
     * @param $name
     * @return mixed
     * @author 陈妙威
     */
    public function getHashForName($name)
    {
        return ArrayHelper::getValue($this->nameMap, $name);
    }


    /**
     * Return the absolute URI for a resource, identified by hash.
     * This method is fairly low-level and ignores packaging.
     *
     * @param string Resource hash to lookup.
     * @return string|null Resource URI, or null if the hash is unknown.
     */
    private function getURIForHash($hash)
    {
        if ($hash === null) {
            return null;
        }
        return $this->resources->getResourceURI($hash, $this->hashMap[$hash]);
    }


    /**
     * Return the resource symbols required by a named resource.
     *
     * @param string Resource name to lookup.
     * @return array<string>|null  List of required symbols, or null if the name
     *                            is unknown.
     */
    public function getRequiredSymbolsForName($name)
    {
        $hash = ArrayHelper::getValue($this->nameMap, $name);
        if ($hash === null) {
            return null;
        }
        return ArrayHelper::getValue($this->requiresMap, $hash, array());
    }


    /**
     * Return the resource name for a given symbol.
     *
     * @param string Resource symbol to lookup.
     * @return string|null Resource name, or null if the symbol is unknown.
     */
    public function getResourceNameForSymbol($symbol)
    {
        $hash = ArrayHelper::getValue($this->symbolMap, $symbol);
        return ArrayHelper::getValue($this->hashMap, $hash);
    }

    /**
     * @param $name
     * @return bool
     * @author 陈妙威
     */
    public function isPackageResource($name)
    {
        return isset($this->packageMap[$name]);
    }

    /**
     * @param $name
     * @return mixed
     * @author 陈妙威
     */
    public function getResourceTypeForName($name)
    {
        return $this->resources->getResourceType($name);
    }

}
