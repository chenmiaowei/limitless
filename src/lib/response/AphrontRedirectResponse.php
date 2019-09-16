<?php

namespace orangins\lib\response;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIBoxView;
use PhutilURI;
use orangins\lib\view\page\PhabricatorStandardPageView;
use Exception;

/**
 * TODO: Should be final but isn't because of AphrontReloadResponse.
 */
class AphrontRedirectResponse extends AphrontResponse
{

    /**
     * @var
     */
    private $uri;
    /**
     * @var
     */
    private $stackWhenCreated;
    /**
     * @var
     */
    private $isExternal;
    /**
     * @var
     */
    private $closeDialogBeforeRedirect;

    /**
     * @param $external
     * @return $this
     * @author 陈妙威
     */
    public function setIsExternal($external)
    {
        $this->isExternal = $external;
        return $this;
    }

    /**
     * AphrontRedirectResponse constructor.
     * @throws Exception
     */
    public function __construct()
    {
        if ($this->shouldStopForDebugging()) {
            // If we're going to stop, capture the stack so we can print it out.
            $this->stackWhenCreated = (new Exception())->getTrace();
        }
    }

    /**
     * @param $uri
     * @return $this
     * @author 陈妙威
     */
    public function setURI($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     * @throws Exception
     */
    public function getURI()
    {
        // NOTE: When we convert a RedirectResponse into an AjaxResponse, we pull
        // the URI through this method. Make sure it passes checks before we
        // hand it over to callers.
        return self::getURIForRedirect($this->uri, $this->isExternal);
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    public function shouldStopForDebugging()
    {
        return PhabricatorEnv::getEnvConfig('debug.stop-on-redirect');
    }

    /**
     * @param $close
     * @return $this
     * @author 陈妙威
     */
    public function setCloseDialogBeforeRedirect($close)
    {
        $this->closeDialogBeforeRedirect = $close;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCloseDialogBeforeRedirect()
    {
        return $this->closeDialogBeforeRedirect;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getHTTPResponseCode()
    {
        return 302;
    }

    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function getHeaders()
    {
        $headers = array();
        if (!$this->shouldStopForDebugging()) {
            $uri = self::getURIForRedirect($this->uri, $this->isExternal);
            $headers[] = array('Location', $uri);
        }
        $headers = array_merge(parent::getHeaders(), $headers);
        return $headers;
    }

    /**
     * @return string|void
     * @author 陈妙威
     * @throws Exception
     */
    public function buildResponseString()
    {
        if ($this->shouldStopForDebugging()) {
            $request = $this->getRequest();
            $viewer = $request->getViewer();

            $view = new PhabricatorStandardPageView();
            $view->setRequest($this->getRequest());
            $view->setApplicationName(\Yii::t("app", 'Debug'));
            $view->setTitle(\Yii::t("app", 'Stopped on Redirect'));

            $dialog = new AphrontDialogView();
            $dialog->setUser($viewer);
            $dialog->setTitle(\Yii::t("app", 'Stopped on Redirect'));

            $dialog->appendParagraph(
                \Yii::t("app",
                    'You were stopped here because {0} is set in your configuration.',
                    JavelinHtml::phutil_tag('tt', array(), 'debug.stop-on-redirect')));

            $dialog->appendParagraph(
                \Yii::t("app",
                    'You are being redirected to: {0}',
                    JavelinHtml::phutil_tag('tt', array(), $this->getURI())));

            $dialog->addCancelButton($this->getURI(), \Yii::t("app", 'Continue'));

            $dialog->appendChild(JavelinHtml::phutil_tag('br'));

            $dialog->appendChild(
                (new AphrontStackTraceView())
                    ->setUser($viewer)
                    ->setTrace($this->stackWhenCreated));

            $dialog->setIsStandalone(true);
            $dialog->setWidth(AphrontDialogView::WIDTH_FULL);

            $box = (new PHUIBoxView())
                ->addMargin(PHUI::MARGIN_LARGE)
                ->appendChild($dialog);

            $view->appendChild($box);

            return $view->render();
        }

        return '';
    }


    /**
     * Format a URI for use in a "Location:" header.
     *
     * Verifies that a URI redirects to the expected type of resource (local or
     * remote) and formats it for use in a "Location:" header.
     *
     * The HTTP spec says "Location:" headers must use absolute URIs. Although
     * browsers work with relative URIs, we return absolute URIs to avoid
     * ambiguity. For example, Chrome interprets "Location: /\evil.com" to mean
     * "perform a protocol-relative redirect to evil.com".
     *
     * @param   string  URI to redirect to.
     * @param   bool    True if this URI identifies a remote resource.
     * @return  string  URI for use in a "Location:" header.
     * @throws Exception
     */
    public static function getURIForRedirect($uri, $is_external)
    {
        $uri_object = new PhutilURI($uri);
        if ($is_external) {
            // If this is a remote resource it must have a domain set. This
            // would also be caught below, but testing for it explicitly first allows
            // us to raise a better error message.
            if (!strlen($uri_object->getDomain())) {
                throw new Exception(
                    \Yii::t("app",
                        'Refusing to redirect to external URI "{0}". This URI ' .
                        'is not fully qualified, and is missing a domain name. To ' .
                        'redirect to a local resource, remove the external flag.',
                        [
                            (string)$uri
                        ]));
            }

            // Check that it's a valid remote resource.
            if (!PhabricatorEnv::isValidURIForLink($uri)) {
                throw new Exception(
                    \Yii::t("app",
                        'Refusing to redirect to external URI "{0}". This URI ' .
                        'is not a valid remote web resource.',
                        [
                            (string)$uri
                        ]));
            }
        } else {
            // If this is a local resource, it must not have a domain set. This allows
            // us to raise a better error message than the check below can.
            if (strlen($uri_object->getDomain())) {
                throw new Exception(
                    \Yii::t("app",
                        'Refusing to redirect to local resource "{0}". The URI has a ' .
                        'domain, but the redirect is not marked external. Mark ' .
                        'redirects as external to allow redirection off the local ' .
                        'domain.',
                        (string)$uri));
            }

            // If this is a local resource, it must be a valid local resource.
            if (!PhabricatorEnv::isValidLocalURIForLink($uri)) {
                throw new Exception(
                    \Yii::t("app",
                        'Refusing to redirect to local resource "{0}". This URI is not ' .
                        'formatted in a recognizable way.',
                        [
                            (string)$uri
                        ]));
            }
        }
        return (string)$uri;
    }

}
