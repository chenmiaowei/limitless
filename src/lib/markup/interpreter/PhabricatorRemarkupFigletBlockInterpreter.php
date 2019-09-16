<?php

namespace orangins\lib\markup\interpreter;

use Filesystem;
use PhutilRemarkupBlockInterpreter;
use Text_Figlet;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorRemarkupFigletBlockInterpreter
 * @package orangins\lib\markup\interpreter
 * @author 陈妙威
 */
final class PhabricatorRemarkupFigletBlockInterpreter
    extends PhutilRemarkupBlockInterpreter
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getInterpreterName()
    {
        return 'figlet';
    }

    /**
     * @phutil-external-symbol class Text_Figlet
     * @throws \FilesystemException
     * @throws \Exception
     */
    public function markupContent($content, array $argv)
    {
        $map = self::getFigletMap();

        $font = ArrayHelper::getValue($argv, 'font');
        $font = phutil_utf8_strtolower($font);
        if (empty($map[$font])) {
            $font = 'standard';
        }

        $root = phutil_get_library_root('orangins');
        require_once $root . '/../externals/pear-figlet/Text/Figlet.php';

        $figlet = new Text_Figlet();
        $figlet->loadFont($map[$font]);

        $result = $figlet->lineEcho($content);

        $engine = $this->getEngine();

        if ($engine->isTextMode()) {
            return $result;
        }

        if ($engine->isHTMLMailMode()) {
            return phutil_tag('pre', array(), $result);
        }

        return phutil_tag(
            'div',
            array(
                'class' => 'PhabricatorMonospaced remarkup-figlet',
            ),
            $result);
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws \FilesystemException
     */
    private static function getFigletMap()
    {
        $root = phutil_get_library_root('orangins');

        $dirs = array(
            $root . '/../externals/figlet/fonts/',
            $root . '/../externals/pear-figlet/fonts/',
            dirname($root) . '/resources/figlet/custom/',
        );

        $map = array();
        foreach ($dirs as $dir) {
            foreach (Filesystem::listDirectory($dir, false) as $file) {
                if (preg_match('/\.flf\z/', $file)) {
                    $name = phutil_utf8_strtolower($file);
                    $name = preg_replace('/\.flf\z/', '', $name);
                    $map[$name] = $dir . $file;
                }
            }
        }

        return $map;
    }

}
