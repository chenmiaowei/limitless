<?php

namespace orangins\modules\config\option;

use orangins\modules\config\exception\PhabricatorConfigValidationException;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\OranginsUtil;
use PhutilURI;
use Yii;

/**
 * Class PhabricatorCoreConfigOptions
 * @package orangins\modules\config\option
 */
final class PhabricatorCoreConfigOptions
    extends PhabricatorApplicationConfigOptions
{

    const VALUE_FORMAT_12HOUR = 'g:i A';
    const VALUE_FORMAT_24HOUR = 'H:i';


    const VALUE_FORMAT_ISO = 'Y-m-d';
    const VALUE_FORMAT_US = 'n/j/Y';
    const VALUE_FORMAT_EUROPE = 'd-m-Y';

    /**
     * @return mixed|string
     */
    public function getName()
    {
        return \Yii::t('app', 'Core');
    }

    /**
     * @return mixed|string
     */
    public function getDescription()
    {
        return \Yii::t('app', 'Configure core options, including URIs.');
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'icon-gear';
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
     * @throws \yii\base\Exception
     */
    public function getOptions()
    {
        $timeFormats = array(
            self::VALUE_FORMAT_12HOUR => Yii::t("app", '12 Hour, 2:34 PM'),
            self::VALUE_FORMAT_24HOUR => Yii::t("app", '24 Hour, 14:34'),
        );
        $dataFormats = array(
            self::VALUE_FORMAT_ISO => Yii::t("app", 'ISO 8601: 2000-02-28'),
            self::VALUE_FORMAT_US => Yii::t("app", 'US: 2/28/2000'),
            self::VALUE_FORMAT_EUROPE => Yii::t("app", 'Europe: 28-02-2000'),
        );
        if (phutil_is_windows()) {
            $paths = array();
        } else {
            $paths = array(
                '/bin',
                '/usr/bin',
                '/usr/local/bin',
            );
        }

        $path = getenv('PATH');

        $proto_doc_href = PhabricatorEnv::getDoclink(
            'User Guide: Prototype Applications');
        $proto_doc_name = \Yii::t('app', 'User Guide: Prototype Applications');
        $applications_app_href = '/applications/';

        return array(

            $this->newOption('orangins.base-uri', 'string', null)
                ->setLocked(true)
                ->setSummary(\Yii::t('app', 'URI where Phabricator is installed.'))
                ->setDescription(
                    \Yii::t('app',
                        'Set the URI where Phabricator is installed. Setting this ' .
                        'improves security by preventing cookies from being set on other ' .
                        'domains, and allows daemons to send emails with links that have ' .
                        'the correct domain.'))
                ->addExample('http://orangins.example.com/', \Yii::t('app', 'Valid Setting')),
            $this->newOption('orangins.site-name', 'string', "这是一个测试站")
                ->setSummary(\Yii::t('app', 'Site name for your website.'))
                ->setDescription(
                    \Yii::t('app',
                        'Set site name for your website.'))
                ->addExample('上海保橙网络科技有限公司', \Yii::t('app', 'Valid Setting')),
            $this->newOption('orangins.production-uri', 'string', null)
                ->setSummary(
                    \Yii::t('app', 'Primary install URI, for multi-environment installs.'))
                ->setDescription(
                    \Yii::t('app',
                        'If you have multiple Phabricator environments (like a ' .
                        'development/staging environment for working on testing ' .
                        'Phabricator, and a production environment for deploying it), ' .
                        'set the production environment URI here so that emails and other ' .
                        'durable URIs will always generate with links pointing at the ' .
                        'production environment. If unset, defaults to `%s`. Most ' .
                        'installs do not need to set this option.',
                        'orangins.base-uri'))
                ->addExample('http://orangins.example.com/', \Yii::t('app', 'Valid Setting')),
            $this->newOption('orangins.allowed-uris', 'list<string>', array())
                ->setLocked(true)
                ->setSummary(\Yii::t('app', 'Alternative URIs that can access Phabricator.'))
                ->setDescription(
                    \Yii::t('app',
                        "These alternative URIs will be able to access 'normal' pages " .
                        "on your Phabricator install. Other features such as OAuth " .
                        "won't work. The major use case for this is moving installs " .
                        "across domains."))
                ->addExample(
                    "http://orangins2.example.com/\n" .
                    "http://orangins3.example.com/",
                    \Yii::t('app', 'Valid Setting')),
            $this->newOption('orangins.timezone', 'string', null)
                ->setSummary(
                    \Yii::t('app', 'The timezone Phabricator should use.'))
                ->setDescription(
                    \Yii::t('app',
                        "PHP requires that you set a timezone in your php.ini before " .
                        "using date functions, or it will emit a warning. If this isn't " .
                        "possible (for instance, because you are using HPHP) you can set " .
                        "some valid constant for %s here and Phabricator will set it on " .
                        "your behalf, silencing the warning.",
                        'date_default_timezone_set()'))
                ->addExample('America/New_York', \Yii::t('app', 'US East (EDT)'))
                ->addExample('America/Chicago', \Yii::t('app', 'US Central (CDT)'))
                ->addExample('America/Boise', \Yii::t('app', 'US Mountain (MDT)'))
                ->addExample('America/Los_Angeles', \Yii::t('app', 'US West (PDT)')),
            $this->newOption('orangins.date-format', 'enum', self::VALUE_FORMAT_ISO)
                ->setEnumOptions($dataFormats)
                ->setSummary(
                    \Yii::t('app', 'The timezone Phabricator should use.'))
                ->setDescription(
                    \Yii::t('app',
                        "PHP requires that you set a timezone in your php.ini before " .
                        "using date functions, or it will emit a warning. If this isn't " .
                        "possible (for instance, because you are using HPHP) you can set " .
                        "some valid constant for %s here and Phabricator will set it on " .
                        "your behalf, silencing the warning.",
                        'date_default_timezone_set()')),

            $this->newOption('orangins.time-format', 'enum', self::VALUE_FORMAT_12HOUR)
                ->setEnumOptions($timeFormats)
                ->setSummary(
                    \Yii::t('app', 'The timezone Phabricator should use.'))
                ->setDescription(
                    \Yii::t('app',
                        "PHP requires that you set a timezone in your php.ini before " .
                        "using date functions, or it will emit a warning. If this isn't " .
                        "possible (for instance, because you are using HPHP) you can set " .
                        "some valid constant for %s here and Phabricator will set it on " .
                        "your behalf, silencing the warning.",
                        'date_default_timezone_set()')),


            $this->newOption('orangins.cookie-prefix', 'string', null)
                ->setLocked(true)
                ->setSummary(
                    \Yii::t('app',
                        'Set a string Phabricator should use to prefix cookie names.'))
                ->setDescription(
                    \Yii::t('app',
                        'Cookies set for x.com are also sent for y.x.com. Assuming ' .
                        'Phabricator instances are running on both domains, this will ' .
                        'create a collision preventing you from logging in.'))
                ->addExample('dev', \Yii::t('app', 'Prefix cookie with "%s"', 'dev')),
            $this->newOption('orangins.show-prototypes', 'bool', false)
                ->setLocked(true)
                ->setBoolOptions(
                    array(
                        \Yii::t('app', 'Enable Prototypes'),
                        \Yii::t('app', 'Disable Prototypes'),
                    ))
                ->setSummary(
                    \Yii::t('app',
                        'Install applications which are still under development.'))
                ->setDescription(
                    \Yii::t('app',
                        "IMPORTANT: The upstream does not provide support for prototype " .
                        "applications." .
                        "\n\n" .
                        "Phabricator includes prototype applications which are in an " .
                        "**early stage of development**. By default, prototype " .
                        "applications are not installed, because they are often not yet " .
                        "developed enough to be generally usable. You can enable " .
                        "this option to install them if you're developing Phabricator " .
                        "or are interested in previewing upcoming features." .
                        "\n\n" .
                        "To learn more about prototypes, see [[ %s | %s ]]." .
                        "\n\n" .
                        "After enabling prototypes, you can selectively uninstall them " .
                        "(like normal applications).",
                        [
                            $proto_doc_href,
                            $proto_doc_name
                        ])),
            $this->newOption('orangins.serious-business', 'bool', false)
                ->setBoolOptions(
                    array(
                        \Yii::t('app', 'Serious business'),
                        \Yii::t('app', 'Shenanigans'), // That should be interesting to translate. :P
                    ))
                ->setSummary(
                    \Yii::t('app', 'Allows you to remove levity and jokes from the UI.'))
                ->setDescription(
                    \Yii::t('app',
                        'By default, Phabricator includes some flavor text in the UI, ' .
                        'like a prompt to "Weigh In" rather than "Add Comment" in ' .
                        'Maniphest. If you\'d prefer more traditional UI strings like ' .
                        '"Add Comment", you can set this flag to disable most of the ' .
                        'extra flavor.')),
            $this->newOption('remarkup.ignored-object-names', 'string', '/^(Q|V)\d$/')
                ->setSummary(
                    \Yii::t('app', 'Text values that match this regex and are also object names ' .
                        'will not be linked.'))
                ->setDescription(
                    \Yii::t('app',
                        'By default, Phabricator links object names in Remarkup fields ' .
                        'to the corresponding object. This regex can be used to modify ' .
                        'this behavior; object names that match this regex will not be ' .
                        'linked.')),
            $this->newOption('environment.append-paths', 'list<string>', $paths)
                ->setSummary(
                    \Yii::t('app',
                        'These paths get appended to your %s environment variable.',
                        '$PATH'))
                ->setDescription(
                    \Yii::t('app',
                        "Phabricator occasionally shells out to other binaries on the " .
                        "server. An example of this is the `%s` command, used to " .
                        "syntax-highlight code written in languages other than PHP. By " .
                        "default, it is assumed that these binaries are in the %s of the " .
                        "user running Phabricator (normally 'apache', 'httpd', or " .
                        "'nobody'). Here you can add extra directories to the %s " .
                        "environment variable, for when these binaries are in " .
                        "non-standard locations.\n\n" .
                        "Note that you can also put binaries in `%s` (for example, by " .
                        "symlinking them).\n\n" .
                        "The current value of PATH after configuration is applied is:\n\n" .
                        "  lang=text\n" .
                        "  %s",
                        [
                            'pygmentize',
                            '$PATH',
                            '$PATH',
                            'orangins/support/bin/',
                            $path
                        ]))
                ->setLocked(true)
                ->addExample('/usr/local/bin', \Yii::t('app', 'Add One Path'))
                ->addExample("/usr/bin\n/usr/local/bin", \Yii::t('app', 'Add Multiple Paths')),
            $this->newOption('config.lock', 'set', array())
                ->setLocked(true)
                ->setDescription(\Yii::t('app', 'Additional configuration options to lock.')),
            $this->newOption('config.hide', 'set', array())
                ->setLocked(true)
                ->setDescription(\Yii::t('app', 'Additional configuration options to hide.')),
            $this->newOption('config.ignore-issues', 'set', array())
                ->setLocked(true)
                ->setDescription(\Yii::t('app', 'Setup issues to ignore.')),
            $this->newOption('orangins.env', 'string', null)
                ->setLocked(true)
                ->setDescription(\Yii::t('app', 'Internal.')),
            $this->newOption('test.value', 'wild', null)
                ->setLocked(true)
                ->setDescription(\Yii::t('app', 'Unit test value.')),
            $this->newOption('orangins.uninstalled-applications', 'set', array())
                ->setLocked(true)
                ->setLockedMessage(\Yii::t('app',
                    'Use the %s to manage installed applications.'
//                    ,
//                    JavelinHtml::tag(
//                        'a',
//                        array(
//                            'href' => $applications_app_href,
//                        ),
//                        \Yii::t('app', 'Applications application')
//                    )
                ))
                ->setDescription(
                    \Yii::t('app', 'Array containing list of uninstalled applications.')),
            $this->newOption('orangins.application-settings', 'wild', array())
                ->setLocked(true)
                ->setDescription(
                    \Yii::t('app', 'Customized settings for Phabricator applications.')),
            $this->newOption('orangins.cache-namespace', 'string', 'orangins')
                ->setLocked(true)
                ->setDescription(\Yii::t('app', 'Cache namespace.')),
            $this->newOption('orangins.allow-email-users', 'bool', false)
                ->setBoolOptions(
                    array(
                        \Yii::t('app', 'Allow'),
                        \Yii::t('app', 'Disallow'),
                    ))
                ->setDescription(
                    \Yii::t('app', 'Allow non-members to interact with tasks over email.')),
            $this->newOption('orangins.show-save-query', 'bool', false)
                ->setBoolOptions(
                    array(
                        \Yii::t('app', 'Allow'),
                        \Yii::t('app', 'Disallow'),
                    ))
                ->setDescription(
                    \Yii::t('app', 'Allow save-query button show.')),
            $this->newOption('orangins.silent', 'bool', false)
                ->setLocked(true)
                ->setBoolOptions(
                    array(
                        \Yii::t('app', 'Run Silently'),
                        \Yii::t('app', 'Run Normally'),
                    ))
                ->setSummary(\Yii::t('app', 'Stop Phabricator from sending any email, etc.'))
                ->setDescription(
                    \Yii::t('app',
                        'This option allows you to stop Phabricator from sending ' .
                        'any data to external services. Among other things, it will ' .
                        'disable email, SMS, repository mirroring, and HTTP hooks.' .
                        "\n\n" .
                        'This option is intended to allow a Phabricator instance to ' .
                        'be exported, copied, imported, and run in a test environment ' .
                        'without impacting users. For example, if you are migrating ' .
                        'to new hardware, you could perform a test migration first, ' .
                        'make sure things work, and then do a production cutover ' .
                        'later with higher confidence and less disruption. Without ' .
                        'this flag, users would receive duplicate email during the ' .
                        'time the test instance and old production instance were ' .
                        'both in operation.')),
        );

    }

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @throws PhabricatorConfigValidationException
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    protected function didValidateOption(
        PhabricatorConfigOption $option,
        $value)
    {

        $key = $option->getKey();
        if ($key == 'orangins.base-uri' ||
            $key == 'orangins.production-uri') {

            $uri = new PhutilURI($value);
            $protocol = $uri->getProtocol();
            if ($protocol !== 'http' && $protocol !== 'https') {
                throw new PhabricatorConfigValidationException(
                    \Yii::t('app',
                        "Config option '{0}' is invalid. The URI must start with " .
                        "{1}' or '{2}'.",
                        [
                            'http://',
                            'https://',
                            $key
                        ]));
            }

            $domain = $uri->getDomain();
            if (strpos($domain, '.') === false) {
                throw new PhabricatorConfigValidationException(
                    \Yii::t('app',
                        "Config option '{0}' is invalid. The URI must contain a dot " .
                        "('{1}'), like '{2}', not just a bare name like '{3}'. Some web " .
                        "browsers will not set cookies on domains with no TLD.",
                        [
                            '.',
                            'http://example.com/',
                            'http://example/',
                            $key
                        ]));
            }

            $path = $uri->getPath();
            if ($path !== '' && $path !== '/') {
                throw new PhabricatorConfigValidationException(
                    \Yii::t('app',
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


        if ($key === 'orangins.timezone') {
            $old = date_default_timezone_get();
            $ok = @date_default_timezone_set($value);
            @date_default_timezone_set($old);

            if (!$ok) {
                throw new PhabricatorConfigValidationException(
                    \Yii::t('app',
                        "Config option '%s' is invalid. The timezone identifier must " .
                        "be a valid timezone identifier recognized by PHP, like '%s'. " . "
            You can find a list of valid identifiers here: %s",
                        [
                            $key,
                            'America/Los_Angeles',
                            'http://php.net/manual/timezones.php'
                        ]
                    ));
            }
        }
    }


}
