<?php

/**
 * Class PhutilRemarkupBlockInterpreter
 * @author 陈妙威
 */
abstract class PhutilRemarkupBlockInterpreter extends Phobject
{

    /**
     * @var PhutilRemarkupEngine
     */
    private $engine;

    /**
     * @param PhutilRemarkupEngine $engine
     * @return $this
     * @author 陈妙威
     */
    final public function setEngine($engine)
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * @return PhutilRemarkupEngine
     * @author 陈妙威
     */
    final public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @return string
     */
    abstract public function getInterpreterName();

    /**
     * @param $content
     * @param array $argv
     * @return mixed
     * @author 陈妙威
     */
    abstract public function markupContent($content, array $argv);

    /**
     * @param $string
     * @return PhutilSafeHTML|string
     * @throws Exception
     * @author 陈妙威
     */
    protected function markupError($string)
    {
        if ($this->getEngine()->isTextMode()) {
            return '(' . $string . ')';
        } else {
            return phutil_tag(
                'div',
                array(
                    'class' => 'remarkup-interpreter-error',
                ),
                $string);
        }
    }

}
