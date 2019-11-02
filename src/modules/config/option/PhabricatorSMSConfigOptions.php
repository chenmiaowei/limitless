<?php

namespace orangins\modules\config\option;

final class PhabricatorSMSConfigOptions
    extends PhabricatorApplicationConfigOptions
{

    public function getName()
    {
        return \Yii::t("app", 'SMS');
    }

    public function getDescription()
    {
        return \Yii::t("app", 'Configure SMS.');
    }

    public function getIcon()
    {
        return 'icon-mobile';
    }

    public function getGroup()
    {
        return 'core';
    }

    public function getOptions()
    {
        $adapter_description = \Yii::t("app",
            'Adapter class to use to transmit SMS to an external provider. A given ' .
            'external provider will most likely need more configuration which will ' .
            'most likely require registration and payment for the service.');

        return array(
            $this->newOption(
                'sms.default-sender',
                'string',
                null)
                ->setDescription(\Yii::t("app", 'Default "from" number.'))
                ->addExample('8675309', 'Jenny still has this number')
                ->addExample('18005555555', 'Maybe not a real number'),
            $this->newOption(
                'sms.default-adapter',
                'class',
                null)
                ->setBaseClass('PhabricatorSMSImplementationAdapter')
                ->setSummary(\Yii::t("app", 'Control how SMS is sent.'))
                ->setDescription($adapter_description),
            $this->newOption(
                'twilio.account-sid',
                'string',
                null)
                ->setDescription(\Yii::t("app", 'Account ID on Twilio service.'))
                ->setLocked(true)
                ->addExample('gf5kzccfn2sfknpnadvz7kokv6nz5v', \Yii::t("app", '30 characters')),
            $this->newOption(
                'twilio.auth-token',
                'string',
                null)
                ->setDescription(\Yii::t("app", 'Authorization token from Twilio service.'))
                ->setHidden(true)
                ->addExample('f3jsi4i67wiwt6w54hf2zwvy3fjf5h', \Yii::t("app", '30 characters')),
        );
    }

}
