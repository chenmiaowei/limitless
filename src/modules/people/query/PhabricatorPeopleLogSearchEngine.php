<?php

namespace orangins\modules\people\query;

use orangins\lib\export\field\PhabricatorPHIDExportField;
use orangins\lib\export\field\PhabricatorStringExportField;
use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\people\models\PhabricatorUserLog;
use orangins\modules\people\searchfield\PhabricatorUsersSearchField;
use orangins\modules\people\view\PhabricatorUserLogView;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\field\PhabricatorSearchCheckboxesField;
use orangins\modules\search\field\PhabricatorSearchDateField;
use orangins\modules\search\field\PhabricatorSearchStringListField;
use orangins\modules\search\field\PhabricatorSearchTextField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorPeopleLogSearchEngine
 * @package orangins\modules\people\query
 * @author 陈妙威
 */
final class PhabricatorPeopleLogSearchEngine
    extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app",'Account Activity');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorPeopleApplication::class;
    }

    /**
     * @param PhabricatorSavedQuery $saved
     * @return int
     * @author 陈妙威
     */
    public function getPageSize(PhabricatorSavedQuery $saved)
    {
        return 500;
    }

    /**
     * @return null|PhabricatorPeopleLogQuery
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function newQuery()
    {
        $query = PhabricatorUserLog::find();

        // NOTE: If the viewer isn't an administrator, always restrict the query to
        // related records. This echoes the policy logic of these logs. This is
        // mostly a performance optimization, to prevent us from having to pull
        // large numbers of logs that the user will not be able to see and filter
        // them in-process.
        $viewer = $this->requireViewer();
        if (!$viewer->getIsAdmin()) {
            $query->withRelatedPHIDs(array($viewer->getPHID()));
        }

        return $query;
    }

    /**
     * @param array $map
     * @return null|PhabricatorPeopleLogQuery
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

        if ($map['userPHIDs']) {
            $query->withUserPHIDs($map['userPHIDs']);
        }

        if ($map['actorPHIDs']) {
            $query->withActorPHIDs($map['actorPHIDs']);
        }

        if ($map['actions']) {
            $query->withActions($map['actions']);
        }

        if (strlen($map['ip'])) {
            $query->withRemoteAddressPrefix($map['ip']);
        }

        if ($map['sessions']) {
            $query->withSessionKeys($map['sessions']);
        }

        if ($map['createdStart'] || $map['createdEnd']) {
            $query->withDateCreatedBetween(
                $map['createdStart'],
                $map['createdEnd']);
        }

        return $query;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function buildCustomSearchFields()
    {
        return array(
            (new PhabricatorUsersSearchField())
                ->setKey('userPHIDs')
                ->setAliases(array('users', 'user', 'userPHID'))
                ->setLabel(\Yii::t("app",'Users'))
                ->setDescription(\Yii::t("app",'Search for activity affecting specific users.')),
            (new PhabricatorUsersSearchField())
                ->setKey('actorPHIDs')
                ->setAliases(array('actors', 'actor', 'actorPHID'))
                ->setLabel(\Yii::t("app",'Actors'))
                ->setDescription(\Yii::t("app",'Search for activity by specific users.')),
            (new PhabricatorSearchCheckboxesField())
                ->setKey('actions')
                ->setLabel(\Yii::t("app",'Actions'))
                ->setDescription(\Yii::t("app",'Search for particular types of activity.'))
                ->setOptions(PhabricatorUserLog::getActionTypeMap()),
            (new PhabricatorSearchTextField())
                ->setKey('ip')
                ->setLabel(\Yii::t("app",'Filter IP'))
                ->setDescription(\Yii::t("app",'Search for actions by remote address.')),
            (new PhabricatorSearchStringListField())
                ->setKey('sessions')
                ->setLabel(\Yii::t("app",'Sessions'))
                ->setDescription(\Yii::t("app",'Search for activity in particular sessions.')),
            (new PhabricatorSearchDateField())
                ->setLabel(\Yii::t("app",'Created After'))
                ->setKey('createdStart'),
            (new PhabricatorSearchDateField())
                ->setLabel(\Yii::t("app",'Created Before'))
                ->setKey('createdEnd'),
        );
    }

    /**
     * @param null $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge(['/people/index/' . $path], $params));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        $names = array(
            'all' => \Yii::t("app",'All'),
        );

        return $names;
    }

    /**
     * @param $query_key
     * @return mixed|PhabricatorSavedQuery
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildSavedQueryFromBuiltin($query_key)
    {
        $query = $this->newSavedQuery();
        $query->setQueryKey($query_key);

        switch ($query_key) {
            case 'all':
                return $query;
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $logs
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return mixed|PhabricatorApplicationSearchResultView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function renderResultList(
        array $logs,
        PhabricatorSavedQuery $query,
        array $handles)
    {
        assert_instances_of($logs, PhabricatorUserLog::class);

        $viewer = $this->requireViewer();

        $table = (new PhabricatorUserLogView())
            ->setUser($viewer)
            ->setLogs($logs);

        if ($viewer->getIsAdmin()) {
            $table->setSearchBaseURI($this->getApplicationURI('logs/index'));
        }

        return (new PhabricatorApplicationSearchResultView())
            ->setTable($table);
    }

    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function newExportFields()
    {
        $viewer = $this->requireViewer();

        $fields = array(
            $fields[] = (new PhabricatorPHIDExportField())
                ->setKey('actorPHID')
                ->setLabel(\Yii::t("app",'Actor PHID')),
            $fields[] = (new PhabricatorStringExportField())
                ->setKey('actor')
                ->setLabel(\Yii::t("app",'Actor')),
            $fields[] = (new PhabricatorPHIDExportField())
                ->setKey('userPHID')
                ->setLabel(\Yii::t("app",'User PHID')),
            $fields[] = (new PhabricatorStringExportField())
                ->setKey('user')
                ->setLabel(\Yii::t("app",'User')),
            $fields[] = (new PhabricatorStringExportField())
                ->setKey('action')
                ->setLabel(\Yii::t("app",'Action')),
            $fields[] = (new PhabricatorStringExportField())
                ->setKey('actionName')
                ->setLabel(\Yii::t("app",'Action Name')),
            $fields[] = (new PhabricatorStringExportField())
                ->setKey('session')
                ->setLabel(\Yii::t("app",'Session')),
            $fields[] = (new PhabricatorStringExportField())
                ->setKey('old')
                ->setLabel(\Yii::t("app",'Old Value')),
            $fields[] = (new PhabricatorStringExportField())
                ->setKey('new')
                ->setLabel(\Yii::t("app",'New Value')),
        );

        if ($viewer->getIsAdmin()) {
            $fields[] = (new PhabricatorStringExportField())
                ->setKey('remoteAddress')
                ->setLabel(\Yii::t("app",'Remote Address'));
        }

        return $fields;
    }

    /**
     * @param array $logs
     * @return array
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function newExportData(array $logs)
    {
        $viewer = $this->requireViewer();


        $phids = array();
        foreach ($logs as $log) {
            $phids[] = $log->getUserPHID();
            $phids[] = $log->getActorPHID();
        }
        $handles = $viewer->loadHandles($phids);

        $action_map = PhabricatorUserLog::getActionTypeMap();

        $export = array();
        foreach ($logs as $log) {

            $user_phid = $log->getUserPHID();
            if ($user_phid) {
                $user_name = $handles[$user_phid]->getName();
            } else {
                $user_name = null;
            }

            $actor_phid = $log->getActorPHID();
            if ($actor_phid) {
                $actor_name = $handles[$actor_phid]->getName();
            } else {
                $actor_name = null;
            }

            $action = $log->getAction();
            $action_name = ArrayHelper::getValue($action_map, $action, \Yii::t("app",'Unknown ("%s")', $action));

            $map = array(
                'actorPHID' => $actor_phid,
                'actor' => $actor_name,
                'userPHID' => $user_phid,
                'user' => $user_name,
                'action' => $action,
                'actionName' => $action_name,
                'session' => substr($log->getSession(), 0, 6),
                'old' => $log->getOldValue(),
                'new' => $log->getNewValue(),
            );

            if ($viewer->getIsAdmin()) {
                $map['remoteAddress'] = $log->getRemoteAddr();
            }

            $export[] = $map;
        }

        return $export;
    }

}
