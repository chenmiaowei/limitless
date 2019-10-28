<?php

namespace orangins\modules\herald\query;

use orangins\lib\view\phui\PHUIBigInfoView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\config\HeraldRuleTypeConfig;
use orangins\modules\herald\models\HeraldRule;
use orangins\modules\herald\view\HeraldRuleListView;
use orangins\modules\people\searchfield\PhabricatorUsersSearchField;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\field\PhabricatorPHIDsSearchField;
use orangins\modules\search\field\PhabricatorSearchCheckboxesField;
use orangins\modules\search\field\PhabricatorSearchThreeStateField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;


/**
 * Class HeraldRuleSearchEngine
 * @package orangins\modules\herald\query
 * @author 陈妙威
 */
final class HeraldRuleSearchEngine extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return pht('Herald Rules');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return 'PhabricatorHeraldApplication';
    }

    /**
     * @return HeraldRuleQuery
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function newQuery()
    {
        return HeraldRule::find()
            ->needValidateAuthors(true);
    }

    /**
     * @return array|void
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function buildCustomSearchFields()
    {
        $viewer = $this->requireViewer();

        $rule_types = HeraldRuleTypeConfig::getRuleTypeMap();
        $content_types = HeraldAdapter::getEnabledAdapterMap($viewer);

        return array(
            (new PhabricatorUsersSearchField())
                ->setLabel(pht('Authors'))
                ->setKey('authorPHIDs')
                ->setAliases(array('author', 'authors', 'authorPHID'))
                ->setDescription(
                    pht('Search for rules with given authors.')),
            (new PhabricatorSearchCheckboxesField())
                ->setKey('ruleTypes')
                ->setAliases(array('ruleType'))
                ->setLabel(pht('Rule Type'))
                ->setDescription(
                    pht('Search for rules of given types.'))
                ->setOptions($rule_types),
            (new PhabricatorSearchCheckboxesField())
                ->setKey('contentTypes')
                ->setLabel(pht('Content Type'))
                ->setDescription(
                    pht('Search for rules affecting given types of content.'))
                ->setOptions($content_types),
            (new PhabricatorSearchThreeStateField())
                ->setLabel(pht('Active Rules'))
                ->setKey('active')
                ->setOptions(
                    pht('(Show All)'),
                    pht('Show Only Active Rules'),
                    pht('Show Only Inactive Rules')),
            (new PhabricatorSearchThreeStateField())
                ->setLabel(pht('Disabled Rules'))
                ->setKey('disabled')
                ->setOptions(
                    pht('(Show All)'),
                    pht('Show Only Disabled Rules'),
                    pht('Show Only Enabled Rules')),
            (new PhabricatorPHIDsSearchField())
                ->setLabel(pht('Affected Objects'))
                ->setKey('affectedPHIDs')
                ->setAliases(array('affectedPHID')),
        );
    }

    /**
     * @param array $map
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery|void
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

        if ($map['authorPHIDs']) {
            $query->withAuthorPHIDs($map['authorPHIDs']);
        }

        if ($map['contentTypes']) {
            $query->withContentTypes($map['contentTypes']);
        }

        if ($map['ruleTypes']) {
            $query->withRuleTypes($map['ruleTypes']);
        }

        if ($map['disabled'] !== null) {
            $query->withDisabled($map['disabled']);
        }

        if ($map['active'] !== null) {
            $query->withActive($map['active']);
        }

        if ($map['affectedPHIDs']) {
            $query->withAffectedObjectPHIDs($map['affectedPHIDs']);
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
            '/herald/index/' . $path
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
            $names['authored'] = pht('Authored');
        }

        $names['active'] = pht('Active');
        $names['all'] = pht('All');

        return $names;
    }

    /**
     * @param $query_key
     * @return mixed|PhabricatorSavedQuery
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSavedQueryFromBuiltin($query_key)
    {
        $query = $this->newSavedQuery();
        $query->setQueryKey($query_key);

        $viewer_phid = $this->requireViewer()->getPHID();

        switch ($query_key) {
            case 'all':
                return $query;
            case 'active':
                return $query
                    ->setParameter('active', true);
            case 'authored':
                return $query
                    ->setParameter('authorPHIDs', array($viewer_phid))
                    ->setParameter('disabled', false);
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $rules
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return mixed|PhabricatorApplicationSearchResultView
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function renderResultList(
        array $rules,
        PhabricatorSavedQuery $query,
        array $handles)
    {
        assert_instances_of($rules, HeraldRule::className());
        $viewer = $this->requireViewer();

        $list = (new HeraldRuleListView())
            ->setViewer($viewer)
            ->setRules($rules)
            ->newObjectList();

        $result = new PhabricatorApplicationSearchResultView();
        $result->setObjectList($list);
        $result->setNoDataString(pht('No rules found.'));

        return $result;
    }

    /**
     * @return PHUIBigInfoView|null
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function getNewUserBody()
    {
        $create_button = (new PHUIButtonView())
            ->setTag('a')
            ->setText(pht('Create Herald Rule'))
            ->setHref(Url::to(['/herald/index/create']))
            ->setColor(PHUIButtonView::COLOR_SUCCESS);

        $icon = $this->getApplication()->getIcon();
        $app_name = $this->getApplication()->getName();
        $view = (new PHUIBigInfoView())
            ->setIcon($icon)
            ->setTitle(pht('Welcome to %s', $app_name))
            ->setDescription(
                pht('A flexible rules engine that can notify and act on ' .
                    'other actions such as tasks, diffs, and commits.'))
            ->addAction($create_button);

        return $view;
    }

}
