<?php

namespace orangins\modules\aphlict\query;

use orangins\lib\OranginsObject;
use orangins\lib\PhabricatorApplication;
use orangins\modules\conpherence\application\PhabricatorConpherenceApplication;
use orangins\modules\notification\application\PhabricatorNotificationsApplication;
use orangins\modules\people\models\PhabricatorUser;
use Exception;

/**
 * Class AphlictDropdownDataQuery
 * @package orangins\modules\aphlict\query
 * @author 陈妙威
 */
final class AphlictDropdownDataQuery extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $notificationData;
    /**
     * @var
     */
    private $conpherenceData;

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param array $data
     * @return $this
     * @author 陈妙威
     */
    private function setNotificationData(array $data)
    {
        $this->notificationData = $data;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威«
     * @throws Exception
     */
    public function getNotificationData()
    {
        if ($this->notificationData === null) {
            throw new Exception(\Yii::t("app",'You must {0} first!', ['execute()']));
        }
        return $this->notificationData;
    }

    /**
     * @param array $data
     * @return $this
     * @author 陈妙威
     */
    private function setConpherenceData(array $data)
    {
        $this->conpherenceData = $data;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    public function getConpherenceData()
    {
        if ($this->conpherenceData === null) {
            throw new Exception(\Yii::t("app",'You must {0} first!', [
                'execute()'
            ]));
        }
        return $this->conpherenceData;
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     */
    public function execute()
    {
        $viewer = $this->getViewer();

        $conpherence_app = PhabricatorConpherenceApplication::class;
        $is_c_installed = PhabricatorApplication::isClassInstalledForViewer(
            $conpherence_app,
            $viewer);
        if ($is_c_installed) {
            $raw_message_count_number = $viewer->getUnreadMessageCount();
            $message_count_number = $this->formatNumber($raw_message_count_number);
        } else {
            $raw_message_count_number = null;
            $message_count_number = null;
        }


        $conpherence_data = array(
            'isInstalled' => $is_c_installed,
            'countType' => 'messages',
            'count' => $message_count_number,
            'rawCount' => $raw_message_count_number,
        );
        $this->setConpherenceData($conpherence_data);

        $notification_app = PhabricatorNotificationsApplication::class;
        $is_n_installed = PhabricatorApplication::isClassInstalledForViewer(
            $notification_app,
            $viewer);
        if ($is_n_installed) {
            $raw_notification_count_number = $viewer->getUnreadNotificationCount();
            $notification_count_number = $this->formatNumber(
                $raw_notification_count_number);
        } else {
            $notification_count_number = null;
            $raw_notification_count_number = null;
        }

        $notification_data = array(
            'isInstalled' => $is_n_installed,
            'countType' => 'notifications',
            'count' => $notification_count_number,
            'rawCount' => $raw_notification_count_number,
        );
        $this->setNotificationData($notification_data);

        return array(
            $notification_app => $this->getNotificationData(),
            $conpherence_app => $this->getConpherenceData(),
        );
    }

    /**
     * @param $number
     * @return string
     * @author 陈妙威
     */
    private function formatNumber($number)
    {
        $formatted = $number;
        if ($number > 999) {
            $formatted = "\xE2\x88\x9E";
        }
        return $formatted;
    }

}
