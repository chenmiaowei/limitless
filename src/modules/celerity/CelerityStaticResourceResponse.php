<?php

namespace orangins\modules\celerity;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\OranginsObject;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\response\AphrontResponse;
use Yii;
use Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\AssetBundle;
use yii\web\View;

/**
 * Tracks and resolves dependencies the page declares with
 * @{function:require_celerity_resource}, and then builds appropriate HTML or
 * Ajax responses.
 */
final class CelerityStaticResourceResponse extends OranginsObject
{

    /**
     * @var array
     */
    private $symbols = array();
    /**
     * @var bool
     */
    private $needsResolve = true;
    /**
     * @var
     */
    private $resolved;
    /**
     * @var
     */
    private $packaged;
    /**
     * @var array
     */
    private $metadata = array();
    /**
     * @var int
     */
    private $metadataBlock = 0;
    /**
     * @var
     */
    private $metadataLocked;
    /**
     * @var array
     */
    private $behaviors = array();
    /**
     * @var array
     */
    private $hasRendered = array();
    /**
     * @var
     */
    private $postprocessorKey;
    /**
     * @var array
     */
    private $contentSecurityPolicyURIs = array();
    /**
     * @var AssetBundle[]
     */
    private $assets = [];

    /**
     * CelerityStaticResourceResponse constructor.
     */
    public function __construct()
    {
        if (isset($_REQUEST['__metablock__'])) {
            $this->metadataBlock = (int)$_REQUEST['__metablock__'];
        }
    }

