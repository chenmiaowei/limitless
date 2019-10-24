<?php

namespace orangins\modules\metamta\adapters;

use PhutilClassMapQuery;
use PhutilEmailAddress;
use PhutilInvalidStateException;
use ReflectionException;
use Yii;
use orangins\lib\OranginsObject;
use Exception;

/**
 * Class PhabricatorMailImplementationAdapter
 * @author 陈妙威
 */
abstract class PhabricatorMailAdapter extends OranginsObject
{
    /**
     * @var
     */
    private $key;
    /**
     * @var
     */
    private $priority;
    /**
     * @var array
     */
    private $options = array();

    /**
     * @var bool
     */
    private $supportsInbound = true;
    /**
     * @var bool
     */
    private $supportsOutbound = true;

    /**
     * @return mixed
     * @throws ReflectionException
     * @author 陈妙威
     */
    final public function getAdapterType()
    {
        return $this->getPhobjectClassConstant('ADAPTERTYPE');
    }

    /**
     * @return PhabricatorMailAdapter[]
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    final public static function getAllAdapters()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getAdapterType')
            ->execute();
    }


    /**
     * @param $email
     * @param string $name
     * @return mixed
     * @author 陈妙威
     */
    abstract public function setFrom($email, $name = '');

    /**
     * @param $email
     * @param string $name
     * @return mixed
     * @author 陈妙威
     */
    abstract public function addReplyTo($email, $name = '');

    /**
     * @param array $emails
     * @return mixed
     * @author 陈妙威
     */
    abstract public function addTos(array $emails);

    /**
     * @param array $emails
     * @return mixed
     * @author 陈妙威
     */
    abstract public function addCCs(array $emails);

    /**
     * @param $data
     * @param $filename
     * @param $mimetype
     * @return mixed
     * @author 陈妙威
     */
    abstract public function addAttachment($data, $filename, $mimetype);

    /**
     * @param $header_name
     * @param $header_value
     * @return mixed
     * @author 陈妙威
     */
    abstract public function addHeader($header_name, $header_value);

    /**
     * @param $plaintext_body
     * @return mixed
     * @author 陈妙威
     */
    abstract public function setBody($plaintext_body);

    /**
     * @param $html_body
     * @return mixed
     * @author 陈妙威
     */
    abstract public function setHTMLBody($html_body);

    /**
     * @param $subject
     * @return mixed
     * @author 陈妙威
     */
    abstract public function setSubject($subject);


    /**
     * Some mailers, notably Amazon SES, do not support us setting a specific
     * Message-ID header.
     */
    abstract public function supportsMessageIDHeader();


    /**
     * Send the message. Generally, this means connecting to some service and
     * handing data to it.
     *
     * If the adapter determines that the mail will never be deliverable, it
     * should throw a @{class:PhabricatorMetaMTAPermanentFailureException}.
     *
     * For temporary failures, throw some other exception or return `false`.
     *
     * @return bool True on success.
     */
    abstract public function send();

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     */
    final public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getKey()
    {
        return $this->key;
    }

    /**
     * @param $priority
     * @return $this
     * @author 陈妙威
     */
    final public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param $supports_inbound
     * @return $this
     * @author 陈妙威
     */
    final public function setSupportsInbound($supports_inbound)
    {
        $this->supportsInbound = $supports_inbound;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    final public function getSupportsInbound()
    {
        return $this->supportsInbound;
    }

    /**
     * @param $supports_outbound
     * @return $this
     * @author 陈妙威
     */
    final public function setSupportsOutbound($supports_outbound)
    {
        $this->supportsOutbound = $supports_outbound;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    final public function getSupportsOutbound()
    {
        return $this->supportsOutbound;
    }

    /**
     * @param $key
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final public function getOption($key)
    {
        if (!array_key_exists($key, $this->options)) {
            throw new Exception(
                Yii::t("app",
                    'Mailer ("{0}") is attempting to access unknown option ("{1}").',
                    [
                        get_class($this),
                        $key
                    ]));
        }

        return $this->options[$key];
    }

    /**
     * @param array $options
     * @return $this
     * @author 陈妙威
     */
    final public function setOptions(array $options)
    {
        $this->validateOptions($options);
        $this->options = $options;
        return $this;
    }

    /**
     * @param array $options
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function validateOptions(array $options);

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function newDefaultOptions();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function newLegacyOptions();

    /**
     * @author 陈妙威
     */
    public function prepareForSend()
    {
        return;
    }

    /**
     * @param $email
     * @param null $name
     * @return string
     * @author 陈妙威
     */
    protected function renderAddress($email, $name = null)
    {
        if (strlen($name)) {
            return (string)(new PhutilEmailAddress())
                ->setDisplayName($name)
                ->setAddress($email);
        } else {
            return $email;
        }
    }
}
