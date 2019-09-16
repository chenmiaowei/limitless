<?php

namespace orangins\modules\file\document;

use orangins\lib\view\layout\PhabricatorSourceCodeView;
use PhutilTypeSpec;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorTextDocumentEngine
 * @package orangins\modules\file\document
 * @author 陈妙威
 */
abstract class PhabricatorTextDocumentEngine
    extends PhabricatorDocumentEngine
{

    /**
     * @var null
     */
    private $encodingMessage = null;

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool|mixed
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function canRenderDocumentType(PhabricatorDocumentRef $ref)
    {
        return $ref->isProbablyText();
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool
     * @author 陈妙威
     */
    public function canConfigureEncoding(PhabricatorDocumentRef $ref)
    {
        return true;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @param $content
     * @param array $options
     * @return \PhutilSafeHTML
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function newTextDocumentContent(
        PhabricatorDocumentRef $ref,
        $content,
        array $options = array())
    {

        PhutilTypeSpec::checkMap(
            $options,
            array(
                'blame' => 'optional wild',
                'coverage' => 'optional list<wild>',
            ));

        if (is_array($content)) {
            $lines = $content;
        } else {
            $lines = phutil_split_lines($content);
        }

        $view = (new PhabricatorSourceCodeView())
            ->setHighlights($this->getHighlightedLines())
            ->setLines($lines)
            ->setSymbolMetadata($ref->getSymbolMetadata());

        $blame = ArrayHelper::getValue($options, 'blame');
        if ($blame !== null) {
            $view->setBlameMap($blame);
        }

        $coverage = ArrayHelper::getValue($options, 'coverage');
        if ($coverage !== null) {
            $view->setCoverage($coverage);
        }

        $message = null;
        if ($this->encodingMessage !== null) {
            $message = $this->newMessage($this->encodingMessage);
        }

        $container = phutil_tag(
            'div',
            array(
                'class' => 'document-engine-text',
            ),
            array(
                $message,
                $view,
            ));

        return $container;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return null|string|string[]
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function loadTextData(PhabricatorDocumentRef $ref)
    {
        $content = $ref->loadData();

        $encoding = $this->getEncodingConfiguration();
        if ($encoding !== null) {
            if (function_exists('mb_convert_encoding')) {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                $this->encodingMessage = \Yii::t("app",
                    'This document was converted from %s to UTF8 for display.',
                    $encoding);
            } else {
                $this->encodingMessage = \Yii::t("app",
                    'Unable to perform text encoding conversion: mbstring extension ' .
                    'is not available.');
            }
        } else {
            if (!phutil_is_utf8($content)) {
                if (function_exists('mb_detect_encoding')) {
                    $try_encodings = array(
                        'JIS' => \Yii::t("app",'JIS'),
                        'EUC-JP' => \Yii::t("app",'EUC-JP'),
                        'SJIS' => \Yii::t("app",'Shift JIS'),
                        'ISO-8859-1' => \Yii::t("app",'ISO-8859-1 (Latin 1)'),
                    );

                    $guess = mb_detect_encoding($content, array_keys($try_encodings));
                    if ($guess) {
                        $content = mb_convert_encoding($content, 'UTF-8', $guess);
                        $this->encodingMessage = \Yii::t("app",
                            'This document is not UTF8. It was detected as %s and ' .
                            'converted to UTF8 for display.',
                            ArrayHelper::getValue($try_encodings, $guess, $guess));
                    }
                }
            }
        }

        if (!phutil_is_utf8($content)) {
            $content = phutil_utf8ize($content);
            $this->encodingMessage = \Yii::t("app",
                'This document is not UTF8 and its text encoding could not be ' .
                'detected automatically. Use "Change Text Encoding..." to choose ' .
                'an encoding.');
        }

        return $content;
    }

}
