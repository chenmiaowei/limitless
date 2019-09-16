<?php

namespace orangins\lib\events;


use orangins\lib\OranginsObject;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\PhabricatorApplication;
use PhutilClassMapQuery;
use Yii;
use Exception;

/**
 * Class PhabricatorEventEngine
 * @package orangins\lib\events
 * @author 陈妙威
 */
final class PhabricatorEventEngine extends OranginsObject
{

    /**
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public static function initialize()
    {
        // NOTE: If any of this fails, we just log it and move on. It's important
        // to try to make it through here because users may have difficulty fixing
        // fix the errors if we don't: for example, if we fatal here a user may not
        // be able to run `bin/config` in order to remove an invalid listener.

        // Load automatic listeners.
        $listeners = (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorAutoEventListener::class)
            ->execute();

        // Load configured listeners.
        $config_listeners = PhabricatorEnv::getEnvConfig('events.listeners');
        foreach ($config_listeners as $listener_class) {
            try {
                $listeners[] = newv($listener_class, array());
            } catch (Exception $ex) {
                Yii::error($ex);
            }
        }

        // Add built-in listeners.
//        $listeners[] = new DarkConsoleEventPluginAPI();

        // Add application listeners.
        $applications = PhabricatorApplication::getAllInstalledApplications();
        foreach ($applications as $application) {
            $app_listeners = $application->getEventListeners();
            foreach ($app_listeners as $listener) {
                /** @var PhabricatorEventListener $listener */
                $listener = Yii::createObject($listener);
                $listener->setApplication($application);
                $listeners[] = $listener;
            }
        }

        // Now, register all of the listeners.
        foreach ($listeners as $listener) {
            try {
                $listener->register();
            } catch (Exception $ex) {
                \Yii::error($ex);
            }
        }
    }

}
