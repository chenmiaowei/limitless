<?php

namespace orangins\modules\rbac\query;

use orangins\lib\helpers\OranginsUtil;
use orangins\lib\view\phui\PHUIBigInfoView;
use orangins\modules\rbac\application\PhabricatorRBACApplication;
use orangins\modules\rbac\models\RbacRole;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\field\PhabricatorSearchTextField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorSxbzxrSearchEngine
 * @package orangins\modules\file\query
 * @author 陈妙威
 */
final class PhabricatorRBACRoleSearchEngine extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app", '角色');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorRBACApplication::class;
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
     * @return \orangins\modules\rbac\models\PhabricatorRBACRoleQuery
     * @author 陈妙威
     */
    public function newQuery()
    {
        $query = RbacRole::find();
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
                ->setKey('name')
                ->setLabel(\Yii::t("app", '角色名称')),
            (new PhabricatorSearchTextField())
                ->setKey('description')
                ->setLabel(\Yii::t("app", '角色描述')),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getDefaultFieldOrder()
    {
        return array(
            '...',
            'createdStart',
            'createdEnd',
        );
    }

    /**
     * @param array $map
     * @return \orangins\modules\rbac\models\PhabricatorRBACRoleQuery
     * @author 陈妙威
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();
        if ($map['name']) {
            $query->withName($map['name']);
        }
        if ($map['description']) {
            $query->withDescription($map['description']);
        }
        return $query;
    }

    /**
     * @param $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge([
            '/rbac/role/' . $path
        ], $params));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        $names = array();
        $names += array(
            'all' => \Yii::t("app", 'All'),
        );
        return $names;
    }

    /**
     * @param $query_key
     * @return mixed
     * @throws \ReflectionException
     * @throws \Exception
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
     * @param RbacRole[] $files
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return PhabricatorApplicationSearchResultView|mixed
     * @author 陈妙威
     */
    protected function renderResultList(
        array $files,
        PhabricatorSavedQuery $query,
        array $handles)
    {

        OranginsUtil::assert_instances_of($files, RbacRole::className());

        $tableView = new PhabricatorRBACRoleTableView();
        $tableView->setTasks($files);
        $tableView->setNoDataString(\Yii::t("app", '限高被执行人为空。'));

        $result = new PhabricatorApplicationSearchResultView();
        $result->setTable($tableView);

        return $result;
    }

    /**
     * @return null
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getNewUserBody()
    {
        $icon = $this->getApplication()->getIcon();
        $view = (new PHUIBigInfoView())
            ->setIcon($icon)
            ->setTitle(\Yii::t("app", '暂无数据'));

        return $view;
    }
}
