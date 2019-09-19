<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 3:11 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\rbac\typeahead;


use orangins\modules\rbac\application\PhabricatorRBACApplication;
use orangins\modules\rbac\models\RbacRole;
use orangins\modules\tag\models\PhabricatorTag;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;

/**
 * Class PhabricatorTaskTagLocalDatasource
 * @author 陈妙威
 */
class PhabricatorRBACNodeDatasource extends PhabricatorTypeaheadDatasource
{
    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app", '浏览权限节点');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return PhabricatorRBACApplication::className();
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function loadResults()
    {
        $query = RbacRole::find();

        /** @var PhabricatorTag[] $dashboards */
        $dashboards = $this->executeQuery($query);
        $results = array();
        foreach ($dashboards as $dashboard) {
            $result = (new PhabricatorTypeaheadResult())
                ->setName($dashboard->name)
                ->setPHID($dashboard->phid)
                ->addAttribute(\Yii::t("app",'权限节点'));
            $results[] = $result;
        }

        return $this->filterResultsAgainstTokens($results);
    }
}