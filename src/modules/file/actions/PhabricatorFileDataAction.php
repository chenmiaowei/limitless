<?php

namespace orangins\modules\file\actions;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontFileResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\response\AphrontResponse;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\people\models\PhabricatorUser;
use PhutilInvalidStateException;
use PhutilSafeHTML;
use PhutilURI;

/**
 * Class PhabricatorFileDataController
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorFileDataAction extends PhabricatorFileAction
{

    /**
     * @var
     */
    private $phid;
    /**
     * @var
     */
    private $key;
    /**
     * @var
     */
    private $file;

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireLogin()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPartialSessions()
    {
        return true;
    }

    /**
     * @return AphrontResponse|\orangins\lib\view\AphrontDialogView
     * @throws PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $this->phid = $request->getURIData('phid');
        $this->key = $request->getURIData('key');

        $alt = PhabricatorEnv::getEnvConfig('security.alternate-file-domain');
        $base_uri = PhabricatorEnv::getEnvConfig('orangins.base-uri');
        $alt_uri = new PhutilURI($alt);
        $alt_domain = $alt_uri->getDomain();
        $req_domain = $request->getHost();
        $main_domain = (new PhutilURI($base_uri))->getDomain();

        $request_kind = $request->getURIData('kind');
        $is_download = ($request_kind === 'download');

        if (!strlen($alt) || $main_domain === $alt_domain) {
            // No alternate domain.
            $should_redirect = false;
            $is_alternate_domain = false;
        } else if ($req_domain != $alt_domain) {
            // Alternate domain, but this request is on the main domain.
            $should_redirect = true;
            $is_alternate_domain = false;
        } else {
            // Alternate domain, and on the alternate domain.
            $should_redirect = false;
            $is_alternate_domain = true;
        }

        $response = $this->loadFile();
        if ($response) {
            return $response;
        }

        $file = $this->getFile();

        if ($should_redirect) {
            return (new AphrontRedirectResponse())
                ->setIsExternal(true)
                ->setURI($file->getCDNURI($request_kind));
        }

        $response = new AphrontFileResponse();
        $response->setCacheDurationInSeconds(60 * 60 * 24 * 30);
        $response->setCanCDN($file->getCanCDN());

        $begin = null;
        $end = null;

        // NOTE: It's important to accept "Range" requests when playing audio.
        // If we don't, Safari has difficulty figuring out how long sounds are
        // and glitches when trying to loop them. In particular, Safari sends
        // an initial request for bytes 0-1 of the audio file, and things go south
        // if we can't respond with a 206 Partial Content.
        $range = $request->getHeaders()['range'];
        if (strlen($range)) {
            list($begin, $end) = $response->parseHTTPRange($range);
        }

        if (!$file->isViewableInBrowser()) {
            $is_download = true;
        }

        $request_type = $request->getHeaders()['X-Phabricator-Request-Type'];
        $is_lfs = ($request_type == 'git-lfs');

        if (!$is_download) {
            $response->setMimeType($file->getViewableMimeType());
        } else {
            $is_post = $request->isHTTPPost();
            $is_public = !$viewer->isLoggedIn();

            // NOTE: Require POST to download files from the primary domain. If the
            // request is not a POST request but arrives on the primary domain, we
            // render a confirmation dialog. For discussion, see T13094.

            // There are two exceptions to this rule:

            // Git LFS requests can download with GET. This is safe (Git LFS won't
            // execute files it downloads) and necessary to support Git LFS.

            // Requests with no credentials may also download with GET. This
            // primarily supports downloading files with `arc download` or other
            // API clients. This is only "mostly" safe: if you aren't logged in, you
            // are likely immune to XSS and CSRF. However, an attacker may still be
            // able to set cookies on this domain (for example, to fixate your
            // session). For now, we accept these risks because users running
            // Phabricator in this mode are knowingly accepting a security risk
            // against setup advice, and there's significant value in having
            // API development against test and production installs work the same
            // way.

            $is_safe = ($is_alternate_domain || $is_post || $is_lfs || $is_public);
            if (!$is_safe) {
                return $this->newDialog()
                    ->setSubmitURI($file->getDownloadURI())
                    ->setTitle(\Yii::t("app",'Download File'))
                    ->appendParagraph(
                        new PhutilSafeHTML(\Yii::t("app",
                            'Download file {0} ({1})?', [
                                phutil_tag('strong', array(), $file->getName()),
                                phutil_format_bytes($file->getByteSize())
                            ])))
//                    ->addCancelButton($file->getURI())
                    ->addSubmitButton(\Yii::t("app",'Download File'));
            }

            $response->setMimeType($file->getMimeType());
            $response->setDownload($file->getName());
        }

        $iterator = $file->getFileDataIterator($begin, $end);

        $response->setContentLength($file->getByteSize());
        $response->setContentIterator($iterator);

        // In Chrome, we must permit this domain in "object-src" CSP when serving a
        // PDF or the browser will refuse to render it.
        if (!$is_download && $file->isPDF()) {
            $absoluteRequestURI = clone $request->getAbsoluteRequestURI();
            $request_uri = $absoluteRequestURI
                ->setPath(null)
                ->setFragment(null)
                ->setQueryParams(array());

            $response->addContentSecurityPolicyURI(
                'object-src',
                (string)$request_uri);
        }

        return $response;
    }

    /**
     * @return null|Aphront404Response|AphrontDialogResponse
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    private function loadFile()
    {
        // Access to files is provided by knowledge of a per-file secret key in
        // the URI. Knowledge of this secret is sufficient to retrieve the file.

        // For some requests, we also have a valid viewer. However, for many
        // requests (like alternate domain requests or Git LFS requests) we will
        // not. Even if we do have a valid viewer, use the omnipotent viewer to
        // make this logic simpler and more consistent.

        // Beyond making the policy check itself more consistent, this also makes
        // sure we're consistent about returning HTTP 404 on bad requests instead
        // of serving HTTP 200 with a login page, which can mislead some clients.

        $viewer = PhabricatorUser::getOmnipotentUser();

        $file = PhabricatorFile::find()
            ->setViewer($viewer)
            ->withPHIDs(array($this->phid))
            ->withIsDeleted(false)
            ->executeOne();

        if (!$file) {
            return new Aphront404Response();
        }

        // We may be on the CDN domain, so we need to use a fully-qualified URI
        // here to make sure we end up back on the main domain.
        $info_uri = PhabricatorEnv::getURI($file->getInfoURI());


        if (!$file->validateSecretKey($this->key)) {
            $dialog = $this->newDialog()
                ->setTitle(\Yii::t("app",'Invalid Authorization'))
                ->appendParagraph(
                    \Yii::t("app",
                        'The link you followed to access this file is no longer ' .
                        'valid. The visibility of the file may have changed after ' .
                        'the link was generated.'))
                ->appendParagraph(
                    \Yii::t("app",
                        'You can continue to the file detail page to get more ' .
                        'information and attempt to access the file.'))
                ->addCancelButton($info_uri, \Yii::t("app",'Continue'));

            return (new AphrontDialogResponse())
                ->setDialog($dialog)
                ->setHTTPResponseCode(404);
        }

        if ($file->getIsPartial()) {
            $dialog = $this->newDialog()
                ->setTitle(\Yii::t("app",'Partial Upload'))
                ->appendParagraph(
                    \Yii::t("app",
                        'This file has only been partially uploaded. It must be ' .
                        'uploaded completely before you can download it.'))
                ->appendParagraph(
                    \Yii::t("app",
                        'You can continue to the file detail page to monitor the ' .
                        'upload progress of the file.'))
                ->addCancelButton($info_uri, \Yii::t("app",'Continue'));

            return (new AphrontDialogResponse())
                ->setDialog($dialog)
                ->setHTTPResponseCode(404);
        }

        $this->file = $file;

        return null;
    }

    /**
     * @return PhabricatorFile
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    private function getFile()
    {
        if (!$this->file) {
            throw new PhutilInvalidStateException('loadFile');
        }
        return $this->file;
    }

}
