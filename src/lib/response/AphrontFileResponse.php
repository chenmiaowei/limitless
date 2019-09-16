<?php

namespace orangins\lib\response;

/**
 * Class AphrontFileResponse
 * @package orangins\lib\response
 * @author 陈妙威
 */
final class AphrontFileResponse extends AphrontResponse
{

    /**
     * @var
     */
    private $content;
    /**
     * @var
     */
    private $contentIterator;
    /**
     * @var
     */
    private $contentLength;
    /**
     * @var
     */
    private $compressResponse;

    /**
     * @var
     */
    private $mimeType;
    /**
     * @var
     */
    private $download;
    /**
     * @var
     */
    private $rangeMin;
    /**
     * @var
     */
    private $rangeMax;
    /**
     * @var array
     */
    private $allowOrigins = array();

    /**
     * @param $origin
     * @return $this
     * @author 陈妙威
     */
    public function addAllowOrigin($origin)
    {
        $this->allowOrigins[] = $origin;
        return $this;
    }

    /**
     * @param $download
     * @return $this
     * @author 陈妙威
     */
    public function setDownload($download)
    {
        if (!strlen($download)) {
            $download = 'untitled';
        }
        $this->download = $download;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDownload()
    {
        return $this->download;
    }

    /**
     * @param $mime_type
     * @return $this
     * @author 陈妙威
     */
    public function setMimeType($mime_type)
    {
        $this->mimeType = $mime_type;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @param $content
     * @return $this
     * @author 陈妙威
     */
    public function setContent($content)
    {
        $this->setContentLength(strlen($content));
        $this->content = $content;
        return $this;
    }

    /**
     * @param $iterator
     * @return $this
     * @author 陈妙威
     */
    public function setContentIterator($iterator)
    {
        $this->contentIterator = $iterator;
        return $this;
    }

    /**
     * @author 陈妙威
     */
    public function buildResponseString()
    {
        return $this->content;
    }

    /**
     * @return array
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function getContentIterator()
    {
        if ($this->contentIterator) {
            return $this->contentIterator;
        }
        return parent::getContentIterator();
    }

    /**
     * @param $length
     * @return $this
     * @author 陈妙威
     */
    public function setContentLength($length)
    {
        $this->contentLength = $length;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getContentLength()
    {
        return $this->contentLength;
    }

    /**
     * @param $compress_response
     * @return $this
     * @author 陈妙威
     */
    public function setCompressResponse($compress_response)
    {
        $this->compressResponse = $compress_response;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCompressResponse()
    {
        return $this->compressResponse;
    }

    /**
     * @param $min
     * @param $max
     * @return $this
     * @author 陈妙威
     */
    public function setRange($min, $max)
    {
        $this->rangeMin = $min;
        $this->rangeMax = $max;
        return $this;
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getHeaders()
    {
        $headers = array(
            array('Content-Type', $this->getMimeType()),
            // This tells clients that we can support requests with a "Range" header,
            // which allows downloads to be resumed, in some browsers, some of the
            // time, if the stars align.
            array('Accept-Ranges', 'bytes'),
        );

        if ($this->rangeMin !== null || $this->rangeMax !== null) {
            $len = $this->getContentLength();
            $min = $this->rangeMin;

            $max = $this->rangeMax;
            if ($max === null) {
                $max = ($len - 1);
            }

            $headers[] = array('Content-Range', "bytes {$min}-{$max}/{$len}");
            $content_len = ($max - $min) + 1;
        } else {
            $content_len = $this->getContentLength();
        }

        if (!$this->shouldCompressResponse()) {
            $headers[] = array('Content-Length', $content_len);
        }

        if (strlen($this->getDownload())) {
            $headers[] = array('X-Download-Options', 'noopen');

            $filename = $this->getDownload();
            $filename = addcslashes($filename, '"\\');
            $headers[] = array(
                'Content-Disposition',
                'attachment; filename="' . $filename . '"',
            );
        }

        if ($this->allowOrigins) {
            $headers[] = array(
                'Access-Control-Allow-Origin',
                implode(',', $this->allowOrigins),
            );
        }

        $headers = array_merge(parent::getHeaders(), $headers);
        return $headers;
    }

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    protected function shouldCompressResponse()
    {
        return $this->getCompressResponse();
    }

    /**
     * @param $range
     * @return array
     * @author 陈妙威
     */
    public function parseHTTPRange($range)
    {
        $begin = null;
        $end = null;

        $matches = null;
        if (preg_match('/^bytes=(\d+)-(\d*)$/', $range, $matches)) {
            // Note that the "Range" header specifies bytes differently than
            // we do internally: the range 0-1 has 2 bytes (byte 0 and byte 1).
            $begin = (int)$matches[1];

            // The "Range" may be "200-299" or "200-", meaning "until end of file".
            if (strlen($matches[2])) {
                $range_end = (int)$matches[2];
                $end = $range_end + 1;
            } else {
                $range_end = null;
            }

            $this->setHTTPResponseCode(206);
            $this->setRange($begin, $range_end);
        }

        return array($begin, $end);
    }

}
