<?php

namespace orangins\lib\markup;

use AphrontWriteGuard;
use orangins\lib\db\ActiveRecord;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\lib\markup\rule\PhabricatorKeyboardRemarkupRule;
use orangins\lib\markup\rule\PhabricatorNavigationRemarkupRule;
use orangins\lib\markup\rule\PhabricatorRemarkupCustomBlockRule;
use orangins\lib\markup\rule\PhabricatorRemarkupCustomInlineRule;
use orangins\lib\markup\rule\PhabricatorYoutubeRemarkupRule;
use orangins\lib\OranginsObject;
use orangins\lib\PhabricatorApplication;
use orangins\lib\syntax\PhabricatorDefaultSyntaxStyle;
use orangins\modules\cache\models\PhabricatorMarkupCache;
use orangins\modules\markup\menuitem\PhabricatorMentionRemarkupRule;
use orangins\modules\people\models\PhabricatorUser;
use PhutilClassMapQuery;
use PhutilInvalidStateException;
use PhutilRemarkupBoldRule;
use PhutilRemarkupCodeBlockRule;
use PhutilRemarkupDefaultBlockRule;
use PhutilRemarkupDelRule;
use PhutilRemarkupDocumentLinkRule;
use PhutilRemarkupEngine;
use PhutilRemarkupEscapeRemarkupRule;
use PhutilRemarkupHeaderBlockRule;
use PhutilRemarkupHighlightRule;
use PhutilRemarkupHorizontalRuleBlockRule;
use PhutilRemarkupHyperlinkRule;
use PhutilRemarkupInterpreterBlockRule;
use PhutilRemarkupItalicRule;
use PhutilRemarkupListBlockRule;
use PhutilRemarkupLiteralBlockRule;
use PhutilRemarkupMonospaceRule;
use PhutilRemarkupNoteBlockRule;
use PhutilRemarkupQuotesBlockRule;
use PhutilRemarkupReplyBlockRule;
use PhutilRemarkupSimpleTableBlockRule;
use PhutilRemarkupTableBlockRule;
use PhutilRemarkupUnderlineRule;
use PhutilUTF8StringTruncator;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Manages markup engine selection, configuration, application, caching and
 * pipelining.
 *
 * @{class:PhabricatorMarkupEngine} can be used to render objects which
 * implement @{interface:PhabricatorMarkupInterface} in a batched, cache-aware
 * way. For example, if you have a list of comments written in remarkup (and
 * the objects implement the correct interface) you can render them by first
 * building an engine and adding the fields with @{method:addObject}.
 *
 *   $field  = 'field:body'; // Field you want to render. Each object exposes
 *                           // one or more fields of markup.
 *
 *   $engine = new PhabricatorMarkupEngine();
 *   foreach ($comments as $comment) {
 *     $engine->addObject($comment, $field);
 *   }
 *
 * Now, call @{method:process} to perform the actual cache/rendering
 * step. This is a heavyweight call which does batched data access and
 * transforms the markup into output.
 *
 *   $engine->process();
 *
 * Finally, do something with the results:
 *
 *   $results = array();
 *   foreach ($comments as $comment) {
 *     $results[] = $engine->getOutput($comment, $field);
 *   }
 *
 * If you have a single object to render, you can use the convenience method
 * @{method:renderOneObject}.
 *
 * @task markup Markup Pipeline
 * @task engine Engine Construction
 */
final class PhabricatorMarkupEngine extends OranginsObject
{

    /**
     * @var array
     */
    private $objects = array();
    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $contextObject;
    /**
     * @var int
     */
    private $version = 17;
    /**
     * @var array
     */
    private $engineCaches = array();
    /**
     * @var array
     */
    private $auxiliaryConfig = array();


    /* -(  Markup Pipeline  )---------------------------------------------------- */