    /**
     * @param $metadata
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public function addMetadata($metadata)
    {
        if ($this->metadataLocked) {
            throw new Exception(
                \Yii::t("app",
                    'Attempting to add more metadata after metadata has been ' .
                    'locked.'));
        }

        $id = count($this->metadata);
        $this->metadata[$id] = $metadata;
        return $this->metadataBlock . '_' . $id;
    }

    /**
     * @param $kind
     * @param $uri
     * @return $this
     * @author 陈妙威
     */
    public function addContentSecurityPolicyURI($kind, $uri)
    {
        $this->contentSecurityPolicyURIs[$kind][] = $uri;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getContentSecurityPolicyURIMap()
    {
        return $this->contentSecurityPolicyURIs;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getMetadataBlock()
    {
        return $this->metadataBlock;
    }

    /**
     * @param $postprocessor_key
     * @return $this
     * @author 陈妙威
     */
    public function setPostprocessorKey($postprocessor_key)
    {
        $this->postprocessorKey = $postprocessor_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPostprocessorKey()
    {
        return $this->postprocessorKey;
    }

    /**
     * @return AssetBundle[]
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * @param AssetBundle[] $assets
     * @return self
     */
    public function setAssets($assets)
    {
        $this->assets = $assets;
        return $this;
    }

    /**
     * @param AssetBundle $assets
     * @return self
     */
    public function addAsset($assets)
    {
        $this->assets[] = $assets;
        return $this;
    }


    /**
     * Register a behavior for initialization.
     *
     * NOTE: If `$config` is empty, a behavior will execute only once even if it
     * is initialized multiple times. If `$config` is nonempty, the behavior will
     * be invoked once for each configuration.
     * @param $behavior
     * @param array $config
     * @param null $source_name
     * @return CelerityStaticResourceResponse
     * @throws Exception
     * @throws \ReflectionException
     */
    public function initBehavior($behavior, array $config = array(), $source_name = null)
    {
//
//        $this->requireResource('javelin-behavior-' . $behavior, $source_name);

        if (empty($this->behaviors[$behavior])) {
            $this->behaviors[$behavior] = array();
        }

        if ($config) {
            $this->behaviors[$behavior][] = $config;
        }

        return $this;
    }

//    /**
//     * @param $symbol
//     * @param $source_name
//     * @return $this
//     * @throws Exception
//     * @throws \ReflectionException
//     * @author 陈妙威
//     */
//    public function requireResource($symbol, $source_name)
//    {
//        if (isset($this->symbols[$source_name][$symbol])) {
//            return $this;
//        }
//
//        // Verify that the resource exists.
//        $map = CelerityResourceMap::getNamedInstance($source_name);
//        $name = $map->getResourceNameForSymbol($symbol);
//        if ($name === null) {
//            throw new Exception(
//                \Yii::t("app",
//                    'No resource with symbol "%s" exists in source "%s"!',
//                    $symbol,
//                    $source_name));
//        }
//
//        $this->symbols[$source_name][$symbol] = true;
//        $this->needsResolve = true;
//
//        return $this;
//    }

//    /**
    //     * @return $this
    //     * @author 陈妙威
    //     * @throws Exception
    //     * @throws \ReflectionException
    //     */
//    private function resolveResources()
//    {
//        if ($this->needsResolve) {
//            $this->packaged = array();
//            foreach ($this->symbols as $source_name => $symbols_map) {
//                $symbols = array_keys($symbols_map);
//
//                $map = CelerityResourceMap::getNamedInstance($source_name);
//                $packaged = $map->getPackagedNamesForSymbols($symbols);
//
//                $this->packaged[$source_name] = $packaged;
//            }
//            $this->needsResolve = false;
//        }
//        return $this;
//    }
//
//    /**
//     * @param $symbol
//     * @param $source_name
//     * @return array
//     * @author 陈妙威
//     * @throws Exception
//     * @throws \ReflectionException
//     */
//    public function renderSingleResource($symbol, $source_name)
//    {
//        $map = CelerityResourceMap::getNamedInstance($source_name);
//        $packaged = $map->getPackagedNamesForSymbols(array($symbol));
//        return $this->renderPackagedResources($map, $packaged);
//    }
//
//    /**
//     * @param $type
//     * @return mixed
//     * @author 陈妙威
//     * @throws Exception
//     * @throws \ReflectionException
//     */
//    public function renderResourcesOfType($type)
//    {
//        $this->resolveResources();
//
//        $result = array();
//        foreach ($this->packaged as $source_name => $resource_names) {
//            $map = CelerityResourceMap::getNamedInstance($source_name);
//
//            $resources_of_type = array();
//            foreach ($resource_names as $resource_name) {
//                $resource_type = $map->getResourceTypeForName($resource_name);
//                if ($resource_type == $type) {
//                    $resources_of_type[] = $resource_name;
//                }
//            }
//
//            $result[] = $this->renderPackagedResources($map, $resources_of_type);
//        }
//
//        return phutil_implode_html('', $result);
//    }
//
//    /**
//     * @param CelerityResourceMap $map
//     * @param array $resources
//     * @return array
//     * @throws Exception
//     * @author 陈妙威
//     */
//    private function renderPackagedResources(
//        CelerityResourceMap $map,
//        array $resources)
//    {
//
//        $output = array();
//        foreach ($resources as $name) {
//            if (isset($this->hasRendered[$name])) {
//                continue;
//            }
//            $this->hasRendered[$name] = true;
//
//            $output[] = $this->renderResource($map, $name);
//        }
//
//        return $output;
//    }
//
//    /**
//     * @param CelerityResourceMap $map
//     * @param $name
//     * @return mixed
//     * @throws Exception
//     * @author 陈妙威
//     */
//    private function renderResource(
//        CelerityResourceMap $map,
//        $name)
//    {
//
//        $uri = $this->getURI($map, $name);
//        $type = $map->getResourceTypeForName($name);
//
//        $multimeter = MultimeterControl::getInstance();
//        if ($multimeter) {
//            $event_type = MultimeterEvent::TYPE_STATIC_RESOURCE;
//            $multimeter->newEvent($event_type, 'rsrc.' . $name, 1);
//        }
//
//        switch ($type) {
//            case 'css':
//                return JavelinHtml::tag(
//                    'link',
//                    array(
//                        'rel' => 'stylesheet',
//                        'type' => 'text/css',
//                        'href' => $uri,
//                    ));
//            case 'js':
//                return JavelinHtml::tag(
//                    'script',
//                    array(
//                        'type' => 'text/javascript',
//                        'src' => $uri,
//                    ),
//                    '');
//        }
//
//        throw new Exception(
//            \Yii::t("app",
//                'Unable to render resource "%s", which has unknown type "%s".',
//                $name,
//                $type));
//    }

    /**
     * @param View $view
     * @param $is_frameable
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function renderHTMLFooter(View $view, $is_frameable)
    {
        foreach ($this->getAssets() as $asset) {
            $asset::register($view);
        }

        $this->metadataLocked = true;
        $merge_data = array(
            'block' => $this->metadataBlock,
            'data' => $this->metadata,
        );
        $this->metadata = array();

        $behavior_lists = array();
        if ($this->behaviors) {
            $behaviors = $this->behaviors;
            $this->behaviors = array();

            $higher_priority_names = array(
                'refresh-csrf',
                'aphront-basic-tokenizer',
                'dark-console',
                'history-install',
            );

            $higher_priority_behaviors = OranginsUtil::array_select_keys($behaviors, $higher_priority_names);

            foreach ($higher_priority_names as $name) {
                unset($behaviors[$name]);
            }

            $behavior_groups = array(
                $higher_priority_behaviors,
                $behaviors,
            );

            foreach ($behavior_groups as $group) {
                if (!$group) {
                    continue;
                }
                $behavior_lists[] = $group;
            }
        }

        $initializers = array();

        // Even if there is no metadata on the page, Javelin uses the mergeData()
        // call to start dispatching the event queue, so we always want to include
        // this initializer.
        $initializers[] = array(
            'kind' => 'merge',
            'data' => $merge_data,
        );

        foreach ($behavior_lists as $behavior_list) {
            $initializers[] = array(
                'kind' => 'behaviors',
                'data' => $behavior_list,
            );
        }

        if ($is_frameable) {
            $initializers[] = array(
                'data' => 'frameable',
                'kind' => (bool)$is_frameable,
            );
        }

        $tags = array();
        foreach ($initializers as $initializer) {
            $data = $initializer['data'];
            if (is_array($data)) {
                $json_data = AphrontResponse::encodeJSONForHTTPResponse($data);
            } else {
                $json_data = json_encode($data);
            }

            $tags[] = Html::tag('data', '', array(
                'data-javelin-init-kind' => $initializer['kind'],
                'data-javelin-init-data' => $json_data,
            ));
        }
        return implode("\n", $tags);
    }

//    /**
//     * @param $data
//     * @return mixed
//     * @throws Exception
//     * @author 陈妙威
//     */
//    public static function renderInlineScript($data)
//    {
//        if (stripos($data, '</script>') !== false) {
//            throw new Exception(
//                \Yii::t("app",
//                    'Literal %s is not allowed inside inline script.',
//                    '</script>'));
//        }
//        if (strpos($data, '<!') !== false) {
//            throw new Exception(
//                \Yii::t("app",
//                    'Literal %s is not allowed inside inline script.',
//                    '<!'));
//        }
//        // We don't use <![CDATA[ ]]> because it is ignored by HTML parsers. We
//        // would need to send the document with XHTML content type.
//        return JavelinHtml::tag(
//            'script',
//            array('type' => 'text/javascript'),
//            phutil_safe_html($data));
//    }
//
    /**
     * @param $payload
     * @param null $error
     * @return array
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    public function buildAjaxResponse($payload, $error = null)
    {
        $response = array(
            'error' => $error,
            'payload' => $payload,
        );

        if ($this->metadata) {
            $response['javelin_metadata'] = $this->metadata;
            $this->metadata = array();
        }

        if ($this->behaviors) {
            $response['javelin_behaviors'] = $this->behaviors;
            $this->behaviors = array();
        }

        $resources = [];
        foreach ($this->getAssets() as $assetBundle) {
            $resources = ArrayHelper::merge($resources, $this->registerAssetFiles($assetBundle));
        }
        if ($resources) {
            $resources = array_values(array_unique($resources));
            $response['javelin_resources'] = $resources;
        }

//        $this->resolveResources();
//        $resources = array();
//        foreach ($this->packaged as $source_name => $resource_names) {
//            $map = CelerityResourceMap::getNamedInstance($source_name);
//            foreach ($resource_names as $resource_name) {
//                $resources[] = $this->getURI($map, $resource_name);
//            }
//        }
//        if ($resources) {
//            $response['javelin_resources'] = $resources;
//        }

        return $response;
    }


    /**
     * @param AssetBundle $assetBundle
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    public function registerAssetFiles(AssetBundle $assetBundle)
    {
        $view = Yii::$app->getView();
        $manager = $view->getAssetManager();
        $assetBundle->publish($manager);

        $resources = [];
        foreach ($assetBundle->depends as $depend) {
            $resources = ArrayHelper::merge($resources, $this->registerAssetFiles(Yii::createObject($depend)));
        }
        foreach ($assetBundle->js as $js) {
            $resources[] = PhabricatorEnv::getURI($manager->getAssetUrl($assetBundle, $js));
        }
        foreach ($assetBundle->css as $css) {
            $resources[] = PhabricatorEnv::getURI($manager->getAssetUrl($assetBundle, $css));
        }
        return $resources;
    }
//
//    /**
//     * @param CelerityResourceMap $map
//     * @param $name
//     * @param bool $use_primary_domain
//     * @return mixed
//     * @author 陈妙威
//     * @throws Exception
//     */
//    public function getURI(
//        CelerityResourceMap $map,
//        $name,
//        $use_primary_domain = false)
//    {
//
//        $uri = $map->getURIForName($name);
//
//        // If we have a postprocessor selected, add it to the URI.
//        $postprocessor_key = $this->getPostprocessorKey();
//        if ($postprocessor_key) {
//            $uri = preg_replace('@^/res/@', '/res/' . $postprocessor_key . 'X/', $uri);
//        }
//
//        // In developer mode, we dump file modification times into the URI. When a
//        // page is reloaded in the browser, any resources brought in by Ajax calls
//        // do not trigger revalidation, so without this it's very difficult to get
//        // changes to Ajaxed-in CSS to work (you must clear your cache or rerun
//        // the map script). In production, we can assume the map script gets run
//        // after changes, and safely skip this.
//        if (PhabricatorEnv::getEnvConfig('phabricator.developer-mode')) {
//            $mtime = $map->getModifiedTimeForName($name);
//            $uri = preg_replace('@^/res/@', '/res/' . $mtime . 'T/', $uri);
//        }
//
//        if ($use_primary_domain) {
//            return PhabricatorEnv::getURI($uri);
//        } else {
//            return PhabricatorEnv::getCDNURI($uri);
//        }
//    }
}
