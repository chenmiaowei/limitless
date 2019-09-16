<?php

namespace orangins\modules\dashboard\query;

use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\dashboard\application\PhabricatorDashboardApplication;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\people\typeahead\PhabricatorPeopleUserFunctionDatasource;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\field\PhabricatorSearchDatasourceField;
use orangins\modules\search\field\PhabricatorSearchSelectField;
use orangins\modules\search\field\PhabricatorSearchTextField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorDashboardPanelSearchEngine
 * @package orangins\modules\dashboard\query
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelSearchEngine
    extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app", 'Dashboard Panels');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorDashboardApplication::class;
    }

    /**
     * @return null|PhabricatorDashboardPanelQuery
     * @author 陈妙威
     */
    public function newQuery()
    {
        return PhabricatorDashboardPanel::find();
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
     * @param array $map
     * @return null|PhabricatorDashboardPanelQuery|void
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();
        if ($map['status']) {
            switch ($map['status']) {
                case 'active':
                    $query->withArchived(false);
                    break;
                case 'archived':
                    $query->withArchived(true);
                    break;
                default:
                    break;
            }
        }

        if ($map['paneltype']) {
            $query->withPanelTypes(array($map['paneltype']));
        }

        if ($map['authorPHIDs']) {
            $query->withAuthorPHIDs($map['authorPHIDs']);
        }

        if ($map['name'] !== null) {
            $query->withNameNgrams($map['name']);
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
             (new PhabricatorSearchTextField())
                ->setLabel(\Yii::t("app", 'Name Contains'))
                ->setKey('name')
                ->setDescription(\Yii::t("app", 'Search for panels by name substring.')),
             (new PhabricatorSearchDatasourceField())
                ->setLabel(\Yii::t("app", 'Authored By'))
                ->setKey('authorPHIDs')
                ->setDatasource(new PhabricatorPeopleUserFunctionDatasource()),
             (new PhabricatorSearchSelectField())
                ->setKey('status')
                ->setLabel(\Yii::t("app", 'Status'))
                ->setOptions(
                     (new PhabricatorDashboardPanel())
                        ->getStatuses()),
             (new PhabricatorSearchSelectField())
                ->setKey('paneltype')
                ->setLabel(\Yii::t("app", 'Panel Type'))
                ->setOptions(
                     (new PhabricatorDashboardPanel())
                        ->getPanelTypes()),
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
        return Url::to(ArrayHelper::merge(['/dashboard/index/' . $path], $params));
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
            $names['authored'] = \Yii::t("app", 'Authored');
        }

        $names['active'] = \Yii::t("app", 'Active Panels');
        $names['all'] = \Yii::t("app", 'All Panels');

        return $names;
    }

    /**
     * @param $query_key
     * @return mixed|\orangins\modules\search\models\PhabricatorSavedQuery
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildSavedQueryFromBuiltin($query_key)
    {
        $query = $this->newSavedQuery();
        $query->setQueryKey($query_key);
        $viewer = $this->requireViewer();

        switch ($query_key) {
            case 'active':
                return $query->setParameter('status', 'active');
            case 'authored':
                return $query->setParameter(
                    'authorPHIDs',
                    array(
                        $viewer->getPHID(),
                    ));
            case 'all':
                return $query;
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $panels
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return mixed|PhabricatorApplicationSearchResultView
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function renderResultList(
        array $panels,
        PhabricatorSavedQuery $query,
        array $handles)
    {

        $viewer = $this->requireViewer();

        $list = new PHUIObjectItemListView();
        $list->setUser($viewer);
        foreach ($panels as $panel) {
            $item =  (new PHUIObjectItemView())
                ->setObjectName($panel->getMonogram())
                ->setHeader($panel->getName())
                ->setHref('/' . $panel->getMonogram())
                ->setObject($panel);

            $impl = $panel->getImplementation();
            if ($impl) {
                $type_text = $impl->getPanelTypeName();
            } else {
                $type_text = nonempty($panel->getPanelType(), \Yii::t("app", 'Unknown Type'));
            }
            $item->addAttribute($type_text);

            $properties = $panel->getProperties();
            $class = ArrayHelper::getValue($properties, 'class');
            $item->addAttribute($class);

            if ($panel->getIsArchived()) {
                $item->setDisabled(true);
            }

            $list->addItem($item);
        }

        $result = new PhabricatorApplicationSearchResultView();
        $result->setObjectList($list);
        $result->setNoDataString(\Yii::t("app", 'No panels found.'));

        return $result;
    }

}
