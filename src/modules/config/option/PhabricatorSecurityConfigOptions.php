<?php

namespace orangins\modules\config\option;

use Exception;
use orangins\modules\config\exception\PhabricatorConfigValidationException;
use PhutilURI;
use Yii;

/**
 * Class PhabricatorSecurityConfigOptions
 * @package orangins\modules\config\option
 */
final class PhabricatorSecurityConfigOptions
    extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed
     */
    public function getName()
    {
        return Yii::t('app', 'Security');
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return Yii::t('app', 'Security options.');
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'icon-lock';
    }

    /**
     * @return mixed|string
     */
    public function getGroup()
    {
        return 'core';
    }

    /**
     * @return array|PhabricatorConfigOption[]
     */
    public function getOptions()
    {
        $doc_href = "";
//    $doc_href = PhabricatorEnv::getDoclink('Configuring a File Domain');
        $doc_name = Yii::t('app', 'Configuration Guide: Configuring a File Domain');

        $default_address_blacklist = array(
            // This is all of the IANA special/reserved blocks in IPv4 space.
            '0.0.0.0/8',
            '10.0.0.0/8',
            '100.64.0.0/10',
            '127.0.0.0/8',
            '169.254.0.0/16',
            '172.16.0.0/12',
            '192.0.0.0/24',
            '192.0.2.0/24',
            '192.88.99.0/24',
            '192.168.0.0/16',
            '198.18.0.0/15',
            '198.51.100.0/24',
            '203.0.113.0/24',
            '224.0.0.0/4',
            '240.0.0.0/4',
            '255.255.255.255/32',

            // And these are the IANA special/reserved blocks in IPv6 space.
            '::/128',
            '::1/128',
            '::ffff:0:0/96',
            '100::/64',
            '64:ff9b::/96',
            '2001::/32',
            '2001:10::/28',
            '2001:20::/28',
            '2001:db8::/32',
            '2002::/16',
            'fc00::/7',
            'fe80::/10',
            'ff00::/8',
        );

        $keyring_type = 'custom:PhabricatorKeyringConfigOptionType';
        $keyring_description = $this->deformat(Yii::t('app', <<<EOTEXT
The keyring stores master encryption keys. For help with configuring a keyring
and encryption, see **[[ %s | Configuring Encryption ]]**.
EOTEXT
            ,
//      PhabricatorEnv::getDoclink('Configuring Encryption')
            ""
        ));

        $require_mfa_description = $this->deformat(Yii::t('app', <<<EOTEXT
By default, Phabricator allows users to add multi-factor authentication to
their accounts, but does not require it. By enabling this option, you can
force all users to add at least one authentication factor before they can use
their accounts.

Administrators can query a list of users who do not have MFA configured in
{nav People}:

  - **[[ %s | %s ]]**
EOTEXT
            ,
            [
                '/people/?mfa=false',
                Yii::t('app', 'List of Users Without MFA')
            ]));

        return array(
            $this->newOption('security.alternate-file-domain', 'string', null)
                ->setLocked(true)
                ->setSummary(Yii::t('app', 'Alternate domain to serve files from.'))
                ->setDescription(
                    Yii::t('app',
                        'By default, Phabricator serves files from the same domain ' .
                        'the application is served from. This is convenient, but ' .
                        'presents a security risk.' .
                        "\n\n" .
                        'You should configure a CDN or alternate file domain to mitigate ' .
                        'this risk. Configuring a CDN will also improve performance. See ' .
                        '[[ %s | %s ]] for instructions.',
                        [
                            $doc_href,
                            $doc_name
                        ]))
                ->addExample('https://files.phabcdn.net/', Yii::t('app', 'Valid Setting')),
            $this->newOption(
                'security.hmac-key',
                'string',
                '[D\t~Y7eNmnQGJ;rnH6aF;m2!vJ8@v8C=Cs:aQS\.Qw')
                ->setHidden(true)
                ->setSummary(
                    Yii::t('app', 'Key for HMAC digests.'))
                ->setDescription(
                    Yii::t('app',
                        'Default key for HMAC digests where the key is not important ' .
                        '(i.e., the hash itself is secret). You can change this if you ' .
                        'want (to any other string), but doing so will break existing ' .
                        'sessions and CSRF tokens. This option is deprecated. Newer ' .
                        'code automatically manages HMAC keys.')),
            $this->newOption('security.require-https', 'bool', false)
                ->setLocked(true)
                ->setSummary(
                    Yii::t('app', 'Force users to connect via HTTPS instead of HTTP.'))
                ->setDescription(
                    Yii::t('app',
                        "If the web server responds to both HTTP and HTTPS requests but " .
                        "you want users to connect with only HTTPS, you can set this " .
                        "to `true` to make Phabricator redirect HTTP requests to HTTPS." .
                        "\n\n" .
                        "Normally, you should just configure your server not to accept " .
                        "HTTP traffic, but this setting may be useful if you originally " .
                        "used HTTP and have now switched to HTTPS but don't want to " .
                        "break old links, or if your webserver sits behind a load " .
                        "balancer which terminates HTTPS connections and you can not " .
                        "reasonably configure more granular behavior there." .
                        "\n\n" .
                        "IMPORTANT: Phabricator determines if a request is HTTPS or not " .
                        "by examining the PHP `%s` variable. If you run " .
                        "Apache/mod_php this will probably be set correctly for you " .
                        "automatically, but if you run Phabricator as CGI/FCGI (e.g., " .
                        "through nginx or lighttpd), you need to configure your web " .
                        "server so that it passes the value correctly based on the " .
                        "connection type." .
                        "\n\n" .
                        "If you configure Phabricator in cluster mode, note that this " .
                        "setting is ignored by intracluster requests.",
                        [
                            "\$_SERVER['HTTPS']"
                        ]))
                ->setBoolOptions(
                    array(
                        Yii::t('app', 'Force HTTPS'),
                        Yii::t('app', 'Allow HTTP'),
                    )),
            $this->newOption('security.require-multi-factor-auth', 'bool', false)
                ->setLocked(true)
                ->setSummary(
                    Yii::t('app', 'Require all users to configure multi-factor authentication.'))
                ->setDescription($require_mfa_description)
                ->setBoolOptions(
                    array(
                        Yii::t('app', 'Multi-Factor Required'),
                        Yii::t('app', 'Multi-Factor Optional'),
                    )),
            $this->newOption(
                'orangins.csrf-key',
                'string',
                '0b7ec0592e0a2829d8b71df2fa269b2c6172eca3')
                ->setHidden(true)
                ->setSummary(
                    Yii::t('app', 'Hashed with other inputs to generate CSRF tokens.'))
                ->setDescription(
                    Yii::t('app',
                        'This is hashed with other inputs to generate CSRF tokens. If ' .
                        'you want, you can change it to some other string which is ' .
                        'unique to your install. This will make your install more secure ' .
                        'in a vague, mostly theoretical way. But it will take you like 3 ' .
                        'seconds of mashing on your keyboard to set it up so you might ' .
                        'as well.')),
            $this->newOption(
                'orangins.mail-key',
                'string',
                '5ce3e7e8787f6e40dfae861da315a5cdf1018f12')
                ->setHidden(true)
                ->setSummary(
                    Yii::t('app', 'Hashed with other inputs to generate mail tokens.'))
                ->setDescription(
                    Yii::t('app',
                        "This is hashed with other inputs to generate mail tokens. If " .
                        "you want, you can change it to some other string which is " .
                        "unique to your install. In particular, you will want to do " .
                        "this if you accidentally send a bunch of mail somewhere you " .
                        "shouldn't have, to invalidate all old reply-to addresses.")),
            $this->newOption(
                'uri.allowed-protocols',
                'set',
                array(
                    'http' => true,
                    'https' => true,
                    'mailto' => true,
                ))
                ->setSummary(
                    Yii::t('app', 'Determines which URI protocols are auto-linked.'))
                ->setDescription(
                    Yii::t('app',
                        "When users write comments which have URIs, they'll be " .
                        "automatically linked if the protocol appears in this set. This " .
                        "whitelist is primarily to prevent security issues like " .
                        "%s URIs.",
                        [
                            'javascript://'
                        ]))
                ->addExample("http\nhttps", Yii::t('app', 'Valid Setting'))
                ->setLocked(true),
            $this->newOption(
                'uri.allowed-editor-protocols',
                'set',
                array(
                    'http' => true,
                    'https' => true,

                    // This handler is installed by Textmate.
                    'txmt' => true,

                    // This handler is for MacVim.
                    'mvim' => true,

                    // Unofficial handler for Vim.
                    'vim' => true,

                    // Unofficial handler for Sublime.
                    'subl' => true,

                    // Unofficial handler for Emacs.
                    'emacs' => true,

                    // This isn't a standard handler installed by an application, but
                    // is a reasonable name for a user-installed handler.
                    'editor' => true,
                ))
                ->setSummary(Yii::t('app', 'Whitelists editor protocols for "Open in Editor".'))
                ->setDescription(
                    Yii::t('app',
                        'Users can configure a URI pattern to open files in a text ' .
                        'editor. The URI must use a protocol on this whitelist.'))
                ->setLocked(true),
            $this->newOption(
                'celerity.resource-hash',
                'string',
                'd9455ea150622ee044f7931dabfa52aa')
                ->setSummary(
                    Yii::t('app', 'An input to the hash function when building resource hashes.'))
                ->setDescription(
                    Yii::t('app',
                        'This value is an input to the hash function when building ' .
                        'resource hashes. It has no security value, but if you ' .
                        'accidentally poison user caches (by pushing a bad patch or ' .
                        'having something go wrong with a CDN, e.g.) you can change this ' .
                        'to something else and rebuild the Celerity map to break user ' .
                        'caches. Unless you are doing Celerity development, it is ' .
                        'exceptionally unlikely that you need to modify this.')),
            $this->newOption('remarkup.enable-embedded-youtube', 'bool', false)
                ->setBoolOptions(
                    array(
                        Yii::t('app', 'Embed YouTube videos'),
                        Yii::t('app', "Don't embed YouTube videos"),
                    ))
                ->setSummary(
                    Yii::t('app', 'Determines whether or not YouTube videos get embedded.'))
                ->setDescription(
                    Yii::t('app',
                        "If you enable this, linked YouTube videos will be embedded " .
                        "inline. This has mild security implications (you'll leak " .
                        "referrers to YouTube) and is pretty silly (but sort of " .
                        "awesome).")),
            $this->newOption(
                'security.outbound-blacklist',
                'list<string>',
                $default_address_blacklist)
                ->setLocked(true)
                ->setSummary(
                    Yii::t('app',
                        'Blacklist subnets to prevent user-initiated outbound ' .
                        'requests.'))
                ->setDescription(
                    Yii::t('app',
                        'Phabricator users can make requests to other services from ' .
                        'the Phabricator host in some circumstances (for example, by ' .
                        'creating a repository with a remote URL or having Phabricator ' .
                        'fetch an image from a remote server).' .
                        "\n\n" .
                        'This may represent a security vulnerability if services on ' .
                        'the same subnet will accept commands or reveal private ' .
                        'information over unauthenticated HTTP GET, based on the source ' .
                        'IP address. In particular, all hosts in EC2 have access to ' .
                        'such a service.' .
                        "\n\n" .
                        'This option defines a list of netblocks which Phabricator ' .
                        'will decline to connect to. Generally, you should list all ' .
                        'private IP space here.'))
                ->addExample(array('0.0.0.0/0'), Yii::t('app', 'No Outbound Requests')),
            $this->newOption('security.strict-transport-security', 'bool', false)
                ->setLocked(true)
                ->setBoolOptions(
                    array(
                        Yii::t('app', 'Use HSTS'),
                        Yii::t('app', 'Do Not Use HSTS'),
                    ))
                ->setSummary(Yii::t('app', 'Enable HTTP Strict Transport Security (HSTS).'))
                ->setDescription(
                    Yii::t('app',
                        'HTTP Strict Transport Security (HSTS) sends a header which ' .
                        'instructs browsers that the site should only be accessed ' .
                        'over HTTPS, never HTTP. This defuses an attack where an ' .
                        'adversary gains access to your network, then proxies requests ' .
                        'through an unsecured link.' .
                        "\n\n" .
                        'Do not enable this option if you serve (or plan to ever serve) ' .
                        'unsecured content over plain HTTP. It is very difficult to ' .
                        'undo this change once users\' browsers have accepted the ' .
                        'setting.')),
            $this->newOption('keyring', $keyring_type, array())
                ->setHidden(true)
                ->setSummary(Yii::t('app', 'Configure master encryption keys.'))
                ->setDescription($keyring_description),
        );
    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @throws PhabricatorConfigValidationException
     * @throws \yii\base\Exception
     * @throws Exception
     */
    protected function didValidateOption(
        PhabricatorConfigOption $option,
        $value)
    {

        $key = $option->getKey();
        if ($key == 'security.alternate-file-domain') {

            $uri = new PhutilURI($value);
            $protocol = $uri->getProtocol();
            if ($protocol !== 'http' && $protocol !== 'https') {
                throw new PhabricatorConfigValidationException(
                    Yii::t('app',
                        "Config option '{0}' is invalid. The URI must start with " .
                        "'{1}' or '{2}'.",
                        [
                            $key,
                            'http://',
                            'https://'
                        ]));
            }

            $domain = $uri->getDomain();
            if (strpos($domain, '.') === false) {
                throw new PhabricatorConfigValidationException(
                    Yii::t('app',
                        "Config option '{0}' is invalid. The URI must contain a dot ('.'), " .
                        "like '{1}', not just a bare name like '{2}'. " .
                        "Some web browsers will not set cookies on domains with no TLD.",
                        [
                            $key,
                            'http://example.com/',
                            'http://example/'
                        ]));
            }

            $path = $uri->getPath();
            if ($path !== '' && $path !== '/') {
                throw new PhabricatorConfigValidationException(
                    Yii::t('app',
                        "Config option '{0}' is invalid. The URI must NOT have a path, " .
                        "e.g. '{1}' is OK, but '{2}' is not. Phabricator must be installed " .
                        "on an entire domain; it can not be installed on a path.",
                        [
                            $key,
                            'http://orangins.example.com/',
                            'http://example.com/orangins/'
                        ]));
            }
        }
    }


}
