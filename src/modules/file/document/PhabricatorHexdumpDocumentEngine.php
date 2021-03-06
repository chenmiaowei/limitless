<?php

namespace orangins\modules\file\document;

use PhutilNumber;

/**
 * Class PhabricatorHexdumpDocumentEngine
 * @package orangins\modules\file\document
 * @author 陈妙威
 */
final class PhabricatorHexdumpDocumentEngine
    extends PhabricatorDocumentEngine
{

    /**
     *
     */
    const ENGINEKEY = 'hexdump';

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed|string
     * @author 陈妙威
     */
    public function getViewAsLabel(PhabricatorDocumentRef $ref)
    {
        return \Yii::t("app",'View as Hexdump');
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return string
     * @author 陈妙威
     */
    protected function getDocumentIconIcon(PhabricatorDocumentRef $ref)
    {
        return 'fa-microchip';
    }

    /**
     * @return float|int
     * @author 陈妙威
     */
    protected function getByteLengthLimit()
    {
        return (1024 * 1024 * 1);
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return int
     * @author 陈妙威
     */
    protected function getContentScore(PhabricatorDocumentRef $ref)
    {
        return 500;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool|mixed
     * @author 陈妙威
     */
    protected function canRenderDocumentType(PhabricatorDocumentRef $ref)
    {
        return true;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool
     * @author 陈妙威
     */
    protected function canRenderPartialDocument(PhabricatorDocumentRef $ref)
    {
        return true;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return array|mixed
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function newDocumentContent(PhabricatorDocumentRef $ref)
    {
        $limit = $this->getByteLengthLimit();
        $length = $ref->getByteLength();

        $is_partial = false;
        if ($limit) {
            if ($length > $limit) {
                $is_partial = true;
                $length = $limit;
            }
        }

        $content = $ref->loadData(null, $length);

        $output = array();
        $offset = 0;

        $lines = str_split($content, 16);
        foreach ($lines as $line) {
            $output[] = sprintf(
                '%08x  %- 23s  %- 23s  %- 16s',
                $offset,
                $this->renderHex(substr($line, 0, 8)),
                $this->renderHex(substr($line, 8)),
                $this->renderBytes($line));

            $offset += 16;
        }

        $output = implode("\n", $output);

        $container = phutil_tag(
            'div',
            array(
                'class' => 'document-engine-hexdump PhabricatorMonospaced',
            ),
            $output);

        $message = null;
        if ($is_partial) {
            $message = $this->newMessage(
                \Yii::t("app",
                    'This document is too large to be completely rendered inline. The ' .
                    'first %s bytes are shown.',
                    new PhutilNumber($limit)));
        }

        return array(
            $message,
            $container,
        );
    }

    /**
     * @param $bytes
     * @return string
     * @author 陈妙威
     */
    private function renderHex($bytes)
    {
        $length = strlen($bytes);

        $output = array();
        for ($ii = 0; $ii < $length; $ii++) {
            $output[] = sprintf('%02x', ord($bytes[$ii]));
        }

        return implode(' ', $output);
    }

    /**
     * @param $bytes
     * @return string
     * @author 陈妙威
     */
    private function renderBytes($bytes)
    {
        $length = strlen($bytes);

        $output = array();
        for ($ii = 0; $ii < $length; $ii++) {
            $chr = $bytes[$ii];
            $ord = ord($chr);

            if ($ord < 0x20 || $ord >= 0x7F) {
                $chr = '.';
            }

            $output[] = $chr;
        }

        return implode('', $output);
    }

}
