<?php

namespace orangins\modules\conduit\typeahead;

use orangins\modules\conduit\method\ConduitAPIMethod;
use orangins\modules\conduit\query\PhabricatorConduitMethodQuery;
use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;

/**
 * Class PhabricatorPeopleDatasource
 * @package orangins\modules\people\typeahead
 * @author 陈妙威
 */
final class PhabricatorConduitDatasource extends PhabricatorTypeaheadDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app", '用户浏览');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app", '请输入接口名称');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return PhabricatorPeopleApplication::class;
    }

    /**
     * @return array|mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function loadResults()
    {
        $viewer = $this->getViewer();

        $query = (new PhabricatorConduitMethodQuery());

        if ($this->getPhase() == self::PHASE_PREFIX) {
            $prefix = $this->getPrefixQuery();
            $query->withNameContains(array($prefix));
        } else {
            $tokens = $this->getTokens();
            if ($tokens) {
                $query->withNameContains($tokens);
            }
        }
        /** @var ConduitAPIMethod[] $users */
        $users = $this->executeQuery($query);

        $is_browse = $this->getIsBrowse();

        /** @var PhabricatorObjectHandle[] $handles */
        $handles = [];
        if ($is_browse && $users) {
            $phids = mpull($users, 'getPHID');
            $handles = (new PhabricatorHandleQuery())
                ->setViewer($viewer)
                ->withPHIDs($phids)
                ->execute();
        }

        $results = array();
        foreach ($users as $user) {
            $phid = $user->getPHID();
            $username = $user->getAPIMethodName();

            $result = (new PhabricatorTypeaheadResult())
                ->setName($user->getMethodDescription())
                ->setPHID($phid)
                ->setPriorityString($username)
                ->setPriorityType('user')
                ->setAutocomplete('@' . $username);


            if ($is_browse) {
                $handle = $handles[$phid];

                $result
                    ->setIcon($handle->getIcon())
                    ->setImageURI($handle->getImageURI())
                    ->addAttribute($handle->getSubtitle());
            }
            $results[] = $result;
        }
        return $results;
    }
}
