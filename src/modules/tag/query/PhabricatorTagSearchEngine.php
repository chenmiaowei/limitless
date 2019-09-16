<?php

namespace orangins\modules\tag\query;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\view\form\control\AphrontFormDateRangeControlValue;
use orangins\lib\view\phui\PHUIBigInfoView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\file\view\PhabricatorGlobalUploadTargetView;
use orangins\modules\people\typeahead\PhabricatorPeopleUserFunctionDatasource;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\field\PhabricatorSearchDatasourceField;
use orangins\modules\search\field\PhabricatorSearchDateField;
use orangins\modules\search\field\PhabricatorSearchDateRangeControlField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use orangins\modules\tag\application\PhabricatorTagsApplication;
use orangins\modules\tag\models\PhabricatorTag;
use PhutilSafeHTML;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorTagSearchEngine
 * @package orangins\modules\file\query
 * @author 陈妙威
 */
final class PhabricatorTagSearchEngine extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app", 'Tags');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorTagsApplication::class;
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
     * @return \orangins\modules\tag\models\PhabricatorTagQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function newQuery()
    {
        $query = PhabricatorTag::find();
        return $query;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function buildCustomSearchFields()
    {
        return array(
            (new PhabricatorSearchDatasourceField())
                ->setLabel(\Yii::t("app", '用户名'))
                ->setKey('author_phid')
                ->setDatasource(new PhabricatorPeopleUserFunctionDatasource()),
            (new PhabricatorSearchDateRangeControlField())
                ->setLabel("时间")
                ->setKey('time_range')
                ->setTimeDisabled(true),
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
     * @return \orangins\modules\tag\models\PhabricatorTagQuery
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

        if ($map['author_phid']) {
            $query->withAuthorPHIDs($map['author_phid']);
        }

        $range_min = null;
        $range_max = null;

        /** @var AphrontFormDateRangeControlValue $range */
        $range = $map['time_range'];
        if ($range) {
            $range_min = $range->getStartValue()->getEpoch();
            $range_max = $range->getEndValue()->getEpoch();
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
        return Url::to(ArrayHelper::merge([
            '/tag/index/' . $path
        ], $params));
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
            $names['authored'] = \Yii::t("app", 'Authored');
        }

        $names += array(
            'all' => \Yii::t("app", 'All'),
        );

        return $names;
    }

    /**
     * @param $query_key
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception

     * @throws \ReflectionException
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
                $author_phid = array($this->requireViewer()->getPHID());
                return $query
                    ->setParameter('authorPHIDs', $author_phid)
                    ->setParameter('explicit', true);
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $files
     * @param PhabricatorSavedQuery $query
     * @return array
     * @author 陈妙威
     */
    protected function getRequiredHandlePHIDsForResultList(
        array $files,
        PhabricatorSavedQuery $query)
    {
        return OranginsUtil::mpull($files, 'getAuthorPHID');
    }

    /**
     * @param PhabricatorTag[] $files
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return PhabricatorApplicationSearchResultView|mixed
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \ReflectionException

     * @author 陈妙威
     */
    protected function renderResultList(
        array $files,
        PhabricatorSavedQuery $query,
        array $handles)
    {

        OranginsUtil::assert_instances_of($files, PhabricatorTag::className());

        $request = $this->getRequest();
        if ($request) {
            $highlighted_ids = $request->getStrList('h');
        } else {
            $highlighted_ids = array();
        }

        $viewer = $this->requireViewer();

        $highlighted_ids = array_fill_keys($highlighted_ids, true);

        $list_view = (new PHUIObjectItemListView())
            ->setViewer($viewer);

        foreach ($files as $file) {
            $id = $file->getID();
            $phid = $file->getPHID();
            $name = $file->name;
            $file_uri = $this->getApplicationURI("index/view", ['id' => $id]);

            $date_created = OranginsViewUtil::phabricator_date($file->created_at, $viewer);
            $author_phid = $file->getAuthorPHID();
            if ($author_phid) {
                $author_link = $handles[$author_phid]->renderLink();
                $uploaded = new PhutilSafeHTML(\Yii::t("app", 'Uploaded by {0} on {1}', [$author_link, $date_created]));
            } else {
                $uploaded = \Yii::t("app", 'Uploaded on {0}', [$date_created]);
            }

            $item = (new PHUIObjectItemView())
                ->setObject($file)
                ->setObjectName("T{$id}")
                ->setHeader($name)
                ->setHref($file_uri)
                ->addAttribute($uploaded);


            if (isset($highlighted_ids[$id])) {
                $item->setEffect('highlighted');
            }

            $list_view->addItem($item);
        }

        $list_view->appendChild((new PhabricatorGlobalUploadTargetView())
            ->setViewer($viewer));


        $result = new PhabricatorApplicationSearchResultView();
        $result->setContent($list_view);

        return $result;
    }

    /**
     * @return null
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function getNewUserBody()
    {
        $create_button = (new PHUIButtonView())
            ->setTag('a')
            ->setIcon('fa-plus')
            ->setText(\Yii::t("app", 'Create {0}', [\Yii::t("app", 'Tag')]))
            ->setHref(Url::to(['/file/index/upload']))
            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"));

        $icon = $this->getApplication()->getIcon();
        $app_name = $this->getApplication()->getName();
        $view = (new PHUIBigInfoView())
            ->setIcon($icon)
            ->setTitle(\Yii::t("app", 'Welcome to {0}', [$app_name]))
            ->setDescription(
                \Yii::t("app", 'Just a place for tags.'))
            ->addAction($create_button);

        return $view;
    }

}
