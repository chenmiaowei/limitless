<?php

namespace orangins\modules\userservice\query;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\view\form\control\AphrontFormDateRangeControlValue;
use orangins\lib\view\phui\PHUIBigInfoView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\modules\people\searchfield\PhabricatorUsersSearchField;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\field\PhabricatorSearchDateRangeControlField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use orangins\modules\userservice\application\PhabricatorUserServiceApplication;
use orangins\modules\userservice\assets\JavelinUserSereviceBatchSelectorBehaviorAsset;
use orangins\modules\userservice\models\PhabricatorUserService;
use orangins\modules\userservice\view\PhabricatorUserServiceTableView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorUserServiceSearchEngine
 * @package orangins\modules\file\query
 * @author 陈妙威
 */
final class PhabricatorUserServiceSearchEngine extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app", 'Tasks');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorUserServiceApplication::class;
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
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function newQuery()
    {
        $query = PhabricatorUserService::find()->andWhere(['!=', 'status', PhabricatorUserService::STATUS_DISABLE]);
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
                ->setKey('user_phid')
                ->setLabel(\Yii::t("app", '用户')),
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
     * @return \orangins\modules\userservice\models\PhabricatorUserServiceQuery
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();
        if ($map['user_phid']) {
            $query->withUserPHIDs($map['user_phid']);
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
            '/userservice/index/' . $path
        ], $params));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        $names = array();
        $names += array(
            'all' => '用户服务',
        );

        return $names;
    }

    /**
     * @param $query_key
     * @return mixed
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
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param PhabricatorUserService[] $files
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return PhabricatorApplicationSearchResultView|mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function renderResultList(
        array $files,
        PhabricatorSavedQuery $query,
        array $handles)
    {

        OranginsUtil::assert_instances_of($files, PhabricatorUserService::className());

        $tableView = new PhabricatorUserServiceTableView();
        $tableView->setViewer($this->getAction()->getViewer());
        $tableView->setTasks($files);
        $tableView->setNoDataString(\Yii::t("app", '用户数据服务为空。'));

        $result = new PhabricatorApplicationSearchResultView();
        $result->setTable($tableView);
        $result->setFooter($this->renderBatchEditor());

        return $result;
    }

    /**
     * @return null
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getNewUserBody()
    {
        $create_button = (new PHUIButtonView())
            ->setTag('a')
            ->setIcon('fa-plus')
            ->setText(\Yii::t("app", 'Create {0}', [\Yii::t("app", "用户数据服务")]))
            ->setHref(Url::to(['/userservice/index/create']))
            ->setWorkflow(true)
            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"));

        $icon = $this->getApplication()->getIcon();
        $app_name = $this->getApplication()->getName();
        $view = (new PHUIBigInfoView())
            ->setIcon($icon)
            ->setTitle(\Yii::t("app", 'Welcome to {0}', [$app_name]))
            ->addAction($create_button);

        return $view;
    }


    /**
     * @return null|\PhutilSafeHTML
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderBatchEditor()
    {
        $user = $this->requireViewer();

        if (!$user->isLoggedIn()) {
            // Don't show the batch editor for logged-out users.
            return null;
        }

        JavelinHtml::initBehavior(
            new JavelinUserSereviceBatchSelectorBehaviorAsset(),
            array(
                'selectAll' => 'batch-select-all',
                'selectNone' => 'batch-select-none',
                'submit' => 'batch-select-submit',
                'status' => 'batch-select-status-cell',
                'idContainer' => 'batch-select-id-container',
                'formID' => 'batch-select-form',
            ));

        $select_all = JavelinHtml::phutil_tag(
            'a',
            array(
                'href' => '#',
                'mustcapture' => true,
                'class' => 'btn btn-sm bg-' . PhabricatorEnv::getEnvConfig("ui.widget-color"),
                'id' => 'batch-select-all',
            ), '全部选中');

        $select_none = JavelinHtml::phutil_tag(
            'a',
            array(
                'href' => '#',
                'mustcapture' => true,
                'class' => "btn btn-sm bg-" . PhabricatorEnv::getEnvConfig("ui.widget-color") . " ml-1 mr-auto",
                'id' => 'batch-select-none',
            ), '全部不选');

        $submit = JavelinHtml::phutil_tag(
            'button',
            array(
                'id' => 'batch-select-submit',
                'disabled' => 'disabled',
                'class' => "btn btn-sm bg-" . PhabricatorEnv::getEnvConfig("ui.widget-color") . ' disabled ml-2',
            ), "批量处理");

        $hidden = JavelinHtml::phutil_tag(
            'div',
            array(
                'id' => 'batch-select-id-container',
            ),
            '');


        $editor = JavelinHtml::phutil_tag("div", [
            "class" => "d-flex"
        ], [
            $select_all,
            $select_none,
            JavelinHtml::phutil_tag("div", ["id" => "batch-select-status-cell", "class" => "d-flex align-items-center justify-content-center"]),
            $submit,
            $hidden
        ]);

        $editor = JavelinHtml::phabricator_form(
            $user,
            array(
                'method' => 'POST',
                'action' => Url::to(['/userservice/index/bulk']),
                'id' => 'batch-select-form',
            ),
            $editor);

        $content = phutil_tag_div('maniphest-batch-editor', [$editor]);

        return $content;
    }
}
