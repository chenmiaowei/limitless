<?php

namespace orangins\modules\metamta\adapters;

use Exception;
use HTTPSFuture;
use orangins\lib\env\PhabricatorEnv;
use PhutilJSONParserException;
use PhutilProxyException;
use PhutilTypeSpec;
use yii\helpers\ArrayHelper;

/**
 * Mail adapter that uses Mailgun's web API to deliver email.
 */
final class PhabricatorMailMailgunAdapter
    extends PhabricatorMailAdapter
{

    /**
     *
     */
    const ADAPTERTYPE = 'mailgun';

    /**
     * @var array
     */
    private $params = array();
    /**
     * @var array
     */
    private $attachments = array();

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
        $this->params['reply-to'][] = $this->renderAddress($email, $name);
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
     * @return $this|mixed
     * @author 陈妙威
     */
    public function addAttachment($data, $filename, $mimetype)
    {
        $this->attachments[] = array(
            'data' => $data,
            'name' => $filename,
            'type' => $mimetype,
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
     * @param $html_body
     * @return $this|mixed
     * @author 陈妙威
     */
    public function setHTMLBody($html_body)
    {
        $this->params['html-body'] = $html_body;
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
                'api-key' => 'string',
                'domain' => 'string',
            ));
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function newDefaultOptions()
    {
        return array(
            'api-key' => null,
            'domain' => null,
        );
    }

    /**
     * @return array|mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function newLegacyOptions()
    {
        return array(
            'api-key' => PhabricatorEnv::getEnvConfig('mailgun.api-key'),
            'domain' => PhabricatorEnv::getEnvConfig('mailgun.domain'),
        );
    }

    /**
     * @return bool
     * @throws PhutilProxyException
     * @throws Exception
     * @author 陈妙威
     */
    public function send()
    {
        $key = $this->getOption('api-key');
        $domain = $this->getOption('domain');
        $params = array();

        $params['to'] = implode(', ', ArrayHelper::getValue($this->params, 'tos', array()));
        $params['subject'] = ArrayHelper::getValue($this->params, 'subject');
        $params['text'] = ArrayHelper::getValue($this->params, 'body');

        if (ArrayHelper::getValue($this->params, 'html-body')) {
            $params['html'] = ArrayHelper::getValue($this->params, 'html-body');
        }

        $from = ArrayHelper::getValue($this->params, 'from');
        $from_name = ArrayHelper::getValue($this->params, 'from-name');
        $params['from'] = $this->renderAddress($from, $from_name);

        if (ArrayHelper::getValue($this->params, 'reply-to')) {
            $replyto = $this->params['reply-to'];
            $params['h:reply-to'] = implode(', ', $replyto);
        }

        if (ArrayHelper::getValue($this->params, 'ccs')) {
            $params['cc'] = implode(', ', $this->params['ccs']);
        }

        foreach (ArrayHelper::getValue($this->params, 'headers', array()) as $header) {
            list($name, $value) = $header;
            $params['h:' . $name] = $value;
        }

        $future = new HTTPSFuture(
            "https://api:{$key}@api.mailgun.net/v2/{$domain}/messages",
            $params);
        $future->setMethod('POST');

        foreach ($this->attachments as $attachment) {
            $future->attachFileData(
                'attachment',
                $attachment['data'],
                $attachment['name'],
                $attachment['type']);
        }

        list($body) = $future->resolvex();

        $response = null;
        try {
            $response = phutil_json_decode($body);
        } catch (PhutilJSONParserException $ex) {
            throw new PhutilProxyException(
                \Yii::t("app", 'Failed to JSON decode response.'),
                $ex);
        }

        if (!ArrayHelper::getValue($response, 'id')) {
            $message = $response['message'];
            throw new Exception(
                \Yii::t("app",
                    'Request failed with errors: %s.',
                    $message));
        }

        return true;
    }

}
