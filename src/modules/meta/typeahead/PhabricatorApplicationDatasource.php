<?php

namespace orangins\modules\meta\typeahead;

use orangins\lib\PhabricatorApplication;
use orangins\modules\meta\application\PhabricatorApplicationsApplication;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;

/**
 * Class PhabricatorApplicationDatasource
 * @package orangins\modules\meta\typeahead
 * @author 陈妙威
 */
final class PhabricatorApplicationDatasource extends PhabricatorTypeaheadDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app",'Browse Applications');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app",'Type an application name...');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return PhabricatorApplicationsApplication::className();
    }

    /**
     * @return \orangins\modules\typeahead\model\PhabricatorTypeaheadResult[]|mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function loadResults()
    {
        $viewer = $this->getViewer();
        $raw_query = $this->getRawQuery();

        $results = array();

        $applications = PhabricatorApplication::getAllInstalledApplications();
        foreach ($applications as $application) {
            $uri = $application->getTypeaheadURI();
            if (!$uri) {
                continue;
            }
            $is_installed = PhabricatorApplication::isClassInstalledForViewer(
                get_class($application),
                $viewer);
            if (!$is_installed) {
                continue;
            }
            $name = $application->getName() . ' ' . $application->getShortDescription();
            $img = 'phui-font-fa phui-icon-view ' . $application->getIcon();
            $results[] = (new PhabricatorTypeaheadResult())
                ->setName($name)
                ->setURI($uri)
                ->setPHID($application->getPHID())
                ->setPriorityString($application->getName())
                ->setDisplayName($application->getName())
                ->setDisplayType($application->getShortDescription())
                ->setPriorityType('apps')
                ->setImageSprite('phabricator-search-icon ' . $img)
                ->setIcon($application->getIcon())
                ->addAttribute($application->getShortDescription());
        }

        return $this->filterResultsAgainstTokens($results);
    }

}
