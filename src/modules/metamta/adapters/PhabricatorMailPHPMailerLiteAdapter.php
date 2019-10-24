<?php

namespace orangins\modules\metamta\adapters;

use orangins\lib\env\PhabricatorEnv;
use PHPMailerLite;
use PhutilTypeSpec;

/**
 * TODO: Should be final, but inherited by SES.
 */
class PhabricatorMailPHPMailerLiteAdapter
    extends PhabricatorMailAdapter
{

    /**
     *
     */
    const ADAPTERTYPE = 'sendmail';

    /**
     * @var
     */
    protected $mailer;

    /**
     * @param array $options
     * @return mixed|void
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @author 陈妙威
     */
    protected function validateOptions(array $options)
    {
        PhutilTypeSpec::checkMap(
            $options,
            array(
                'encoding' => 'string',
            ));
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function newDefaultOptions()
    {
        return array(
            'encoding' => 'base64',
        );
    }

    /**
     * @return array|mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function newLegacyOptions()
    {
        return array(
            'encoding' => PhabricatorEnv::getEnvConfig('phpmailer.smtp-encoding'),
        );
    }

    /**
     * @phutil-external-symbol class PHPMailerLite
     */
    public function prepareForSend()
    {
        $root = phutil_get_library_root('orangins');
        require_once $root . '/../externals/phpmailer/class.phpmailer-lite.php';
        $this->mailer = new PHPMailerLite($use_exceptions = true);
        $this->mailer->CharSet = 'utf-8';

        $encoding = $this->getOption('encoding');
        $this->mailer->Encoding = $encoding;

        // By default, PHPMailerLite sends one mail per recipient. We handle
        // combining or separating To and Cc higher in the stack, so tell it to
        // send mail exactly like we ask.
        $this->mailer->SingleTo = false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function supportsMessageIDHeader()
    {
        return true;
    }

    /**
     * @param $email
     * @param string $name
     * @return $this|mixed
     * @author 陈妙威
     */
    public function setFrom($email, $name = '')
    {
        $this->mailer->SetFrom($email, $name, $crazy_side_effects = false);
        return $this;
    }

    /**
     * @param $email
     * @param string $name
     * @return $this|mixed
     * @author 陈妙威
     */
    public function addReplyTo($email, $name = '')
    {
        $this->mailer->AddReplyTo($email, $name);
        return $this;
    }

    /**
     * @param array $emails
     * @return $this|mixed
     * @author 陈妙威
     */
    public function addTos(array $emails)
    {
        foreach ($emails as $email) {
            $this->mailer->AddAddress($email);
        }
        return $this;
    }

    /**
     * @param array $emails
     * @return $this|mixed
     * @author 陈妙威
     */
    public function addCCs(array $emails)
    {
        foreach ($emails as $email) {
            $this->mailer->AddCC($email);
        }
        return $this;
    }

    /**
     * @param $data
     * @param $filename
     * @param $mimetype
     * @return $this|mixed
     * @author 陈妙威
     */
    public function addAttachment($data, $filename, $mimetype)
    {
        $this->mailer->AddStringAttachment(
            $data,
            $filename,
            'base64',
            $mimetype);
        return $this;
    }

    /**
     * @param $header_name
     * @param $header_value
     * @return $this|mixed
     * @author 陈妙威
     */
    public function addHeader($header_name, $header_value)
    {
        if (strtolower($header_name) == 'message-id') {
            $this->mailer->MessageID = $header_value;
        } else {
            $this->mailer->AddCustomHeader($header_name . ': ' . $header_value);
        }
        return $this;
    }

    /**
     * @param $body
     * @return $this|mixed
     * @author 陈妙威
     */
    public function setBody($body)
    {
        $this->mailer->Body = $body;
        $this->mailer->IsHTML(false);
        return $this;
    }


    /**
     * Note: phpmailer-lite does NOT support sending messages with mixed version
     * (plaintext and html). So for now lets just use HTML if it's available.
     * @param $html
     * @return PhabricatorMailPHPMailerLiteAdapter
     */
    public function setHTMLBody($html_body)
    {
        $this->mailer->Body = $html_body;
        $this->mailer->IsHTML(true);
        return $this;
    }

    /**
     * @param $subject
     * @return $this|mixed
     * @author 陈妙威
     */
    public function setSubject($subject)
    {
        $this->mailer->Subject = $subject;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function hasValidRecipients()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function send()
    {
        return $this->mailer->Send();
    }

}
