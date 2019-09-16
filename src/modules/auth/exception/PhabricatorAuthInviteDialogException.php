<?php

namespace orangins\modules\auth\exception;

/**
 * Class PhabricatorAuthInviteDialogException
 * @package orangins\modules\auth\exception
 * @author 陈妙威
 */
abstract class PhabricatorAuthInviteDialogException
    extends PhabricatorAuthInviteException
{

    /**
     * @var
     */
    private $title;
    /**
     * @var
     */
    private $body;
    /**
     * @var
     */
    private $submitButtonText;
    /**
     * @var
     */
    private $submitButtonURI;
    /**
     * @var
     */
    private $cancelButtonText;
    /**
     * @var
     */
    private $cancelButtonURI;

    /**
     * PhabricatorAuthInviteDialogException constructor.
     * @param $title
     * @param $body
     */
    public function __construct($title, $body)
    {
        $this->title = $title;
        $this->body = $body;
        parent::__construct(\Yii::t("app", '{0}: {1}', [$title, $body]));
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param $submit_button_text
     * @return $this
     * @author 陈妙威
     */
    public function setSubmitButtonText($submit_button_text)
    {
        $this->submitButtonText = $submit_button_text;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSubmitButtonText()
    {
        return $this->submitButtonText;
    }

    /**
     * @param $submit_button_uri
     * @return $this
     * @author 陈妙威
     */
    public function setSubmitButtonURI($submit_button_uri)
    {
        $this->submitButtonURI = $submit_button_uri;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSubmitButtonURI()
    {
        return $this->submitButtonURI;
    }

    /**
     * @param $cancel_button_text
     * @return $this
     * @author 陈妙威
     */
    public function setCancelButtonText($cancel_button_text)
    {
        $this->cancelButtonText = $cancel_button_text;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCancelButtonText()
    {
        return $this->cancelButtonText;
    }

    /**
     * @param $cancel_button_uri
     * @return $this
     * @author 陈妙威
     */
    public function setCancelButtonURI($cancel_button_uri)
    {
        $this->cancelButtonURI = $cancel_button_uri;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCancelButtonURI()
    {
        return $this->cancelButtonURI;
    }

}