    /**
     * Convenience method for pushing a single object through the markup
     * pipeline.
     *
     * @param PhabricatorMarkupInterface $object
     * @param $field
     * @param PhabricatorUser $viewer User viewing the markup.
     * @param null $context_object
     * @return string                     Marked up output.
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws \Throwable
     * @task markup
     */
    public static function renderOneObject(
        PhabricatorMarkupInterface $object,
        $field,
        PhabricatorUser $viewer,
        $context_object = null)
    {
        return (new PhabricatorMarkupEngine())
            ->setViewer($viewer)
            ->setContextObject($context_object)
            ->addObject($object, $field)
            ->process()
            ->getOutput($object, $field);
    }


    /**
     * Queue an object for markup generation when @{method:process} is
     * called. You can retrieve the output later with @{method:getOutput}.
     *
     * @param PhabricatorMarkupInterface  The object to render.
     * @param string                      The field to render.
     * @return PhabricatorMarkupEngine
     * @task markup
     */
    public function addObject(PhabricatorMarkupInterface $object, $field)
    {
        $key = $this->getMarkupFieldKey($object, $field);
        $this->objects[$key] = array(
            'object' => $object,
            'field' => $field,
        );

        return $this;
    }


    /**
     * Process objects queued with @{method:addObject}. You can then retrieve
     * the output with @{method:getOutput}.
     *
     * @return PhabricatorMarkupEngine
     * @task markup
     * @throws Exception
     * @throws \Throwable
     */
    public function process()
    {
        $keys = array();
        foreach ($this->objects as $key => $info) {
            if (!isset($info['markup'])) {
                $keys[] = $key;
            }
        }

        if (!$keys) {
            return $this;
        }

        $objects = array_select_keys($this->objects, $keys);

        // Build all the markup engines. We need an engine for each field whether
        // we have a cache or not, since we still need to postprocess the cache.
        $engines = array();
        foreach ($objects as $key => $info) {
            $engines[$key] = $info['object']->newMarkupEngine($info['field']);
            $engines[$key]->setConfig('viewer', $this->viewer);
            $engines[$key]->setConfig('contextObject', $this->contextObject);

            foreach ($this->auxiliaryConfig as $aux_key => $aux_value) {
                $engines[$key]->setConfig($aux_key, $aux_value);
            }
        }

        // Load or build the preprocessor caches.
        $blocks = $this->loadPreprocessorCaches($engines, $objects);
        $blocks = mpull($blocks, 'getCacheData');

        $this->engineCaches = $blocks;

        // Finalize the output.
        foreach ($objects as $key => $info) {
            $engine = $engines[$key];
            $field = $info['field'];
            $object = $info['object'];

            $output = $engine->postprocessText($blocks[$key]);
            $output = $object->didMarkupText($field, $output, $engine);
            $this->objects[$key]['output'] = $output;
        }

        return $this;
    }


    /**
     * Get the output of markup processing for a field queued with
     * @{method:addObject}. Before you can call this method, you must call
     * @{method:process}.
     *
     * @param PhabricatorMarkupInterface  The object to retrieve.
     * @param string                      The field to retrieve.
     * @return string                     Processed output.
     * @task markup
     * @throws Exception
     * @throws PhutilInvalidStateException
     */
    public function getOutput(PhabricatorMarkupInterface $object, $field)
    {
        $key = $this->getMarkupFieldKey($object, $field);
        $this->requireKeyProcessed($key);

        return $this->objects[$key]['output'];
    }


    /**
     * Retrieve engine metadata for a given field.
     *
     * @param PhabricatorMarkupInterface  The object to retrieve.
     * @param string                      The field to retrieve.
     * @param string                      The engine metadata field to retrieve.
     * @param object                        Optional default value.
     * @task markup
     * @return \object
     * @throws Exception
     * @throws PhutilInvalidStateException
     */
    public function getEngineMetadata(
        PhabricatorMarkupInterface $object,
        $field,
        $metadata_key,
        $default = null)
    {

        $key = $this->getMarkupFieldKey($object, $field);
        $this->requireKeyProcessed($key);

        return ArrayHelper::getValue($this->engineCaches[$key]['metadata'], $metadata_key, $default);
    }


