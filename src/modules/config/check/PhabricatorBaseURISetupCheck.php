<?php

namespace orangins\modules\config\check;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\request\AphrontRequest;

/**
 * Class PhabricatorBaseURISetupCheck
 * @package orangins\modules\config\check
 * @author 陈妙威
 */
final class PhabricatorBaseURISetupCheck extends PhabricatorSetupCheck
{

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getDefaultGroup()
    {
        return self::GROUP_IMPORTANT;
    }

    /**
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function executeChecks()
    {
        $base_uri = PhabricatorEnv::getEnvConfig('orangins.base-uri');

        $host_header = AphrontRequest::getHTTPHeader('Host');
        if (strpos($host_header, '.') === false) {
            if (!strlen(trim($host_header))) {
                $name = \Yii::t("app", 'No "Host" Header');
                $summary = \Yii::t("app", 'No "Host" header present in request.');
                $message = \Yii::t("app",
                    'This request did not include a "Host" header. This may mean that ' .
                    'your webserver (like nginx or apache) is misconfigured so the ' .
                    '"Host" header is not making it to Phabricator, or that you are ' .
                    'making a raw request without a "Host" header using a tool or ' .
                    'library.' .
                    "\n\n" .
                    'If you are using a web browser, check your webserver ' .
                    'configuration. If you are using a tool or library, check how the ' .
                    'request is being constructed.' .
                    "\n\n" .
                    'It is also possible (but very unlikely) that some other network ' .
                    'device (like a load balancer) is stripping the header.' .
                    "\n\n" .
                    'Requests must include a valid "Host" header.');
            } else {
                $name = \Yii::t("app", 'Bad "Host" Header');
                $summary = \Yii::t("app", 'Request has bad "Host" header.');
                $message = \Yii::t("app",
                    'This request included an invalid "Host" header, with value "%s". ' .
                    'Host headers must contain a dot ("."), like "example.com". This ' .
                    'is required for some browsers to be able to set cookies.' .
                    "\n\n" .
                    'This may mean the base URI is configured incorrectly. You must ' .
                    'serve Phabricator from a base URI with a dot (like ' .
                    '"https://phabricator.mycompany.com"), not a bare domain ' .
                    '(like "https://phabricator/"). If you are trying to use a bare ' .
                    'domain, change your configuration to use a full domain with a dot ' .
                    'in it instead.' .
                    "\n\n" .
                    'This might also mean that your webserver (or some other network ' .
                    'device, like a load balancer) is mangling the "Host" header, or ' .
                    'you are using a tool or library to issue a request manually and ' .
                    'setting the wrong "Host" header.' .
                    "\n\n" .
                    'Requests must include a valid "Host" header.',
                    $host_header);
            }

            $this
                ->newIssue('request.host')
                ->setName($name)
                ->setSummary($summary)
                ->setMessage($message)
                ->setIsFatal(true);
        }

        if ($base_uri) {
            return;
        }

        $base_uri_guess = PhabricatorEnv::getRequestBaseURI();

        $summary = \Yii::t("app",
            'The base URI for this install is not configured. Many major features ' .
            'will not work properly until you configure it.');

        $message = \Yii::t("app",
            'The base URI for this install is not configured, and major features ' .
            'will not work properly until you configure it.' .
            "\n\n" .
            'You should set the base URI to the URI you will use to access ' .
            'Phabricator, like "http://phabricator.example.com/".' .
            "\n\n" .
            'Include the protocol (http or https), domain name, and port number if ' .
            'you are using a port other than 80 (http) or 443 (https).' .
            "\n\n" .
            'Based on this request, it appears that the correct setting is:' .
            "\n\n" .
            '%s' .
            "\n\n" .
            'To configure the base URI, run the command shown below.',
            $base_uri_guess);

        $this
            ->newIssue('config.orangins.base-uri')
            ->setShortName(\Yii::t("app", 'No Base URI'))
            ->setName(\Yii::t("app", 'Base URI Not Configured'))
            ->setSummary($summary)
            ->setMessage($message)
            ->addCommand(
                hsprintf(
                    '<tt>phabricator/ $</tt> %s',
                    csprintf(
                        './bin/config set orangins.base-uri %s',
                        $base_uri_guess)));
    }
}
