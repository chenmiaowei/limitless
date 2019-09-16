<?php

namespace orangins\lib\markup;

use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\lib\OranginsObject;
use PhutilMarkupEngine;
use PhutilRemarkupEngine;
use PhutilRemarkupHeaderBlockRule;

/**
 * DEPRECATED. Use @{class:PHUIRemarkupView}.
 */
final class PhabricatorMarkupOneOff
    extends OranginsObject
    implements PhabricatorMarkupInterface
{

    /**
     * @var
     */
    private $content;
    /**
     * @var
     */
    private $preserveLinebreaks;
    /**
     * @var
     */
    private $engineRuleset;
    /**
     * @var
     */
    private $engine;
    /**
     * @var
     */
    private $disableCache;
    /**
     * @var
     */
    private $contentCacheFragment;

    /**
     * @var
     */
    private $generateTableOfContents;
    /**
     * @var
     */
    private $tableOfContents;

    /**
     * @param $engine_ruleset
     * @return $this
     * @author 陈妙威
     */
    public function setEngineRuleset($engine_ruleset)
    {
        $this->engineRuleset = $engine_ruleset;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEngineRuleset()
    {
        return $this->engineRuleset;
    }

    /**
     * @param $preserve_linebreaks
     * @return $this
     * @author 陈妙威
     */
    public function setPreserveLinebreaks($preserve_linebreaks)
    {
        $this->preserveLinebreaks = $preserve_linebreaks;
        return $this;
    }

    /**
     * @param $content
     * @return $this
     * @author 陈妙威
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param PhutilMarkupEngine $engine
     * @return $this
     * @author 陈妙威
     */
    public function setEngine(PhutilMarkupEngine $engine)
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @param $disable_cache
     * @return $this
     * @author 陈妙威
     */
    public function setDisableCache($disable_cache)
    {
        $this->disableCache = $disable_cache;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDisableCache()
    {
        return $this->disableCache;
    }

    /**
     * @param $generate
     * @return $this
     * @author 陈妙威
     */
    public function setGenerateTableOfContents($generate)
    {
        $this->generateTableOfContents = $generate;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getGenerateTableOfContents()
    {
        return $this->generateTableOfContents;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTableOfContents()
    {
        return $this->tableOfContents;
    }

    /**
     * @param $fragment
     * @return $this
     * @author 陈妙威
     */
    public function setContentCacheFragment($fragment)
    {
        $this->contentCacheFragment = $fragment;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getContentCacheFragment()
    {
        return $this->contentCacheFragment;
    }

    /**
     * @param $field
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMarkupFieldKey($field)
    {
        $fragment = $this->getContentCacheFragment();
        if ($fragment !== null) {
            return $fragment;
        }

        return PhabricatorHash::digestForIndex($this->getContent()) . ':oneoff';
    }

    /**
     * @param $field
     * @return null|PhutilRemarkupEngine
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function newMarkupEngine($field)
    {
        if ($this->engine) {
            return $this->engine;
        }

        if ($this->engineRuleset) {
            return PhabricatorMarkupEngine::getEngine($this->engineRuleset);
        } else if ($this->preserveLinebreaks) {
            return PhabricatorMarkupEngine::getEngine();
        } else {
            return PhabricatorMarkupEngine::getEngine('nolinebreaks');
        }
    }

    /**
     * @param $field
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMarkupText($field)
    {
        return $this->getContent();
    }

    /**
     * @param $field
     * @param $output
     * @param PhutilMarkupEngine $engine
     * @return \PhutilSafeHTML|string
     * @throws \Exception
     * @author 陈妙威
     */
    public function didMarkupText(
        $field,
        $output,
        PhutilMarkupEngine $engine)
    {

        if ($this->getGenerateTableOfContents()) {
            $toc = PhutilRemarkupHeaderBlockRule::renderTableOfContents($engine);
            $this->tableOfContents = $toc;
        }

//        require_celerity_resource('phabricator-remarkup-css');
        return phutil_tag(
            'div',
            array(
                'class' => 'phabricator-remarkup',
            ),
            $output);
    }

    /**
     * @param $field
     * @return bool
     * @author 陈妙威
     */
    public function shouldUseMarkupCache($field)
    {
        if ($this->getDisableCache()) {
            return false;
        }

        return true;
    }

}
