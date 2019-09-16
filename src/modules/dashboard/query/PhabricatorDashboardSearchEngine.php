<?php

namespace orangins\modules\dashboard\query;

use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\dashboard\application\PhabricatorDashboardApplication;
use orangins\modules\dashboard\models\PhabricatorDashboard;
use orangins\modules\people\typeahead\PhabricatorPeopleUserFunctionDatasource;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\field\PhabricatorSearchCheckboxesField;
use orangins\modules\search\field\PhabricatorSearchDatasourceField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorDashboardSearchEngine
 * @package orangins\modules\dashboard\query
 * @author 陈妙威
 */
final class PhabricatorDashboardSearchEngine extends PhabricatorApplicationSearchEngine
{


    /**
     * @param null $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge([
            '/dashboard/index/' . $path
        ], $params));
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app",'Dashboards');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorDashboardApplication::className();
    }

    /**
     * @return \orangins\lib\infrastructure\query\PhabricatorQuery|PhabricatorDashboardQuery
     * @author 陈妙威
     */
    public function newQuery()
    {
        return PhabricatorDashboard::find();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canUseInPanelContext()
    {
        return false;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function buildCustomSearchFields()
    {
        return array(
            (new PhabricatorSearchDatasourceField())
                ->setLabel(\Yii::t("app",'Authored By'))
                ->setKey('authorPHIDs')
                ->setDatasource(new PhabricatorPeopleUserFunctionDatasource()),
            (new PhabricatorSearchCheckboxesField())
                ->setKey('statuses')
                ->setLabel(\Yii::t("app",'Status'))
                ->setOptions(PhabricatorDashboard::getStatusNameMap()),
            (new PhabricatorSearchCheckboxesField())
                ->setKey('editable')
                ->setLabel(\Yii::t("app",'Editable'))
                ->setOptions(array('editable' => null)),
        );
    }


    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        $names = array();

        if ($this->requireViewer()->isLoggedIn()) {
            $names['authored'] = \Yii::t("app",'Authored');
        }

        $names['open'] = \Yii::t("app",'Active Dashboards');
        $names['all'] = \Yii::t("app",'All Dashboards');

        return $names;
    }

    /**
     * @param $query_key
     * @return mixed|PhabricatorSavedQuery
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function buildSavedQueryFromBuiltin($query_key)
    {
        $query = $this->newSavedQuery();
        $query->setQueryKey($query_key);
        $viewer = $this->requireViewer();

        switch ($query_key) {
            case 'all':
                return $query;
            case 'authored':
                return $query->setParameter(
                    'authorPHIDs',
                    array(
                        $viewer->getPHID(),
                    ));
            case 'open':
                return $query->setParameter(
                    'statuses',
                    array(
                        PhabricatorDashboard::STATUS_ACTIVE,
                    ));
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $map
     * @return \orangins\lib\infrastructure\query\PhabricatorQuery|PhabricatorDashboardQuery
     * @author 陈妙威
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

        if ($map['statuses']) {
            $query->withStatuses($map['statuses']);
        }

        if ($map['authorPHIDs']) {
            $query->withAuthorPHIDs($map['authorPHIDs']);
        }

        if ($map['editable'] !== null) {
            $query->withCanEdit($map['editable']);
        }

        return $query;
    }

    /**
     * @param array $dashboards
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return mixed|PhabricatorApplicationSearchResultView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function renderResultList(
        array $dashboards,
        PhabricatorSavedQuery $query,
        array $handles)
    {

        $viewer = $this->requireViewer();

        $phids = array();
        foreach ($dashboards as $dashboard) {
            $author_phid = $dashboard->getAuthorPHID();
            if ($author_phid) {
                $phids[] = $author_phid;
            }
        }

        $handles = $viewer->loadHandles($phids);

        if ($dashboards) {
//            $edge_query = (new PhabricatorEdgeQuery())
//                ->withSourcePHIDs(mpull($dashboards, 'getPHID'))
//                ->withEdgeTypes(
//                    array(
//                        PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
//                    ));
//
//            $edge_query->execute();
        }

        $list = (new PHUIObjectItemListView())
            ->setViewer($viewer);

        foreach ($dashboards as $dashboard) {
            $item = (new PHUIObjectItemView())
                ->setViewer($viewer)
                ->setObjectName($dashboard->getObjectName())
                ->setHeader($dashboard->getName())
                ->setHref($dashboard->getURI())
                ->setObject($dashboard);

            if ($dashboard->isArchived()) {
                $item->setDisabled(true);
                $bg_color = 'bg-grey';
            } else {
                $bg_color = 'bg-dark';
            }

            $icon = (new PHUIIconView())
                ->setIcon($dashboard->getIcon())
                ->addClass('mr-2')
                ->setBackground($bg_color);
            $item->setImageIcon($icon);
            $item->setEpoch($dashboard->updated_at);

            $author_phid = $dashboard->getAuthorPHID();
            $author_name = $handles[$author_phid]->renderLink();
            $item->addByline(new \PhutilSafeHTML(\Yii::t("app",'Author: {0}', [$author_name])));

            $phid = $dashboard->getPHID();
//            $project_phids = $edge_query->getDestinationPHIDs(array($phid));
//            $project_handles = $viewer->loadHandles($project_phids);

//            $item->addAttribute(
//                (new PHUIHandleTagListView())
//                    ->setLimit(4)
//                    ->setNoDataString(\Yii::t("app",'No Tags'))
//                    ->setSlim(true)
//                    ->setHandles($project_handles));

            $list->addItem($item);
        }

        $result = new PhabricatorApplicationSearchResultView();
        $result->setObjectList($list);
        $result->setNoDataString(\Yii::t("app",'No dashboards found.'));

        return $result;
    }


}
