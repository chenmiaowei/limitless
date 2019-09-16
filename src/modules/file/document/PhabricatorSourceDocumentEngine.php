<?php

namespace orangins\modules\file\document;

use orangins\lib\markup\PhabricatorSyntaxHighlighter;

/**
 * Class PhabricatorSourceDocumentEngine
 * @package orangins\modules\file\document
 * @author 陈妙威
 */
final class PhabricatorSourceDocumentEngine
    extends PhabricatorTextDocumentEngine
{

    /**
     *
     */
    const ENGINEKEY = 'source';

    const HIGHLIGHT_BYTE_LIMIT = 262144;

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed|string
     * @author 陈妙威
     */
    public function getViewAsLabel(PhabricatorDocumentRef $ref)
    {
        return \Yii::t("app",'View as Source');
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool
     * @author 陈妙威
     */
    public function canConfigureHighlighting(PhabricatorDocumentRef $ref)
    {
        return true;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool
     * @author 陈妙威
     */
    public function canBlame(PhabricatorDocumentRef $ref)
    {
        return true;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return string
     * @author 陈妙威
     */
    protected function getDocumentIconIcon(PhabricatorDocumentRef $ref)
    {
        return 'fa-code';
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return int
     * @author 陈妙威
     */
    protected function getContentScore(PhabricatorDocumentRef $ref)
    {
        return 1500;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return array|mixed
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function newDocumentContent(PhabricatorDocumentRef $ref)
    {
        $content = $this->loadTextData($ref);

        $messages = array();

        $highlighting = $this->getHighlightingConfiguration();
        if ($highlighting !== null) {
            $content = PhabricatorSyntaxHighlighter::highlightWithLanguage(
                $highlighting,
                $content);
        } else {
            $highlight_limit = self::HIGHLIGHT_BYTE_LIMIT;
            if (strlen($content) > $highlight_limit) {
                $messages[] = $this->newMessage(
                    \Yii::t("app",
                        'This file is larger than %s, so syntax highlighting was skipped.',
                        phutil_format_bytes($highlight_limit)));
            } else {
                $content = PhabricatorSyntaxHighlighter::highlightWithFilename(
                    $ref->getName(),
                    $content);
            }
        }

        $options = array();
        if ($ref->getBlameURI() && $this->getBlameEnabled()) {
            $content = phutil_split_lines($content);
            $blame = range(1, count($content));
            $blame = array_fuse($blame);
            $options['blame'] = $blame;
        }

        if ($ref->getCoverage()) {
            $options['coverage'] = $ref->getCoverage();
        }

        return array(
            $messages,
            $this->newTextDocumentContent($ref, $content, $options),
        );
    }

}
