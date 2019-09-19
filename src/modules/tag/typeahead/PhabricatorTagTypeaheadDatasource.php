<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 3:11 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\tag\typeahead;


use orangins\modules\tag\application\PhabricatorTagsApplication;
use orangins\modules\tag\models\PhabricatorTag;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;

/**
 * Class PhabricatorTaskTagLocalDatasource
 * @author 陈妙威
 */
class PhabricatorTagTypeaheadDatasource extends PhabricatorTypeaheadDatasource
{
    /**
     * @var
     */
    public $tagType;

    /**
     * @return mixed
     */
    public function getTagType()
    {
        return $this->tagType;
    }

    /**
     * @param mixed $tagType
     * @return self
     */
    public function setTagType($tagType)
    {
        $this->tagType = $tagType;
        return $this;
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app", 'Browse Tags');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return PhabricatorTagsApplication::class;
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
        $query = PhabricatorTag::find();
        $query->andWhere(['type' => $this->tagType]);

        /** @var PhabricatorTag[] $dashboards */
        $dashboards = $this->executeQuery($query);
        $results = array();
        foreach ($dashboards as $dashboard) {
            $result = (new PhabricatorTypeaheadResult())
                ->setName($dashboard->name)
                ->setPHID($dashboard->phid)
                ->addAttribute(\Yii::t("app",'Tag'));
            $results[] = $result;
        }

        return $this->filterResultsAgainstTokens($results);
    }
}