    /**
     * @task markup
     * @param $key
     * @throws Exception
     * @throws PhutilInvalidStateException
     */
    private function requireKeyProcessed($key)
    {
        if (empty($this->objects[$key])) {
            throw new Exception(
                \Yii::t("app",
                    "Call {0} before using results (key = '{1}').",
                    [
                        'addObject()',
                        $key
                    ]));
        }

        if (!isset($this->objects[$key]['output'])) {
            throw new PhutilInvalidStateException('process');
        }
    }


    /**
     * @task markup
     * @param PhabricatorMarkupInterface $object
     * @param $field
     * @return string
     */
    private function getMarkupFieldKey(
        PhabricatorMarkupInterface $object,
        $field)
    {

        static $custom;
        if ($custom === null) {
            $custom = array_merge(
                self::loadCustomInlineRules(),
                self::loadCustomBlockRules());

            $custom = mpull($custom, 'getRuleVersion', null);
            ksort($custom);
            $custom = PhabricatorHash::digestForIndex(serialize($custom));
        }

        return $object->getMarkupFieldKey($field) . '@' . $this->version . '@' . $custom;
    }


    /**
     * @task markup
     * @param array $engines
     * @param array $objects
     * @return array
     * @throws Exception
     * @throws \Throwable
     */
    private function loadPreprocessorCaches(array $engines, array $objects)
    {
        $blocks = array();

        $use_cache = array();
        foreach ($objects as $key => $info) {
            if ($info['object']->shouldUseMarkupCache($info['field'])) {
                $use_cache[$key] = true;
            }
        }

        if ($use_cache) {
            try {
                $blocks = PhabricatorMarkupCache::find()->andWhere(['IN', 'cache_key', array_keys($use_cache)])->all();
                $blocks = mpull($blocks, null, 'getCacheKey');
            } catch (Exception $ex) {
                \Yii::error($ex);
            }
        }

        $is_readonly = PhabricatorEnv::isReadOnly();

        foreach ($objects as $key => $info) {
            // False check in case MySQL doesn't support unicode characters
            // in the string (T1191), resulting in unserialize returning false.
            if (isset($blocks[$key]) && $blocks[$key]->getCacheData() !== false) {
                // If we already have a preprocessing cache, we don't need to rebuild
                // it.
                continue;
            }

            $text = $info['object']->getMarkupText($info['field']);

            /** @var PhutilRemarkupEngine $var */
            $var = $engines[$key];
            $data = $var->preprocessText($text);

            // NOTE: This is just debugging information to help sort out cache issues.
            // If one machine is misconfigured and poisoning caches you can use this
            // field to hunt it down.

            $metadata = array(
                'host' => php_uname('n'),
            );

            $blocks[$key] = (new PhabricatorMarkupCache())
                ->setCacheKey($key)
                ->setCacheData($data)
                ->setMetadata($metadata);

            if (isset($use_cache[$key]) && !$is_readonly) {
                // This is just filling a cache and always safe, even on a read pathway.
                $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
                $blocks[$key]->replace();
                unset($unguarded);
            }
        }

        return $blocks;
    }


