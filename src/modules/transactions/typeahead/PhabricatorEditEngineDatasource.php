<?php

namespace orangins\modules\transactions\typeahead;

use orangins\modules\search\models\PhabricatorEditEngineConfiguration;
use orangins\modules\transactions\application\PhabricatorTransactionsApplication;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;

/**
 * Class PhabricatorEditEngineDatasource
 * @package orangins\modules\transactions\typeahead
 * @author 陈妙威
 */
final class PhabricatorEditEngineDatasource
    extends PhabricatorTypeaheadDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app",'Browse Forms');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app",'Type a form name...');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return PhabricatorTransactionsApplication::className();
    }

    /**
     * @param array $values
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function renderSpecialTokens(array $values)
    {
        return $this->renderTokensFromResults($this->buildResults(), $values);
    }

    /**
     * @return mixed|\orangins\modules\typeahead\model\PhabricatorTypeaheadResult[]
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function loadResults()
    {
        $results = $this->buildResults();
        return $this->filterResultsAgainstTokens($results);
    }

    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function buildResults()
    {
        $query = PhabricatorEditEngineConfiguration::find();

        /** @var PhabricatorEditEngineConfiguration[] $forms */
        $forms = $this->executeQuery($query);
        $results = array();
        foreach ($forms as $form) {
            $create_uri = $form->getCreateURI();
            if (!$create_uri) {
                continue;
            }

            if ($form->getID()) {
                $key = $form->getEngineKey() . '/' . $form->getID();
            } else {
                $key = $form->getEngineKey() . '/' . $form->getBuiltinKey();
            }

            $result = (new PhabricatorTypeaheadResult())
                ->setName($form->getName())
                ->setPHID($key)
                ->setIcon($form->getIcon());

            if ($form->getIsDisabled()) {
                $result->setClosed(\Yii::t("app",'Archived'));
            }

            if ($form->getIsDefault()) {
                $result->addAttribute(\Yii::t("app",'Create Form'));
            }

            if ($form->getIsEdit()) {
                $result->addAttribute(\Yii::t("app",'Edit Form'));
            }

            $results[$key] = $result;
        }

        return $results;
    }

}
