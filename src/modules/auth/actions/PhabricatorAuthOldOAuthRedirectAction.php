<?php

namespace orangins\modules\auth\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;

/**
 * Class PhabricatorAuthOldOAuthRedirectAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthOldOAuthRedirectAction
    extends PhabricatorAuthAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireLogin()
    {
        return false;
    }

    /**
     * @param $parameter_name
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowRestrictedParameter($parameter_name)
    {
        if ($parameter_name == 'code') {
            return true;
        }
        return parent::shouldAllowRestrictedParameter($parameter_name);
    }

    /**
     * @return AphrontRedirectResponse|Aphront404Response
     * @throws \PhutilMethodNotImplementedException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $provider = $request->getURIData('provider');
        // TODO: Most OAuth providers are OK with changing the redirect URI, but
        // Google and GitHub are strict. We need to respect the old OAuth URI until
        // we can get installs to migrate. This just keeps the old OAuth URI working
        // by redirecting to the new one.

        $provider_map = array(
            'google' => 'google:google.com',
            'github' => 'github:github.com',
        );

        if (!isset($provider_map[$provider])) {
            return new Aphront404Response();
        }

        $provider_key = $provider_map[$provider];

        $uri = $this->getRequest()->getRequestURI();
        $uri->setPath($this->getApplicationURI('login/' . $provider_key . '/'));
        return (new AphrontRedirectResponse())->setURI($uri);
    }

}