    /**
     * Set the viewing user. Used to implement object permissions.
     *
     * @param PhabricatorUser $viewer
     * @return PhabricatorMarkupEngine
     * @task markup
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * Set the context object. Used to implement object permissions.
     *
     * @param $object The object in which context this remarkup is used.
     * @return PhabricatorMarkupEngine
     * @task markup
     */
    public function setContextObject($object)
    {
        $this->contextObject = $object;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function setAuxiliaryConfig($key, $value)
    {
        // TODO: This is gross and should be removed. Avoid use.
        $this->auxiliaryConfig[$key] = $value;
        return $this;
    }


    /* -(  Engine Construction  )------------------------------------------------ */


    /**
     * @task engine
     * @throws Exception
     */
    public static function newManiphestMarkupEngine()
    {
        return self::newMarkupEngine(array());
    }


    /**
     * @task engine
     * @throws Exception
     */
    public static function newPhrictionMarkupEngine()
    {
        return self::newMarkupEngine(array(
            'header.generate-toc' => true,
        ));
    }


    /**
     * @task engine
     * @throws Exception
     */
    public static function newPhameMarkupEngine()
    {
        return self::newMarkupEngine(
            array(
                'macros' => false,
                'uri.full' => true,
                'uri.same-window' => true,
                'uri.base' => PhabricatorEnv::getURI('/'),
            ));
    }


    /**
     * @task engine
     * @throws Exception
     */
    public static function newFeedMarkupEngine()
    {
        return self::newMarkupEngine(
            array(
                'macros' => false,
                'youtube' => false,
            ));
    }

    /**
     * @task engine
     * @throws Exception
     */
    public static function newCalendarMarkupEngine()
    {
        return self::newMarkupEngine(array());
    }


    /**
     * @task engine
     * @param array $options
     * @return PhutilRemarkupEngine
     * @throws Exception
     */
    public static function newDifferentialMarkupEngine(array $options = array())
    {
        return self::newMarkupEngine(array(
            'differential.diff' => ArrayHelper::getValue($options, 'differential.diff'),
        ));
    }


    /**
     * @task engine
     * @return PhutilRemarkupEngine
     * @throws Exception
     */
    public static function newDiffusionMarkupEngine()
    {
        return self::newMarkupEngine(array(
            'header.generate-toc' => true,
        ));
    }

    /**
     * @task engine
     * @param string $ruleset
     * @return mixed|null|PhutilRemarkupEngine
     * @throws Exception
     */
    public static function getEngine($ruleset = 'default')
    {
        static $engines = array();
        if (isset($engines[$ruleset])) {
            return $engines[$ruleset];
        }

        $engine = null;
        switch ($ruleset) {
            case 'default':
                $engine = self::newMarkupEngine(array());
                break;
            case 'feed':
                $engine = self::newMarkupEngine(array());
                $engine->setConfig('autoplay.disable', true);
                break;
            case 'nolinebreaks':
                $engine = self::newMarkupEngine(array());
                $engine->setConfig('preserve-linebreaks', false);
                break;
            case 'diffusion-readme':
                $engine = self::newMarkupEngine(array());
                $engine->setConfig('preserve-linebreaks', false);
                $engine->setConfig('header.generate-toc', true);
                break;
            case 'diviner':
                $engine = self::newMarkupEngine(array());
                $engine->setConfig('preserve-linebreaks', false);
                //    $engine->setConfig('diviner.renderer', new DivinerDefaultRenderer());
                $engine->setConfig('header.generate-toc', true);
                break;
            case 'extract':
                // Engine used for reference/edge extraction. Turn off anything which
                // is slow and doesn't change reference extraction.
                $engine = self::newMarkupEngine(array());
                $engine->setConfig('pygments.enabled', false);
                break;
            default:
                throw new Exception(\Yii::t("app",'Unknown engine ruleset: {0}!', [
                    $ruleset
                ]));
        }

        $engines[$ruleset] = $engine;
        return $engine;
    }

    /**
     * @task engine
     * @throws Exception
     */
    private static function getMarkupEngineDefaultConfiguration()
    {
        return array(
            'pygments' => PhabricatorEnv::getEnvConfig('pygments.enabled'),
            'youtube' => PhabricatorEnv::getEnvConfig(
                'remarkup.enable-embedded-youtube'),
            'differential.diff' => null,
            'header.generate-toc' => false,
            'macros' => true,
            'uri.allowed-protocols' => PhabricatorEnv::getEnvConfig(
                'uri.allowed-protocols'),
            'uri.full' => false,
            'syntax-highlighter.engine' => PhabricatorEnv::getEnvConfig(
                'syntax-highlighter.engine'),
            'preserve-linebreaks' => true,
        );
    }


    /**
     * @task engine
     * @param array $options
     * @return PhutilRemarkupEngine
     * @throws Exception
     */
    public static function newMarkupEngine(array $options)
    {
        $options += self::getMarkupEngineDefaultConfiguration();

        $engine = new PhutilRemarkupEngine();

        $engine->setConfig('preserve-linebreaks', $options['preserve-linebreaks']);

        $engine->setConfig('pygments.enabled', $options['pygments']);
        $engine->setConfig(
            'uri.allowed-protocols',
            $options['uri.allowed-protocols']);
        $engine->setConfig('differential.diff', $options['differential.diff']);
        $engine->setConfig('header.generate-toc', $options['header.generate-toc']);
        $engine->setConfig(
            'syntax-highlighter.engine',
            $options['syntax-highlighter.engine']);

        $style_map = (new PhabricatorDefaultSyntaxStyle())
            ->getRemarkupStyleMap();
        $engine->setConfig('phutil.codeblock.style-map', $style_map);

        $engine->setConfig('uri.full', $options['uri.full']);

        if (isset($options['uri.base'])) {
            $engine->setConfig('uri.base', $options['uri.base']);
        }

        if (isset($options['uri.same-window'])) {
            $engine->setConfig('uri.same-window', $options['uri.same-window']);
        }

        $rules = array();
        $rules[] = new PhutilRemarkupEscapeRemarkupRule();
        $rules[] = new PhutilRemarkupMonospaceRule();


        $rules[] = new PhutilRemarkupDocumentLinkRule();
        $rules[] = new PhabricatorNavigationRemarkupRule();
        $rules[] = new PhabricatorKeyboardRemarkupRule();

        if ($options['youtube']) {
            $rules[] = new PhabricatorYoutubeRemarkupRule();
        }

//        $rules[] = new PhabricatorIconRemarkupRule();
//        $rules[] = new PhabricatorEmojiRemarkupRule();
//        $rules[] = new PhabricatorHandleRemarkupRule();

        $applications = PhabricatorApplication::getAllInstalledApplications();
        foreach ($applications as $application) {
            foreach ($application->getRemarkupRules() as $rule) {
                $rules[] = $rule;
            }
        }

        $rules[] = new PhutilRemarkupHyperlinkRule();

//        if ($options['macros']) {
//            $rules[] = new PhabricatorImageMacroRemarkupRule();
//            $rules[] = new PhabricatorMemeRemarkupRule();
//        }

        $rules[] = new PhutilRemarkupBoldRule();
        $rules[] = new PhutilRemarkupItalicRule();
        $rules[] = new PhutilRemarkupDelRule();
        $rules[] = new PhutilRemarkupUnderlineRule();
        $rules[] = new PhutilRemarkupHighlightRule();

        foreach (self::loadCustomInlineRules() as $rule) {
            $rules[] = clone $rule;
        }

        $blocks = array();
        $blocks[] = new PhutilRemarkupQuotesBlockRule();
        $blocks[] = new PhutilRemarkupReplyBlockRule();
        $blocks[] = new PhutilRemarkupLiteralBlockRule();
        $blocks[] = new PhutilRemarkupHeaderBlockRule();
        $blocks[] = new PhutilRemarkupHorizontalRuleBlockRule();
        $blocks[] = new PhutilRemarkupListBlockRule();
        $blocks[] = new PhutilRemarkupCodeBlockRule();
        $blocks[] = new PhutilRemarkupNoteBlockRule();
        $blocks[] = new PhutilRemarkupTableBlockRule();
        $blocks[] = new PhutilRemarkupSimpleTableBlockRule();
        $blocks[] = new PhutilRemarkupInterpreterBlockRule();
        $blocks[] = new PhutilRemarkupDefaultBlockRule();

        foreach (self::loadCustomBlockRules() as $rule) {
            $blocks[] = $rule;
        }

        foreach ($blocks as $block) {
            $block->setMarkupRules($rules);
        }

        $engine->setBlockRules($blocks);

        return $engine;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param array $content_blocks
     * @return array|mixed
     * @author 陈妙威
     * @throws Exception
     */
    public static function extractPHIDsFromMentions(
        PhabricatorUser $viewer,
        array $content_blocks)
    {

        $mentions = array();

        $engine = self::newDifferentialMarkupEngine();
        $engine->setConfig('viewer', $viewer);

        foreach ($content_blocks as $content_block) {
            $engine->markupText($content_block);
            $phids = $engine->getTextMetadata(
                PhabricatorMentionRemarkupRule::KEY_MENTIONED,
                array());
            $mentions += $phids;
        }

        return $mentions;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param array $content_blocks
     * @return array
     * @author 陈妙威
     * @throws Exception
     */
    public static function extractFilePHIDsFromEmbeddedFiles(
        PhabricatorUser $viewer,
        array $content_blocks)
    {
        $files = array();

        $engine = self::newDifferentialMarkupEngine();
        $engine->setConfig('viewer', $viewer);

        foreach ($content_blocks as $content_block) {
            $engine->markupText($content_block);
            $phids = $engine->getTextMetadata(
                PhabricatorEmbedFileRemarkupRule::KEY_EMBED_FILE_PHIDS,
                array());
            foreach ($phids as $phid) {
                $files[$phid] = $phid;
            }
        }

        return array_values($files);
    }

    /**
     * @param $corpus
     * @return string
     * @author 陈妙威
     */
    public static function summarizeSentence($corpus)
    {
        $corpus = trim($corpus);
        $blocks = preg_split('/\n+/', $corpus, 2);
        $block = head($blocks);

        $sentences = preg_split(
            '/\b([.?!]+)\B/u',
            $block,
            2,
            PREG_SPLIT_DELIM_CAPTURE);

        if (count($sentences) > 1) {
            $result = $sentences[0] . $sentences[1];
        } else {
            $result = head($sentences);
        }

        return (new PhutilUTF8StringTruncator())
            ->setMaximumGlyphs(128)
            ->truncateString($result);
    }

    /**
     * Produce a corpus summary, in a way that shortens the underlying text
     * without truncating it somewhere awkward.
     *
     * TODO: We could do a better job of this.
     *
     * @param string  Remarkup corpus to summarize.
     * @return string Summarized corpus.
     */
    public static function summarize($corpus)
    {

        // Major goals here are:
        //  - Don't split in the middle of a character (utf-8).
        //  - Don't split in the middle of, e.g., **bold** text, since
        //    we end up with hanging '**' in the summary.
        //  - Try not to pick an image macro, header, embedded file, etc.
        //  - Hopefully don't return too much text. We don't explicitly limit
        //    this right now.

        $blocks = preg_split("/\n *\n\s*/", $corpus);

        $best = null;
        foreach ($blocks as $block) {
            // This is a test for normal spaces in the block, i.e. a heuristic to
            // distinguish standard paragraphs from things like image macros. It may
            // not work well for non-latin text. We prefer to summarize with a
            // paragraph of normal words over an image macro, if possible.
            $has_space = preg_match('/\w\s\w/', $block);

            // This is a test to find embedded images and headers. We prefer to
            // summarize with a normal paragraph over a header or an embedded object,
            // if possible.
            $has_embed = preg_match('/^[{=]/', $block);

            if ($has_space && !$has_embed) {
                // This seems like a good summary, so return it.
                return $block;
            }

            if (!$best) {
                // This is the first block we found; if everything is garbage just
                // use the first block.
                $best = $block;
            }
        }

        return $best;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private static function loadCustomInlineRules()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorRemarkupCustomInlineRule::class)
            ->execute();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private static function loadCustomBlockRules()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorRemarkupCustomBlockRule::class)
            ->execute();
    }

    /**
     * @param $object
     * @param $content
     * @return string
     * @throws Exception
     * @throws \AphrontCountQueryException
     * @throws \FilesystemException
     * @author 陈妙威
     */
    public static function digestRemarkupContent($object, $content)
    {
        $parts = array();
        $parts[] = get_class($object);

        if ($object instanceof ActiveRecord) {
            $parts[] = $object->getID();
        }

        $parts[] = $content;

        $message = implode("\n", $parts);

        return PhabricatorHash::digestWithNamedKey($message, 'remarkup');
    }

}
