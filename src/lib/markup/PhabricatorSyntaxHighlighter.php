<?php

namespace orangins\lib\markup;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\OranginsObject;

/**
 * Class PhabricatorSyntaxHighlighter
 * @package orangins\lib\markup
 * @author 陈妙威
 */
final class PhabricatorSyntaxHighlighter extends OranginsObject
{

    /**
     * @return object
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public static function newEngine()
    {
        $engine = PhabricatorEnv::newObjectFromConfig('syntax-highlighter.engine');

        $config = array(
            'pygments.enabled' => PhabricatorEnv::getEnvConfig('pygments.enabled'),
            'filename.map' => PhabricatorEnv::getEnvConfig('syntax.filemap'),
        );

        foreach ($config as $key => $value) {
            $engine->setConfig($key, $value);
        }

        return $engine;
    }

    /**
     * @param $filename
     * @param $source
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public static function highlightWithFilename($filename, $source)
    {
        $engine = self::newEngine();
        $language = $engine->getLanguageFromFilename($filename);
        return $engine->highlightSource($language, $source);
    }

    /**
     * @param $language
     * @param $source
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public static function highlightWithLanguage($language, $source)
    {
        $engine = self::newEngine();
        return $engine->highlightSource($language, $source);
    }

}
