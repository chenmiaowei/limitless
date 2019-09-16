<?php

namespace orangins\modules\metamta\adapters;

use Exception;
use HTTPSFuture;
use orangins\lib\env\PhabricatorEnv;
use PhutilJSONParserException;
use PhutilProxyException;
use PhutilTypeSpec;

/**
 * Mail adapter that uses SendGrid's web API to deliver email.
 */
final class PhabricatorMailImplementationSendGridAdapter
    extends PhabricatorMailImplementationAdapter
{

    /**
     *
     */
    const ADAPTERTYPE = 'sendgrid';

    /**
     * @var array
     */
    private $params = array();

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
                'api-user' => 'string',
                'api-key' => 'string',
            ));
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function newDefaultOptions()
    {
        return array(
            'api-user' => null,
            'api-key' => null,
        );
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function newLegacyOptions()
    {
        return array(
            'api-user' => PhabricatorEnv::getEnvConfig('sendgrid.api-user'),
            'api-key' => PhabricatorEnv::getEnvConfig('sendgrid.api-key'),
        );
    }

    /**
     * @param $email
     * @param string $name
     * @return $this|mixed
     * @author 陈妙威
     */
    public function setFrom($email, $name = '')
    {
        $this->params['from'] = $email;
        $this->params['from-name'] = $name;
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
        if (empty($this->params['reply-to'])) {
            $this->params['reply-to'] = array();
        }
        $this->params['reply-to'][] = array(
            'email' => $email,
            'name' => $name,
        );
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
            $this->params['tos'][] = $email;
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
            $this->params['ccs'][] = $email;
        }
        return $this;
    }

    /**
     * @param $data
     * @param $filename
     * @param $mimetype
     * @return mixed|void
     * @author 陈妙威
     */
    public function addAttachment($data, $filename, $mimetype)
    {
        if (empty($this->params['files'])) {
            $this->params['files'] = array();
        }
        $this->params['files'][$filename] = $data;
    }

    /**
     * @param $header_name
     * @param $header_value
     * @return $this|mixed
     * @author 陈妙威
     */
    public function addHeader($header_name, $header_value)
    {
        $this->params['headers'][] = array($header_name, $header_value);
        return $this;
    }

    /**
     * @param $body
     * @return $this|mixed
     * @author 陈妙威
     */
    public function setBody($body)
    {
        $this->params['body'] = $body;
        return $this;
    }

    /**
     * @param $body
     * @return $this|mixed
     * @author 陈妙威
     */
    public function setHTMLBody($body)
    {
        $this->params['html-body'] = $body;
        return $this;
    }


    /**
     * @param $subject
     * @return $this|mixed
     * @author 陈妙威
     */
    public function setSubject($subject)
    {
        $this->params['subject'] = $subject;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function supportsMessageIDHeader()
    {
        return false;
    }

    /**
     * @return bool
     * @throws \yii\base\Exception
     * @throws Exception
     * @author 陈妙威
     */
    public function send()
    {

        $user = $this->getOption('api-user');
        $key = $this->getOption('api-key');

        if (!$user || !$key) {
            throw new Exception(
                \Yii::t("app",
                    "Configure '%s' and '%s' to use SendGrid for mail delivery.",
                    'sendgrid.api-user',
                    'sendgrid.api-key'));
        }

        $params = array();

        $ii = 0;
        foreach (ArrayHelper::getValue($this->params, 'tos', array()) as $to) {
            $params['to[' . ($ii++) . ']'] = $to;
        }

        $params['subject'] = ArrayHelper::getValue($this->params, 'subject');
        $params['text'] = ArrayHelper::getValue($this->params, 'body');

        if (ArrayHelper::getValue($this->params, 'html-body')) {
            $params['html'] = ArrayHelper::getValue($this->params, 'html-body');
        }

        $params['from'] = ArrayHelper::getValue($this->params, 'from');
        if (ArrayHelper::getValue($this->params, 'from-name')) {
            $params['fromname'] = $this->params['from-name'];
        }

        if (ArrayHelper::getValue($this->params, 'reply-to')) {
            $replyto = $this->params['reply-to'];

            // Pick off the email part, no support for the name part in this API.
            $params['replyto'] = $replyto[0]['email'];
        }

        foreach (ArrayHelper::getValue($this->params, 'files', array()) as $name => $data) {
            $params['files[' . $name . ']'] = $data;
        }

        $headers = ArrayHelper::getValue($this->params, 'headers', array());

        // See SendGrid Support Ticket #29390; there's no explicit REST API support
        // for CC right now but it works if you add a generic "Cc" header.
        //
        // SendGrid said this is supported:
        //   "You can use CC as you are trying to do there [by adding a generic
        //    header]. It is supported despite our limited documentation to this
        //    effect, I am glad you were able to figure it out regardless. ..."
        if (ArrayHelper::getValue($this->params, 'ccs')) {
            $headers[] = array('Cc', implode(', ', $this->params['ccs']));
        }

        if ($headers) {
            // Convert to dictionary.
            $headers = ipull($headers, 1, 0);
            $headers = json_encode($headers);
            $params['headers'] = $headers;
        }

        $params['api_user'] = $user;
        $params['api_key'] = $key;

        $future = new HTTPSFuture(
            'https://sendgrid.com/api/mail.send.json',
            $params);
        $future->setMethod('POST');

        list($body) = $future->resolvex();

        $response = null;
        try {
            $response = phutil_json_decode($body);
        } catch (PhutilJSONParserException $ex) {
            throw new PhutilProxyException(
                \Yii::t("app",'Failed to JSON decode response.'),
                $ex);
        }

        if ($response['message'] !== 'success') {
            $errors = implode(';', $response['errors']);
            throw new Exception(\Yii::t("app",'Request failed with errors: %s.', $errors));
        }

        return true;
    }

}
