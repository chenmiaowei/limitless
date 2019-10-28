<?php

namespace orangins\modules\herald\query;

use Exception;
use orangins\modules\herald\application\PhabricatorHeraldApplication;
use orangins\modules\herald\view\HeraldConditionTableView;
use orangins\modules\herald\models\HeraldCondition;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\field\PhabricatorSearchTextField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use ReflectionException;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorXgbzxrSearchEngine
 * @package orangins\modules\file\query
 * @author 陈妙威
 */
final class HeraldConditionSearchEngine extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return Yii::t("app", 'Herald Condition');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorHeraldApplication::className();
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
     * @throws InvalidConfigException
     * @return HeraldConditionQuery     */
    public function newQuery()
    {
        $query = HeraldCondition::find();
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
                ->setKey('id')
                ->setLabel(Yii::t("app", 'Id')),
            (new PhabricatorSearchTextField())
                ->setKey('rule_id')
                ->setLabel(Yii::t("app", 'Rule Id')),
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
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     * @return HeraldConditionQuery     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

        if ($map['id']) {
            $query->withId($map['id']);
        }
        if ($map['rule_id']) {
            $query->withRule_id($map['rule_id']);
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
            '/herald/herald-condition/' . $path
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
            'all' => Yii::t("app", 'All'),
        );
        return $names;
    }

    /**
    * @param $query_key
    * @return mixed
    * @throws ReflectionException
    * @throws Exception
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
     * @param HeraldCondition[] $files
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return PhabricatorApplicationSearchResultView
     */
    protected function renderResultList(
        array $files,
        PhabricatorSavedQuery $query,
        array $handles)
    {

        OranginsUtil::assert_instances_of($files, HeraldCondition::className());

        $tableView = new HeraldConditionTableView();
        $tableView->setItems($files);
        $tableView->setNoDataString(Yii::t("app", 'No Data'));

        $result = new PhabricatorApplicationSearchResultView();
        $result->setTable($tableView);

        return $result;
    }
}

