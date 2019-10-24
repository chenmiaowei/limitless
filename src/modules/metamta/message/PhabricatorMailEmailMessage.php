<?php

namespace orangins\modules\metamta\message;

use orangins\modules\metamta\engine\PhabricatorMailEmailEngine;
use PhutilEmailAddress;

/**
 * Class PhabricatorMailEmailMessage
 * @package orangins\modules\metamta\message
 * @author 陈妙威
 */
final class PhabricatorMailEmailMessage
    extends PhabricatorMailExternalMessage
{

    /**
     *
     */
    const MESSAGETYPE = 'email';

    /**
     * @var
     */
    private $fromAddress;
    /**
     * @var
     */
    private $replyToAddress;
    /**
     * @var array
     */
    private $toAddresses = array();
    /**
     * @var array
     */
    private $ccAddresses = array();
    /**
     * @var array
     */
    private $headers = array();
    /**
     * @var array
     */
    private $attachments = array();
    /**
     * @var
     */
    private $subject;
    /**
     * @var
     */
    private $textBody;
    /**
     * @var
     */
    private $htmlBody;

    /**
     * @return PhabricatorMailEmailEngine
     * @author 陈妙威
     */
    public function newMailMessageEngine()
    {
        return new PhabricatorMailEmailEngine();
    }

    /**
     * @param PhutilEmailAddress $from_address
     * @return $this
     * @author 陈妙威
     */
    public function setFromAddress(PhutilEmailAddress $from_address)
    {
        $this->fromAddress = $from_address;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getFromAddress()
    {
        return $this->fromAddress;
    }

    /**
     * @param PhutilEmailAddress $address
     * @return $this
     * @author 陈妙威
     */
    public function setReplyToAddress(PhutilEmailAddress $address)
    {
        $this->replyToAddress = $address;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getReplyToAddress()
    {
        return $this->replyToAddress;
    }

    /**
     * @param array $addresses
     * @return $this
     * @author 陈妙威
     */
    public function setToAddresses(array $addresses)
    {
        assert_instances_of($addresses, 'PhutilEmailAddress');
        $this->toAddresses = $addresses;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getToAddresses()
    {
        return $this->toAddresses;
    }

    /**
     * @param array $addresses
     * @return $this
     * @author 陈妙威
     */
    public function setCCAddresses(array $addresses)
    {
        assert_instances_of($addresses, 'PhutilEmailAddress');
        $this->ccAddresses = $addresses;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getCCAddresses()
    {
        return $this->ccAddresses;
    }

    /**
     * @param array $headers
     * @return $this
     * @author 陈妙威
     */
    public function setHeaders(array $headers)
    {
        assert_instances_of($headers, 'PhabricatorMailHeader');
        $this->headers = $headers;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $attachments
     * @return $this
     * @author 陈妙威
     */
    public function setAttachments(array $attachments)
    {
        assert_instances_of($attachments, 'PhabricatorMailAttachment');
        $this->attachments = $attachments;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param $subject
     * @return $this
     * @author 陈妙威
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param $text_body
     * @return $this
     * @author 陈妙威
     */
    public function setTextBody($text_body)
    {
        $this->textBody = $text_body;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTextBody()
    {
        return $this->textBody;
    }

    /**
     * @param $html_body
     * @return $this
     * @author 陈妙威
     */
    public function setHTMLBody($html_body)
    {
        $this->htmlBody = $html_body;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHTMLBody()
    {
        return $this->htmlBody;
    }

}
