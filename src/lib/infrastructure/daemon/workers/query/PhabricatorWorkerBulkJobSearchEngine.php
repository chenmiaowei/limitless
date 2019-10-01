<?php

namespace orangins\lib\infrastructure\daemon\workers\query;

use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJob;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\daemon\application\PhabricatorDaemonsApplication;
use orangins\modules\people\searchfield\PhabricatorUsersSearchField;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorWorkerBulkJobSearchEngine
 * @package orangins\lib\infrastructure\daemon\workers\query
 * @author 陈妙威
 */
final class PhabricatorWorkerBulkJobSearchEngine
    extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app",'Daemon Bulk Jobs');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorDaemonsApplication::className();
    }

    /**
     * @return PhabricatorWorkerBulkJobQuery
     * @author 陈妙威
     */
    public function newQuery()
    {
        return PhabricatorWorkerBulkJob::find();
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
     * @return PhabricatorWorkerBulkJobQuery
     * @author 陈妙威
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

        if ($map['authorPHIDs']) {
            $query->withAuthorPHIDs($map['authorPHIDs']);
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
            (new PhabricatorUsersSearchField())
                ->setLabel(\Yii::t("app",'Authors'))
                ->setKey('authorPHIDs')
                ->setAliases(array('author', 'authors')),
        );
    }


    /**
     * @param $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge(['/daemon/bulk/' . $path], $params));
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
            $names['authored'] = \Yii::t("app",'Authored Jobs');
        }

        $names['all'] = \Yii::t("app",'All Jobs');

        return $names;
    }

    /**
     * @param $query_key
     * @return mixed|\orangins\modules\search\models\PhabricatorSavedQuery
     * @throws \PhutilInvalidStateException
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
            case 'authored':
                return $query->setParameter(
                    'authorPHIDs',
                    array($this->requireViewer()->getPHID()));
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $jobs
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return mixed|PhabricatorApplicationSearchResultView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function renderResultList(
        array $jobs,
        PhabricatorSavedQuery $query,
        array $handles)
    {
        assert_instances_of($jobs, PhabricatorWorkerBulkJob::className());

        $viewer = $this->requireViewer();

        $list = (new PHUIObjectItemListView())
            ->setUser($viewer);
        foreach ($jobs as $job) {
            $size = \Yii::t("app",'{0} Bulk Task(s)', $job->getSize());

            $item = (new PHUIObjectItemView())
                ->setObjectName(\Yii::t("app",'Bulk Job {0}', [$job->getID()]))
                ->setHeader($job->getJobName())
                ->addAttribute(OranginsViewUtil::phabricator_datetime($job->created_at, $viewer))
                ->setHref($job->getManageURI())
                ->addIcon($job->getStatusIcon(), $job->getStatusName())
                ->addIcon('none', $size);

            $list->addItem($item);
        }

        return (new PhabricatorApplicationSearchResultView())
            ->setContent($list);
    }
}
