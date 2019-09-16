<?php

namespace orangins\modules\people\view;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\view\AphrontView;
use orangins\lib\view\control\AphrontTableView;
use orangins\modules\people\models\PhabricatorUserLog;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorUserLogView
 * @package orangins\modules\people\view
 * @author 陈妙威
 */
final class PhabricatorUserLogView extends AphrontView
{

    /**
     * @var
     */
    private $logs;
    /**
     * @var
     */
    private $searchBaseURI;

    /**
     * @param $search_base_uri
     * @return $this
     * @author 陈妙威
     */
    public function setSearchBaseURI($search_base_uri)
    {
        $this->searchBaseURI = $search_base_uri;
        return $this;
    }

    /**
     * @param array $logs
     * @return $this
     * @author 陈妙威
     */
    public function setLogs(array $logs)
    {
        assert_instances_of($logs, PhabricatorUserLog::class);
        $this->logs = $logs;
        return $this;
    }

    /**
     * @return AphrontTableView|string
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function render()
    {
        $logs = $this->logs;
        $viewer = $this->getUser();

        $phids = array();
        foreach ($logs as $log) {
            $phids[] = $log->getActorPHID();
            $phids[] = $log->getUserPHID();
        }
        $handles = $viewer->loadHandles($phids);

        $action_map = PhabricatorUserLog::getActionTypeMap();
        $base_uri = $this->searchBaseURI;

        $viewer_phid = $viewer->getPHID();

        $rows = array();
        foreach ($logs as $log) {
            $session = substr($log->getSession(), 0, 6);

            $actor_phid = $log->getActorPHID();
            $user_phid = $log->getUserPHID();

            if ($viewer->getIsAdmin()) {
                $can_see_ip = true;
            } else if ($viewer_phid == $actor_phid) {
                // You can see the address if you took the action.
                $can_see_ip = true;
            } else if (!$actor_phid && ($viewer_phid == $user_phid)) {
                // You can see the address if it wasn't authenticated and applied
                // to you (partial login).
                $can_see_ip = true;
            } else {
                // You can't see the address when an administrator disables your
                // account, since it's their address.
                $can_see_ip = false;
            }

            if ($can_see_ip) {
                $ip = $log->getRemoteAddr();
                if ($base_uri) {
                    $ip = JavelinHtml::phutil_tag(
                        'a',
                        array(
                            'href' => $base_uri . '?ip=' . $ip . '#R',
                        ),
                        $ip);
                }
            } else {
                $ip = null;
            }

            $action = $log->getAction();
            $action_name = ArrayHelper::getValue($action_map, $action, $action);

            if ($actor_phid) {
                $actor_name = $handles[$actor_phid]->renderLink();
            } else {
                $actor_name = null;
            }

            if ($user_phid) {
                $user_name = $handles[$user_phid]->renderLink();
            } else {
                $user_name = null;
            }

            $rows[] = array(
                OranginsViewUtil::phabricator_date($log->created_at, $viewer),
                OranginsViewUtil::phabricator_time($log->created_at, $viewer),
                $action_name,
                $actor_name,
                $user_name,
                $ip,
                $session,
            );
        }

        $table = new AphrontTableView($rows);
        $table->setHeaders(
            array(
                \Yii::t("app", 'Date'),
                \Yii::t("app", 'Time'),
                \Yii::t("app", 'Action'),
                \Yii::t("app", 'Actor'),
                \Yii::t("app", 'User'),
                \Yii::t("app", 'IP'),
                \Yii::t("app", 'Session'),
            ));
        $table->setColumnClasses(
            array(
                '',
                'right',
                'wide',
                '',
                '',
                '',
                'n',
            ));

        return $table;
    }
}
