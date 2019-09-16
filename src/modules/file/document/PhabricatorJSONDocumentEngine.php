<?php

namespace orangins\modules\file\document;

use orangins\lib\markup\PhabricatorSyntaxHighlighter;
use PhutilJSON;
use PhutilJSONParserException;

/**
 * Class PhabricatorJSONDocumentEngine
 * @package orangins\modules\file\document
 * @author 陈妙威
 */
final class PhabricatorJSONDocumentEngine
    extends PhabricatorTextDocumentEngine
{

    const ENGINEKEY = 'json';

    public function getViewAsLabel(PhabricatorDocumentRef $ref)
    {
        return \Yii::t("app",'View as JSON');
    }

    protected function getDocumentIconIcon(PhabricatorDocumentRef $ref)
    {
        return 'fa-database';
    }

    protected function getContentScore(PhabricatorDocumentRef $ref)
    {
        if (preg_match('/\.json\z/', $ref->getName())) {
            return 2000;
        }

        if ($ref->isProbablyJSON()) {
            return 1750;
        }

        return 500;
    }

    protected function newDocumentContent(PhabricatorDocumentRef $ref)
    {
        $raw_data = $this->loadTextData($ref);

        try {
            $data = phutil_json_decode($raw_data);

            if (preg_match('/^\s*\[/', $raw_data)) {
                $content = (new PhutilJSON())->encodeAsList($data);
            } else {
                $content = (new PhutilJSON())->encodeFormatted($data);
            }

            $message = null;
            $content = PhabricatorSyntaxHighlighter::highlightWithLanguage(
                'json',
                $content);
        } catch (PhutilJSONParserException $ex) {
            $message = $this->newMessage(
                \Yii::t("app",
                    'This document is not valid JSON: %s',
                    $ex->getMessage()));

            $content = $raw_data;
        }

        return array(
            $message,
            $this->newTextDocumentContent($ref, $content),
        );
    }

}
