<?php

namespace orangins\modules\metamta\message;

use orangins\modules\metamta\engine\PhabricatorMailSMSEngine;

/**
 * Class PhabricatorMailSMSMessage
 * @package orangins\modules\metamta\message
 * @author 陈妙威
 */
final class PhabricatorMailSMSMessage
    extends PhabricatorMailExternalMessage
{

    /**
     *
     */
    const MESSAGETYPE = 'sms';

    /**
     * @var
     */
    private $toNumber;
    /**
     * @var
     */
    private $textBody;

    /**
     * @return PhabricatorMailSMSEngine
     * @author 陈妙威
     */
    public function newMailMessageEngine()
    {
        return new PhabricatorMailSMSEngine();
    }

    /**
     * @param PhabricatorPhoneNumber $to_number
     * @return $this
     * @author 陈妙威
     */
    public function setToNumber(PhabricatorPhoneNumber $to_number)
    {
        $this->toNumber = $to_number;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getToNumber()
    {
        return $this->toNumber;
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

}
