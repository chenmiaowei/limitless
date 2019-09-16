<?php

namespace orangins\modules\people\typeahead;

use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\view\PhabricatorTypeaheadTokenView;

/**
 * Class PhabricatorViewerDatasource
 * @package orangins\modules\people\typeahead
 * @author 陈妙威
 */
final class PhabricatorViewerDatasource
    extends PhabricatorTypeaheadDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app",'Browse Viewer');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app",'Type viewer()...');
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
     * @return array
     * @author 陈妙威
     */
    public function getDatasourceFunctions()
    {
        return array(
            'viewer' => array(
                'name' => \Yii::t("app",'Current Viewer'),
                'summary' => \Yii::t("app",'Use the current viewing user.'),
                'description' => \Yii::t("app",
                    "This function allows you to change the behavior of a query " .
                    "based on who is running it. When you use this function, you will " .
                    "be the current viewer, so it works like you typed your own " .
                    "username.\n\n" .
                    "However, if you save a query using this function and send it " .
                    "to someone else, it will work like //their// username was the " .
                    "one that was typed. This can be useful for building dashboard " .
                    "panels that always show relevant information to the user who " .
                    "is looking at them."),
            ),
        );
    }

    /**
     * @return \orangins\modules\typeahead\datasource\list|mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function loadResults()
    {
        if ($this->getViewer()->getPHID()) {
            $results = array($this->renderViewerFunctionToken());
        } else {
            $results = array();
        }

        return $this->filterResultsAgainstTokens($results);
    }

    /**
     * @param $function
     * @return bool
     * @author 陈妙威
     */
    protected function canEvaluateFunction($function)
    {
        if (!$this->getViewer()->getPHID()) {
            return false;
        }

        return parent::canEvaluateFunction($function);
    }

    /**
     * @param $function
     * @param array $argv_list
     * @return array
     * @author 陈妙威
     */
    protected function evaluateFunction($function, array $argv_list)
    {
        $results = array();
        foreach ($argv_list as $argv) {
            $results[] = $this->getViewer()->getPHID();
        }
        return $results;
    }

    /**
     * @param $function
     * @param array $argv_list
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function renderFunctionTokens($function, array $argv_list)
    {
        $tokens = array();
        foreach ($argv_list as $argv) {
            $tokens[] = PhabricatorTypeaheadTokenView::newFromTypeaheadResult(
                $this->renderViewerFunctionToken());
        }
        return $tokens;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function renderViewerFunctionToken()
    {
        return $this->newFunctionResult()
            ->setName(\Yii::t("app",'Current Viewer'))
            ->setPHID('viewer()')
            ->setIcon('fa-user')
            ->setUnique(true)
            ->addAttribute(\Yii::t("app",'Select current viewer.'));
    }

}
