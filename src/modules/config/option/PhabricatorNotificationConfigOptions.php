<?php

namespace orangins\modules\config\option;

use orangins\lib\env\PhabricatorEnv;
use PhutilJSON;

/**
 * Class PhabricatorNotificationConfigOptions
 * @package orangins\modules\config\option
 */
final class PhabricatorNotificationConfigOptions
    extends PhabricatorApplicationConfigOptions
{

    /**
     * @return mixed|string
     */
    public function getName()
    {
        return \Yii::t("app", 'Notifications');
    }

    /**
     * @return mixed|string
     */
    public function getDescription()
    {
        return \Yii::t("app", 'Configure real-time notifications.');
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'icon-bell2';
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
     * @throws \Exception
     */
    public function getOptions()
    {
        $servers_type = 'cluster.notifications';
        $servers_help = $this->deformat(\Yii::t("app", <<<EOTEXT
Provide a list of notification servers to enable real-time notifications.

For help setting up notification servers, see **[[ %s | %s ]]** in the
documentation.
EOTEXT
            ,
            [
                PhabricatorEnv::getDoclink('Notifications User Guide: Setup and Configuration'),
                \Yii::t("app", 'Notifications User Guide: Setup and Configuration')
            ]));

        $servers_example1 = array(
            array(
                'type' => 'client',
                'host' => 'orangins.mycompany.com',
                'port' => 22280,
                'protocol' => 'https',
            ),
            array(
                'type' => 'admin',
                'host' => '127.0.0.1',
                'port' => 22281,
                'protocol' => 'http',
            ),
        );

        $servers_example1 = (new PhutilJSON())->encodeAsList(
            $servers_example1);

        return array(
            $this->newOption('notification.servers', $servers_type, array())
                ->setSummary(\Yii::t("app", 'Configure real-time notifications.'))
                ->setDescription($servers_help)
                ->addExample(
                    $servers_example1,
                    \Yii::t("app", 'Simple Example')),
        );
    }

}
