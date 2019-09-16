<?php

namespace orangins\modules\notification\client;

use orangins\lib\OranginsObject;
use orangins\modules\file\helpers\FileSystemHelper;
use Exception;

/**
 * Class PhabricatorNotificationClient
 * @package orangins\modules\notification\client
 * @author 陈妙威
 */
final class PhabricatorNotificationClient extends OranginsObject
{

    /**
     * @author 陈妙威
     */
    public static function tryAnyConnection()
    {
        $servers = PhabricatorNotificationServerRef::getEnabledAdminServers();

        if (!$servers) {
            return;
        }

        foreach ($servers as $server) {
            $server->loadServerStatus();
            return;
        }

        return;
    }

    /**
     * @param array $data
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public static function tryToPostMessage(array $data)
    {
        $unique_id = FileSystemHelper::readRandomCharacters(32);
        $data = $data + array(
                'uniqueID' => $unique_id,
            );

        $servers = PhabricatorNotificationServerRef::getEnabledAdminServers();

        shuffle($servers);

        foreach ($servers as $server) {
            try {
                $server->postMessage($data);
                return;
            } catch (Exception $ex) {
                // Just ignore any issues here.
            }
        }
    }

}
