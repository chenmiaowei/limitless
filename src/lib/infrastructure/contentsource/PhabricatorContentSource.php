<?php

namespace orangins\lib\infrastructure\contentsource;

use orangins\lib\OranginsObject;
use orangins\lib\helpers\OranginsUtil;
use PhutilClassMapQuery;
use orangins\lib\request\AphrontRequest;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorContentSource
 * @package orangins\lib\infrastructure\contentsource
 * @author 陈妙威
 */
abstract class PhabricatorContentSource extends OranginsObject
{

    /**
     * @var
     */
    private $source;
    /**
     * @var array
     */
    private $params = array();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getSourceName();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getSourceDescription();

    /**
     * @return string
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public function getSourceTypeConstant()
    {
        return $this->getPhobjectClassConstant('SOURCECONST', 32);
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public static function getAllContentSources()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getSourceTypeConstant')
            ->execute();
    }

    /**
     * Construct a new content source object.
     *
     * @param string The source type constant to build a source for.
     * @param array Source parameters.
     * @param bool True to suppress errors and force construction of a source
     *   even if the source type is not valid.
     * @return PhabricatorContentSource New source object.
     * @throws Exception
     * @throws \ReflectionException
     */
    final public static function newForSource(
        $source,
        array $params = array(),
        $force = false)
    {

        $map = self::getAllContentSources();
        if (isset($map[$source])) {
            $obj = clone $map[$source];
        } else {
            if ($force) {
                $obj = new PhabricatorUnknownContentSource();
            } else {
                throw new Exception(
                    \Yii::t("app",
                        'Content source type "{0}" is not known to Phabricator!',
                        [
                            $source
                        ]));
            }
        }

        $obj->source = $source;
        $obj->params = $params;

        return $obj;
    }

    /**
     * @param $serialized
     * @return PhabricatorContentSource
     * @author 陈妙威
     * @throws Exception
     * @throws \ReflectionException
     */
    public static function newFromSerialized($serialized)
    {
        $dict = json_decode($serialized, true);
        if (!is_array($dict)) {
            $dict = array();
        }

        $source = ArrayHelper::getValue($dict, 'source');
        $params = ArrayHelper::getValue($dict, 'params');
        if (!is_array($params)) {
            $params = array();
        }

        return self::newForSource($source, $params, true);
    }

    /**
     * @param AphrontRequest $request
     * @return PhabricatorContentSource
     * @author 陈妙威
     * @throws Exception
     * @throws \ReflectionException
     */
    public static function newFromRequest(AphrontRequest $request)
    {
        return self::newForSource(PhabricatorWebContentSource::SOURCECONST);
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    final public function serialize()
    {
        return OranginsUtil::phutil_json_encode(
            array(
                'source' => $this->getSource(),
                'params' => $this->params,
            ));
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getSource()
    {
        return $this->source;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     * @author 陈妙威
     */
    final public function getContentSourceParameter($key, $default = null)
    {
        return ArrayHelper::getValue($this->params, $key, $default);
    }

}
