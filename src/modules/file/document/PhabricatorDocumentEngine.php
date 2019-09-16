<?php

namespace orangins\modules\file\document;

use orangins\lib\OranginsObject;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\people\models\PhabricatorUser;
use PhutilClassMapQuery;
use PhutilNumber;
use PhutilSortVector;
use Exception;

/**
 * Class PhabricatorDocumentEngine
 * @package orangins\modules\file\document
 * @author 陈妙威
 */
abstract class PhabricatorDocumentEngine
    extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var array
     */
    private $highlightedLines = array();
    /**
     * @var
     */
    private $encodingConfiguration;
    /**
     * @var
     */
    private $highlightingConfiguration;
    /**
     * @var bool
     */
    private $blameConfiguration = true;

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param array $highlighted_lines
     * @return $this
     * @author 陈妙威
     */
    final public function setHighlightedLines(array $highlighted_lines)
    {
        $this->highlightedLines = $highlighted_lines;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    final public function getHighlightedLines()
    {
        return $this->highlightedLines;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed
     * @author 陈妙威
     */
    final public function canRenderDocument(PhabricatorDocumentRef $ref)
    {
        return $this->canRenderDocumentType($ref);
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool
     * @author 陈妙威
     */
    public function canConfigureEncoding(PhabricatorDocumentRef $ref)
    {
        return false;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool
     * @author 陈妙威
     */
    public function canConfigureHighlighting(PhabricatorDocumentRef $ref)
    {
        return false;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool
     * @author 陈妙威
     */
    public function canBlame(PhabricatorDocumentRef $ref)
    {
        return false;
    }

    /**
     * @param $config
     * @return $this
     * @author 陈妙威
     */
    final public function setEncodingConfiguration($config)
    {
        $this->encodingConfiguration = $config;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getEncodingConfiguration()
    {
        return $this->encodingConfiguration;
    }

    /**
     * @param $config
     * @return $this
     * @author 陈妙威
     */
    final public function setHighlightingConfiguration($config)
    {
        $this->highlightingConfiguration = $config;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getHighlightingConfiguration()
    {
        return $this->highlightingConfiguration;
    }

    /**
     * @param $blame_configuration
     * @return $this
     * @author 陈妙威
     */
    final public function setBlameConfiguration($blame_configuration)
    {
        $this->blameConfiguration = $blame_configuration;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    final public function getBlameConfiguration()
    {
        return $this->blameConfiguration;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    final protected function getBlameEnabled()
    {
        return $this->blameConfiguration;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool
     * @author 陈妙威
     */
    public function shouldRenderAsync(PhabricatorDocumentRef $ref)
    {
        return false;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function canRenderDocumentType(
        PhabricatorDocumentRef $ref);

    /**
     * @param PhabricatorDocumentRef $ref
     * @return \PhutilSafeHTML
     * @author 陈妙威
     * @throws \Exception
     */
    final public function newDocument(PhabricatorDocumentRef $ref)
    {
        $can_complete = $this->canRenderCompleteDocument($ref);
        $can_partial = $this->canRenderPartialDocument($ref);

        if (!$can_complete && !$can_partial) {
            return $this->newMessage(
                \Yii::t("app",
                    'This document is too large to be rendered inline. (The document ' .
                    'is %s bytes, the limit for this engine is %s bytes.)',
                    new PhutilNumber($ref->getByteLength()),
                    new PhutilNumber($this->getByteLengthLimit())));
        }

        return $this->newDocumentContent($ref);
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed
     * @author 陈妙威
     */
    final public function newDocumentIcon(PhabricatorDocumentRef $ref)
    {
        return (new PHUIIconView())
            ->setIcon($this->getDocumentIconIcon($ref));
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newDocumentContent(
        PhabricatorDocumentRef $ref);

    /**
     * @param PhabricatorDocumentRef $ref
     * @return string
     * @author 陈妙威
     */
    protected function getDocumentIconIcon(PhabricatorDocumentRef $ref)
    {
        return 'fa-file-o';
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return string
     * @author 陈妙威
     */
    protected function getDocumentRenderingText(PhabricatorDocumentRef $ref)
    {
        return \Yii::t("app",'Loading...');
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    final public function getDocumentEngineKey()
    {
        return $this->getPhobjectClassConstant('ENGINEKEY');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public static function getAllEngines()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getDocumentEngineKey')
            ->execute();
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed
     * @author 陈妙威
     */
    final public function newSortVector(PhabricatorDocumentRef $ref)
    {
        $content_score = $this->getContentScore($ref);

        // Prefer engines which can render the entire file over engines which
        // can only render a header, and engines which can render a header over
        // engines which can't render anything.
        if ($this->canRenderCompleteDocument($ref)) {
            $limit_score = 0;
        } else if ($this->canRenderPartialDocument($ref)) {
            $limit_score = 1;
        } else {
            $limit_score = 2;
        }

        return (new PhutilSortVector())
            ->addInt($limit_score)
            ->addInt(-$content_score);
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return int
     * @author 陈妙威
     */
    protected function getContentScore(PhabricatorDocumentRef $ref)
    {
        return 2000;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getViewAsLabel(PhabricatorDocumentRef $ref);

    /**
     * @param PhabricatorDocumentRef $ref
     * @return string
     * @author 陈妙威
     */
    public function getViewAsIconIcon(PhabricatorDocumentRef $ref)
    {
        $can_complete = $this->canRenderCompleteDocument($ref);
        $can_partial = $this->canRenderPartialDocument($ref);

        if (!$can_complete && !$can_partial) {
            return 'fa-times';
        }

        return $this->getDocumentIconIcon($ref);
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return null|string
     * @author 陈妙威
     */
    public function getViewAsIconColor(PhabricatorDocumentRef $ref)
    {
        $can_complete = $this->canRenderCompleteDocument($ref);

        if (!$can_complete) {
            return 'grey';
        }

        return null;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorDocumentRef $ref
     * @return \dict
     * @throws \Exception
     * @author 陈妙威
     */
    final public static function getEnginesForRef(
        PhabricatorUser $viewer,
        PhabricatorDocumentRef $ref)
    {
        $engines = self::getAllEngines();

        foreach ($engines as $key => $engine) {
            $engine = id(clone $engine)
                ->setViewer($viewer);

            if (!$engine->canRenderDocument($ref)) {
                unset($engines[$key]);
                continue;
            }

            $engines[$key] = $engine;
        }

        if (!$engines) {
            throw new Exception(\Yii::t("app",'No content engine can render this document.'));
        }

        $vectors = array();
        foreach ($engines as $key => $usable_engine) {
            $vectors[$key] = $usable_engine->newSortVector($ref);
        }
        $vectors = msortv($vectors, 'getSelf');

        return array_select_keys($engines, array_keys($vectors));
    }

    /**
     * @return float|int
     * @author 陈妙威
     */
    protected function getByteLengthLimit()
    {
        return (1024 * 1024 * 8);
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool
     * @author 陈妙威
     */
    protected function canRenderCompleteDocument(PhabricatorDocumentRef $ref)
    {
        $limit = $this->getByteLengthLimit();
        if ($limit) {
            $length = $ref->getByteLength();
            if ($length > $limit) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool
     * @author 陈妙威
     */
    protected function canRenderPartialDocument(PhabricatorDocumentRef $ref)
    {
        return false;
    }

    /**
     * @param $message
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    protected function newMessage($message)
    {
        return phutil_tag(
            'div',
            array(
                'class' => 'document-engine-error',
            ),
            $message);
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    final public function newLoadingContent(PhabricatorDocumentRef $ref)
    {
        $spinner = (new PHUIIconView())
            ->setIcon('fa-gear')
            ->addClass('ph-spin');

        return phutil_tag(
            'div',
            array(
                'class' => 'document-engine-loading',
            ),
            array(
                $spinner,
                $this->getDocumentRenderingText($ref),
            ));
    }

}
