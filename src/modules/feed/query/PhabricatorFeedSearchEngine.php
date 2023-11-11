<?php

namespace orangins\modules\feed\query;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\feed\builder\PhabricatorFeedBuilder;
use orangins\modules\feed\models\PhabricatorFeedQuery;
use orangins\modules\feed\models\PhabricatorFeedStoryData;
use orangins\modules\people\searchfield\PhabricatorUsersSearchField;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\exception\PhabricatorSearchConstraintException;
use orangins\modules\search\field\PhabricatorSearchCheckboxesField;
use orangins\modules\search\field\PhabricatorSearchDateControlField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use PhutilInvalidStateException;
use ReflectionException;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorFeedSearchEngine
 * @package orangins\modules\feed\query
 * @author 陈妙威
 */
final class PhabricatorFeedSearchEngine extends PhabricatorApplicationSearchEngine
{

    public $classes = [];

    /**
     * @param string $classes
     * @return self
     */
    public function addClass($classes)
    {
        $this->classes[] = $classes;
        return $this;
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return Yii::t("app", 'Feed Stories');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return 'PhabricatorFeedApplication';
    }

    /**
     * @return PhabricatorFeedQuery
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    public function newQuery()
    {
        return PhabricatorFeedStoryData::find();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function shouldShowOrderField()
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
            (new PhabricatorUsersSearchField())
                ->setLabel(Yii::t("app", 'Include Users'))
                ->setKey('userPHIDs'),
            // NOTE: This query is not executed with EdgeLogic, so we can't use
            // a fancy logical datasource.
//            (new PhabricatorSearchDatasourceField())
//                ->setDatasource(new PhabricatorProjectDatasource())
//                ->setLabel(\Yii::t("app",'Include Projects'))
//                ->setKey('projectPHIDs'),
            (new PhabricatorSearchDateControlField())
                ->setLabel(Yii::t("app", 'Occurs After'))
                ->setKey('rangeStart'),
            (new PhabricatorSearchDateControlField())
                ->setLabel(Yii::t("app", 'Occurs Before'))
                ->setKey('rangeEnd'),

            // NOTE: This is a legacy field retained only for backward
            // compatibility. If the projects field used EdgeLogic, we could use
            // `viewerprojects()` to execute an equivalent query.
            (new PhabricatorSearchCheckboxesField())
                ->setKey('viewerProjects')
                ->setOptions(
                    array(
                        'self' => Yii::t("app", 'Include stories about projects I am a member of.'),
                    )),
        );
    }

    /**
     * @param array $map
     * @return PhabricatorFeedQuery|null
     * @throws PhabricatorSearchConstraintException
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

        $phids = array();
        if ($map['userPHIDs']) {
            $phids += OranginsUtil::head_key(OranginsUtil::array_fuse($map['userPHIDs']));
        }


        if ($phids) {
            $query->withFilterPHIDs($phids);
        }

        $range_min = $map['rangeStart'];
        if ($range_min) {
            $range_min = $range_min->getEpoch();
        }

        $range_max = $map['rangeEnd'];
        if ($range_max) {
            $range_max = $range_max->getEpoch();
        }

        if ($range_min && $range_max) {
            if ($range_min > $range_max) {
                throw new PhabricatorSearchConstraintException(
                    Yii::t("app",
                        'The specified "Occurs Before" date is earlier in time than the ' .
                        'specified "Occurs After" date, so this query can never match ' .
                        'any results.'));
            }
        }

        if ($range_min || $range_max) {
            $query->withEpochInRange($range_min, $range_max);
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
        return Url::to(ArrayHelper::merge(['/feed/index/' . $path], $params));
    }

    /**
     * @return array
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        $names = array(
            'all' => Yii::t("app", 'All Stories'),
        );

        if ($this->requireViewer()->isLoggedIn()) {
            $names['projects'] = Yii::t("app", 'Tags');
        }

        return $names;
    }

    /**
     * @param $query_key
     * @return PhabricatorSavedQuery
     * @throws ReflectionException*@throws \Exception
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
            case 'projects':
                return $query->setParameter('viewerProjects', array('self'));
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $objects
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return PhabricatorApplicationSearchResultView|mixed
     * @throws PhutilInvalidStateException
     * @throws Exception
     * @author 陈妙威
     */
    protected function renderResultList(
        array $objects,
        PhabricatorSavedQuery $query,
        array $handles)
    {

        $builder = new PhabricatorFeedBuilder($objects);

        if ($this->isPanelContext()) {
            $builder->setShowHovercards(false);
        } else {
            $builder->setShowHovercards(true);
        }

        $builder->setUser($this->requireViewer());
        $view = $builder->buildView();

        $list = JavelinHtml::phutil_tag_div('phabricator-feed-frame ' . implode(' ', $this->classes), $view);

        $result = new PhabricatorApplicationSearchResultView();
        $result->setContent($list);

        return $result;
    }

}
