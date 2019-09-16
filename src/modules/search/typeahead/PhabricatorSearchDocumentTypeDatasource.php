<?php

namespace orangins\modules\search\typeahead;

use orangins\lib\helpers\OranginsUtil;
use orangins\lib\PhabricatorApplication;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\search\application\PhabricatorSearchApplication;
use orangins\modules\search\query\PhabricatorSearchApplicationSearchEngine;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorSearchDocumentTypeDatasource
 * @package orangins\modules\search\phid
 * @author 陈妙威
 */
final class PhabricatorSearchDocumentTypeDatasource
    extends PhabricatorTypeaheadDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app",'Browse Document Types');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app",'Select a document type...');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return PhabricatorSearchApplication::className();
    }

    /**
     * @return \orangins\modules\typeahead\model\PhabricatorTypeaheadResult[]|mixed
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function loadResults()
    {
        $results = $this->buildResults();
        return $this->filterResultsAgainstTokens($results);
    }

    /**
     * @param array $values
     * @return array|\orangins\modules\typeahead\view\PhabricatorTypeaheadTokenView[]
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function renderTokens(array $values)
    {
        return $this->renderTokensFromResults($this->buildResults(), $values);
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @throws \Exception
     */
    private function buildResults()
    {
        $viewer = $this->getViewer();
        $types =
            PhabricatorSearchApplicationSearchEngine::getIndexableDocumentTypes(
                $viewer);

        $phid_types = mpull(PhabricatorPHIDType::getAllTypes(),
            null,
            'getTypeConstant');

        $results = array();
        foreach ($types as $type => $name) {
            $type_object = ArrayHelper::getValue($phid_types, $type);
            if (!$type_object) {
                continue;
            }
            $application_class = $type_object->getPHIDTypeApplicationClass();
            $application = PhabricatorApplication::getByClass($application_class);
            $application_name = $application->getName();

            $results[$type] = (new PhabricatorTypeaheadResult())
                ->setPHID($type)
                ->setName($name)
                ->addAttribute($application_name)
                ->setIcon($type_object->getTypeIcon());
        }

        return $results;
    }

}
