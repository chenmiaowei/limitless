<?php

namespace orangins\modules\metamta\adapters;

use PhutilCIDRList;
use PhutilPostmarkFuture;
use PhutilTypeSpec;

/**
 * Class PhabricatorMailImplementationPostmarkAdapter
 * @package orangins\modules\metamta\adapters
 * @author 陈妙威
 */
final class PhabricatorMailPostmarkAdapter
    extends PhabricatorMailAdapter
{

    /**
     *
     */
    const ADAPTERTYPE = 'postmark';

    /**
     * @var array
     */
    private $parameters = array();

    /**
     * @param $email
     * @param string $name
     * @return $this|mixed
     * @author 陈妙威
     */
    public function setFrom($email, $name = '')
    {
        $this->parameters['From'] = $this->renderAddress($email, $name);
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
        $this->parameters['ReplyTo'] = $this->renderAddress($email, $name);
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
            $this->parameters['To'][] = $email;
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
            $this->parameters['Cc'][] = $email;
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
        $this->parameters['Attachments'][] = array(
            'Name' => $filename,
            'ContentType' => $mimetype,
            'Content' => base64_encode($data),
        );

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
        $this->parameters['Headers'][] = array(
            'Name' => $header_name,
            'Value' => $header_value,
        );
        return $this;
    }

    /**
     * @param $body
     * @return $this|mixed
     * @author 陈妙威
     */
    public function setBody($body)
    {
        $this->parameters['TextBody'] = $body;
        return $this;
    }

    /**
     * @param $html_body
     * @return $this|mixed
     * @author 陈妙威
     */
    public function setHTMLBody($html_body)
    {
        $this->parameters['HtmlBody'] = $html_body;
        return $this;
    }

    /**
     * @param $subject
     * @return $this|mixed
     * @author 陈妙威
     */
    public function setSubject($subject)
    {
        $this->parameters['Subject'] = $subject;
        return $this;
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
                'access-token' => 'string',
                'inbound-addresses' => 'list<string>',
            ));

        // Make sure this is properly formatted.
        PhutilCIDRList::newList($options['inbound-addresses']);
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function newDefaultOptions()
    {
        return array(
            'access-token' => null,
            'inbound-addresses' => array(
                // Via Postmark support circa February 2018, see:
                //
                // https://postmarkapp.com/support/article/800-ips-for-firewalls
                //
                // "Configuring Outbound Email" should be updated if this changes.
                '50.31.156.6/32',
            ),
        );
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function newLegacyOptions()
    {
        return array();
    }

    /**
     * @return bool
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function send()
    {
        $access_token = $this->getOption('access-token');

        $parameters = $this->parameters;
        $flatten = array(
            'To',
            'Cc',
        );

        foreach ($flatten as $key) {
            if (isset($parameters[$key])) {
                $parameters[$key] = implode(', ', $parameters[$key]);
            }
        }

        (new PhutilPostmarkFuture())
            ->setAccessToken($access_token)
            ->setMethod('email', $parameters)
            ->resolve();

        return true;
    }

}
