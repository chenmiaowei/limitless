<?php

namespace orangins\modules\daemon\event;

use Exception;
use yii\base\Event;
use orangins\lib\events\PhabricatorEventListener;
use orangins\modules\daemon\models\PhabricatorDaemonLog;
use orangins\modules\daemon\models\PhabricatorDaemonLogEvent;
use PhutilDaemonHandle;

/**
 * Class PhabricatorDaemonEventListener
 * @package orangins\modules\daemon\event
 * @author 陈妙威
 */
final class PhabricatorDaemonEventListener extends PhabricatorEventListener
{

    /**
     * @var array
     */
    private $daemons = array();

    /**
     * @return mixed|void
     * @author 陈妙威
     */
    public function register()
    {
        $this->listen(PhutilDaemonHandle::EVENT_DID_LAUNCH);
        $this->listen(PhutilDaemonHandle::EVENT_DID_LOG);
        $this->listen(PhutilDaemonHandle::EVENT_DID_HEARTBEAT);
        $this->listen(PhutilDaemonHandle::EVENT_WILL_GRACEFUL);
        $this->listen(PhutilDaemonHandle::EVENT_WILL_EXIT);
    }

    /**
     * @param Event $event
     * @return mixed|void
     * @throws \AphrontQueryException
     * @throws \yii\db\IntegrityException
     * @throws Exception
     * @author 陈妙威
     */
    public function handleEvent(Event $event)
    {
        switch ($event->getType()) {
            case PhutilDaemonHandle::EVENT_DID_LAUNCH:
                $this->handleLaunchEvent($event);
                break;
            case PhutilDaemonHandle::EVENT_DID_HEARTBEAT:
                $this->handleHeartbeatEvent($event);
                break;
            case PhutilDaemonHandle::EVENT_DID_LOG:
                $this->handleLogEvent($event);
                break;
            case PhutilDaemonHandle::EVENT_WILL_GRACEFUL:
                $this->handleGracefulEvent($event);
                break;
            case PhutilDaemonHandle::EVENT_WILL_EXIT:
                $this->handleExitEvent($event);
                break;
        }
    }

    /**
     * @param Event $event
     * @throws \AphrontQueryException
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    private function handleLaunchEvent(Event $event)
    {
        $id = $event->getValue('id');
        $current_user = posix_getpwuid(posix_geteuid());

        $daemon = (new PhabricatorDaemonLog())
            ->setDaemonID($id)
            ->setDaemon($event->getValue('daemonClass'))
            ->setHost(php_uname('n'))
            ->setPID(getmypid())
            ->setStatus(PhabricatorDaemonLog::STATUS_RUNNING)
            ->setArgv($event->getValue('argv'))
            ->setExplicitArgv($event->getValue('explicitArgv'))
            ->setRunningAsUser($current_user['name'])
            ->save();

        $this->daemons[$id] = $daemon;
    }

    /**
     * @param Event $event
     * @throws Exception
     * @author 陈妙威
     */
    private function handleHeartbeatEvent(Event $event)
    {
        $daemon = $this->getDaemon($event->getValue('id'));

        // Just update the timestamp.
        $daemon->save();
    }

    /**
     * @param Event $event
     * @throws Exception
     * @author 陈妙威
     */
    private function handleLogEvent(Event $event)
    {
        $daemon = $this->getDaemon($event->getValue('id'));

        // TODO: This is a bit awkward for historical reasons, clean it up after
        // removing Conduit.
        $message = $event->getValue('message');
        $context = $event->getValue('context');
        if (strlen($context) && $context !== $message) {
            $message = "({$context}) {$message}";
        }

        $type = $event->getValue('type');

        $message = phutil_utf8ize($message);

        (new PhabricatorDaemonLogEvent())
            ->setLogID($daemon->getID())
            ->setLogType($type)
            ->setMessage((string)$message)
            ->setEpoch(time())
            ->save();

        switch ($type) {
            case 'WAIT':
                $current_status = PhabricatorDaemonLog::STATUS_WAIT;
                break;
            default:
                $current_status = PhabricatorDaemonLog::STATUS_RUNNING;
                break;
        }

        if ($current_status !== $daemon->getStatus()) {
            $daemon->setStatus($current_status)->save();
        }
    }

    /**
     * @param Event $event
     * @throws Exception
     * @author 陈妙威
     */
    private function handleGracefulEvent(Event $event)
    {
        $id = $event->getValue('id');

        $daemon = $this->getDaemon($id);
        $daemon->setStatus(PhabricatorDaemonLog::STATUS_EXITING)->save();
    }

    /**
     * @param Event $event
     * @throws Exception
     * @author 陈妙威
     */
    private function handleExitEvent(Event $event)
    {
        $id = $event->getValue('id');

        $daemon = $this->getDaemon($id);
        $daemon->setStatus(PhabricatorDaemonLog::STATUS_EXITED)->save();

        unset($this->daemons[$id]);
    }

    /**
     * @param $id
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function getDaemon($id)
    {
        if (isset($this->daemons[$id])) {
            return $this->daemons[$id];
        }
        throw new Exception(pht('No such daemon "%s"!', $id));
    }

}
