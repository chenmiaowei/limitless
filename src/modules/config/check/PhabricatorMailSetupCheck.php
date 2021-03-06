<?php

namespace orangins\modules\config\check;

use Filesystem;
use orangins\lib\env\PhabricatorEnv;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;

final class PhabricatorMailSetupCheck extends PhabricatorSetupCheck
{

    public function getDefaultGroup()
    {
        return self::GROUP_OTHER;
    }

    protected function executeChecks()
    {
        if (PhabricatorEnv::getEnvConfig('cluster.mailers')) {
            return;
        }

        $adapter = PhabricatorEnv::getEnvConfig('metamta.mail-adapter');

        switch ($adapter) {
            case 'PhabricatorMailImplementationPHPMailerLiteAdapter':
                if (!Filesystem::pathExists('/usr/bin/sendmail') &&
                    !Filesystem::pathExists('/usr/sbin/sendmail')) {
                    $message = \Yii::t("app",
                        'Mail is configured to send via sendmail, but this system has ' .
                        'no sendmail binary. Install sendmail or choose a different ' .
                        'mail adapter.');

                    $this->newIssue('config.metamta.mail-adapter')
                        ->setShortName(\Yii::t("app", 'Missing Sendmail'))
                        ->setName(\Yii::t("app", 'No Sendmail Binary Found'))
                        ->setMessage($message)
                        ->addRelatedPhabricatorConfig('metamta.mail-adapter');
                }
                break;
            case 'PhabricatorMailImplementationAmazonSESAdapter':
                if (PhabricatorEnv::getEnvConfig('metamta.can-send-as-user')) {
                    $message = \Yii::t("app",
                        'Amazon SES does not support sending email as users. Disable ' .
                        'send as user, or choose a different mail adapter.');

                    $this->newIssue('config.can-send-as-user')
                        ->setName(\Yii::t("app", "SES Can't Send As User"))
                        ->setMessage($message)
                        ->addRelatedPhabricatorConfig('metamta.mail-adapter')
                        ->addPhabricatorConfig('metamta.can-send-as-user');
                }

                if (!PhabricatorEnv::getEnvConfig('amazon-ses.access-key')) {
                    $message = \Yii::t("app",
                        'Amazon SES is selected as the mail adapter, but no SES access ' .
                        'key is configured. Provide an SES access key, or choose a ' .
                        'different mail adapter.');

                    $this->newIssue('config.amazon-ses.access-key')
                        ->setName(\Yii::t("app", 'Amazon SES Access Key Not Set'))
                        ->setMessage($message)
                        ->addRelatedPhabricatorConfig('metamta.mail-adapter')
                        ->addPhabricatorConfig('amazon-ses.access-key');
                }

                if (!PhabricatorEnv::getEnvConfig('amazon-ses.secret-key')) {
                    $message = \Yii::t("app",
                        'Amazon SES is selected as the mail adapter, but no SES secret ' .
                        'key is configured. Provide an SES secret key, or choose a ' .
                        'different mail adapter.');

                    $this->newIssue('config.amazon-ses.secret-key')
                        ->setName(\Yii::t("app", 'Amazon SES Secret Key Not Set'))
                        ->setMessage($message)
                        ->addRelatedPhabricatorConfig('metamta.mail-adapter')
                        ->addPhabricatorConfig('amazon-ses.secret-key');
                }

                if (!PhabricatorEnv::getEnvConfig('amazon-ses.endpoint')) {
                    $message = \Yii::t("app",
                        'Amazon SES is selected as the mail adapter, but no SES endpoint ' .
                        'is configured. Provide an SES endpoint or choose a different ' .
                        'mail adapter.');

                    $this->newIssue('config.amazon-ses.endpoint')
                        ->setName(\Yii::t("app", 'Amazon SES Endpoint Not Set'))
                        ->setMessage($message)
                        ->addRelatedPhabricatorConfig('metamta.mail-adapter')
                        ->addPhabricatorConfig('amazon-ses.endpoint');
                }

                $address_key = 'metamta.default-address';
                $options = PhabricatorApplicationConfigOptions::loadAllOptions();
                $default = $options[$address_key]->getDefault();
                $value = PhabricatorEnv::getEnvConfig($address_key);
                if ($default === $value) {
                    $message = \Yii::t("app",
                        'Amazon SES requires verification of the "From" address, but ' .
                        'you have not configured a "From" address. Configure and verify ' .
                        'a "From" address, or choose a different mail adapter.');

                    $this->newIssue('config.metamta.default-address')
                        ->setName(\Yii::t("app", 'No SES From Address Configured'))
                        ->setMessage($message)
                        ->addRelatedPhabricatorConfig('metamta.mail-adapter')
                        ->addPhabricatorConfig('metamta.default-address');
                }
                break;
        }

    }
}
