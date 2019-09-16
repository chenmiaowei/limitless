<?php

namespace orangins\modules\file\query;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\view\phui\PHUIBigInfoView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\file\application\PhabricatorFilesApplication;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\view\PhabricatorGlobalUploadTargetView;
use orangins\modules\people\searchfield\PhabricatorUsersSearchField;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\field\PhabricatorSearchDateField;
use orangins\modules\search\field\PhabricatorSearchTextField;
use orangins\modules\search\field\PhabricatorSearchThreeStateField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use PhutilSafeHTML;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorFileSearchEngine
 * @package orangins\modules\file\query
 * @author 陈妙威
 */
final class PhabricatorFileSearchEngine extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app", 'Files');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorFilesApplication::class;
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
     * @return \orangins\modules\file\models\PhabricatorFileQuery|null
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function newQuery()
    {
        $query = PhabricatorFile::find();
        $query->withIsDeleted(false);
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
                ->setKey('authorPHIDs')
                ->setAliases(array('author', 'authors'))
                ->setLabel(\Yii::t("app", 'Authors')),
            (new PhabricatorSearchThreeStateField())
                ->setKey('explicit')
                ->setLabel(\Yii::t("app", 'Upload Source'))
                ->setOptions(
                    \Yii::t("app", '(Show All)'),
                    \Yii::t("app", 'Show Only Manually Uploaded Files'),
                    \Yii::t("app", 'Hide Manually Uploaded Files')),
            (new PhabricatorSearchDateField())
                ->setKey('createdStart')
                ->setLabel(\Yii::t("app", 'Created After')),
            (new PhabricatorSearchDateField())
                ->setKey('createdEnd')
                ->setLabel(\Yii::t("app", 'Created Before')),
            (new PhabricatorSearchTextField())
                ->setLabel(\Yii::t("app", 'Name Contains'))
                ->setKey('name')
                ->setDescription(\Yii::t("app", 'Search for files by name substring.')),
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
     * @return \orangins\modules\file\models\PhabricatorFileQuery
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

        if ($map['authorPHIDs']) {
            $query->withAuthorPHIDs($map['authorPHIDs']);
        }

        if ($map['explicit'] !== null) {
            $query->showOnlyExplicitUploads($map['explicit']);
        }

        if ($map['createdStart']) {
            $query->withDateCreatedAfter($map['createdStart']);
        }

        if ($map['createdEnd']) {
            $query->withDateCreatedBefore($map['createdEnd']);
        }

        if ($map['name'] !== null) {
            $query->withNameNgrams($map['name']);
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
            '/file/index/' . $path
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
     * @param PhabricatorFile[] $files
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

        OranginsUtil::assert_instances_of($files, PhabricatorFile::className());

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
            $name = $file->getName();
            $file_uri = $this->getApplicationURI("index/info", ['phid' => $phid]);

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
                ->setObjectName("F{$id}")
                ->setHeader($name)
                ->setHref($file_uri)
                ->addAttribute($uploaded)
                ->addIcon('none', OranginsViewUtil::phutil_format_bytes($file->byte_size));

            $ttl = $file->ttl;
            if ($ttl !== null) {
                $item->addIcon('blame', \Yii::t("app", 'Temporary'));
            }

            if ($file->is_partial) {
                $item->addIcon('fa-exclamation-triangle orange', \Yii::t("app", 'Partial'));
            }

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
            ->setText(\Yii::t("app", 'Upload a File'))
            ->setHref(Url::to(['/file/index/upload']))
            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"));

        $icon = $this->getApplication()->getIcon();
        $app_name = $this->getApplication()->getName();
        $view = (new PHUIBigInfoView())
            ->setIcon($icon)
            ->setTitle(\Yii::t("app", 'Welcome to {0}', [$app_name]))
            ->setDescription(
                \Yii::t("app", 'Just a place for files.'))
            ->addAction($create_button);

        return $view;
    }

}
