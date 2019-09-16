<?php

namespace orangins\modules\settings\query;

use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorUserPreferencesSearchEngine
 * @package orangins\modules\settings\query
 * @author 陈妙威
 */
final class PhabricatorUserPreferencesSearchEngine extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app", 'User Preferences');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return 'PhabricatorSettingApplication';
    }

    /**
     * @return null
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    public function newQuery()
    {
        return PhabricatorUserPreferences::find()
            ->withHasUserPHID(false);
    }

    /**
     * @param array $map
     * @return null
     * @throws \yii\base\InvalidConfigException
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

        return $query;
    }

    /**
     * @author 陈妙威
     */
    protected function buildCustomSearchFields()
    {
        return array();
    }

    /**
     * @param $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge(['/settings/index/' . $path], $params));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        $names = array(
            'all' => \Yii::t("app", 'All Settings'),
        );

        return $names;
    }

    /**
     * @param $query_key
     * @return mixed|PhabricatorSavedQuery
     * @throws \ReflectionException
     * @throws \yii\base\Exception
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
     * @param array $settings
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function renderResultList(
        array $settings,
        PhabricatorSavedQuery $query,
        array $handles)
    {
        assert_instances_of($settings, 'PhabricatorUserPreferences');

        $viewer = $this->requireViewer();

        $list = (new PHUIObjectItemListView())
            ->setViewer($viewer);
        foreach ($settings as $setting) {

            $icon = (new PHUIIconView())
                ->setIcon('fa-globe')
                ->setBackground('bg-sky');

            $item = (new PHUIObjectItemView())
                ->setHeader($setting->getDisplayName())
                ->setHref($setting->getEditURI())
                ->setImageIcon($icon)
                ->addAttribute(\Yii::t("app", 'Edit global default settings for all users.'));

            $list->addItem($item);
        }

        $list->addItem(
            (new PHUIObjectItemView())
                ->setHeader(\Yii::t("app", 'Personal Account Settings'))
                ->addAttribute(\Yii::t("app", 'Edit settings for your personal account.'))
                ->setImageURI($viewer->getProfileImageURI())
                ->setHref(Url::to(['/settings/index/user', 'username' => $viewer->getUsername()])));

        return (new PhabricatorApplicationSearchResultView())
            ->setObjectList($list);
    }

}
