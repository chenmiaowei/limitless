<?php

namespace orangins\modules\dashboard\paneltype;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomFieldList;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIPagerView;
use orangins\modules\dashboard\editfield\PhabricatorDashboardQueryPanelApplicationEditField;
use orangins\modules\dashboard\editfield\PhabricatorDashboardQueryPanelQueryEditField;
use orangins\modules\dashboard\engine\PhabricatorDashboardPanelRenderingEngine;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\dashboard\xaction\panel\PhabricatorDashboardQueryPanelApplicationTransaction;
use orangins\modules\dashboard\xaction\panel\PhabricatorDashboardQueryPanelQueryTransaction;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\search\actions\PhabricatorApplicationSearchAction;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;
use Exception;

/**
 * Class PhabricatorDashboardQueryPanelType
 * @package orangins\modules\dashboard\paneltype
 * @author 陈妙威
 */
final class PhabricatorDashboardQueryPanelType
    extends PhabricatorDashboardPanelType
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelTypeKey()
    {
        return 'query';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelTypeName()
    {
        return \Yii::t("app",'Query Panel');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return 'fa-search';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPanelTypeDescription()
    {
        return \Yii::t("app",
            'Show results of a search query, like the most recently filed tasks or ' .
            'revisions you need to review.');
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return array|mixed
     * @author 陈妙威
     */
    protected function newEditEngineFields(PhabricatorDashboardPanel $panel) {
        $application_field =
            (new PhabricatorDashboardQueryPanelApplicationEditField())
                ->setKey('class')
                ->setLabel(pht('Search For'))
                ->setTransactionType(
                    PhabricatorDashboardQueryPanelApplicationTransaction::TRANSACTIONTYPE)
                ->setValue($panel->getProperty('class', ''));

        $application_id = $application_field->getControlID();

        $query_field =
            (new PhabricatorDashboardQueryPanelQueryEditField())
                ->setKey('key')
                ->setLabel(pht('Query'))
                ->setApplicationControlID($application_id)
                ->setTransactionType(
                    PhabricatorDashboardQueryPanelQueryTransaction::TRANSACTIONTYPE)
                ->setValue($panel->getProperty('key', ''));

        return array(
            $application_field,
            $query_field,
        );
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @param PhabricatorCustomFieldList $field_list
     * @param AphrontRequest $request
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function initializeFieldsFromRequest(
        PhabricatorDashboardPanel $panel,
        PhabricatorCustomFieldList $field_list,
        AphrontRequest $request) {

        $map = array();
        if (strlen($request->getStr('engine'))) {
            $map['class'] = $request->getStr('engine');
        }

        if (strlen($request->getStr('query'))) {
            $map['key'] = $request->getStr('query');
        }

        $full_map = array();
        foreach ($map as $key => $value) {
            $full_map["std:dashboard:core:{$key}"] = $value;
        }

        foreach ($field_list->getFields() as $field) {
            $field_key = $field->getFieldKey();
            if (isset($full_map[$field_key])) {
                $field->setValueFromStorage($full_map[$field_key]);
            }
        }
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorDashboardPanel $panel
     * @param PhabricatorDashboardPanelRenderingEngine $engine
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    public function renderPanelContent(
        PhabricatorUser $viewer,
        PhabricatorDashboardPanel $panel,
        PhabricatorDashboardPanelRenderingEngine $engine) {

        $engine = $this->getSearchEngine($panel);

        $engine->setViewer($viewer);
        $engine->setContext(PhabricatorApplicationSearchEngine::CONTEXT_PANEL);

        $key = $panel->getProperty('key');
        if ($engine->isBuiltinQuery($key)) {
            $saved = $engine->buildSavedQueryFromBuiltin($key);
        } else {
            $saved = PhabricatorSavedQuery::find()
                ->setViewer($viewer)
                ->withEngineClassNames(array(get_class($engine)))
                ->withQueryKeys(array($key))
                ->executeOne();
        }

        if (!$saved) {
            throw new Exception(
                pht(
                    'Query "%s" is unknown to application search engine "%s"!',
                    $key,
                    get_class($engine)));
        }

        $query = $engine->buildQueryFromSavedQuery($saved);
        $pager = $engine->newPagerForSavedQuery($saved);

        if ($panel->getProperty('limit')) {
            $limit = (int)$panel->getProperty('limit');
            if ($pager->getPageSize() !== 0xFFFF) {
                $pager->setPageSize($limit);
            }
        }

        $query->setReturnPartialResultsOnOverheat(true);

        $results = $engine->executeQuery($query, $pager);
        $results_view = $engine->renderResults($results, $saved);

        $is_overheated = $query->getIsOverheated();
        $overheated_view = null;
        if ($is_overheated) {
            $content = $results_view->getContent();

            $overheated_message =
                PhabricatorApplicationSearchAction::newOverheatedError((bool)$results);

            $overheated_warning = (new PHUIInfoView())
                ->setSeverity(PHUIInfoView::SEVERITY_WARNING)
                ->setTitle(pht('Query Overheated'))
                ->setErrors(
                    array(
                        $overheated_message,
                    ));

            $overheated_box = (new PHUIBoxView())
                ->addClass('mmt mmb')
                ->appendChild($overheated_warning);

            $content = array($content, $overheated_box);
            $results_view->setContent($content);
        }

        // TODO: A small number of queries, including "Notifications" and "Search",
        // use an offset pager which has a slightly different API. Some day, we
        // should unify these.
        if ($pager instanceof PHUIPagerView) {
            $has_more = $pager->getHasMorePages();
        } else {
            $has_more = $pager->getHasMoreResults();
        }

        if ($has_more) {
            $item_list = $results_view->getObjectList();

            $more_href = $engine->getQueryResultsPageURI($key);
            if ($item_list) {
                $item_list->newTailButton()
                    ->setHref($more_href);
            } else {
                // For search engines that do not return an object list, add a fake
                // one to the end so we can render a "View All Results" button that
                // looks like it does in normal applications. At time of writing,
                // several major applications like Maniphest (which has group headers)
                // and Feed (which uses custom rendering) don't return simple lists.

                $content = $results_view->getContent();

                $more_list = (new PHUIObjectItemListView())
                    ->setAllowEmptyList(true);

                $more_list->newTailButton()
                    ->setHref($more_href);

                $content = array($content, $more_list);
                $results_view->setContent($content);
            }
        }

        return $results_view;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorDashboardPanel $panel
     * @param PhabricatorDashboardPanelRenderingEngine $engine
     * @param PHUIHeaderView $header
     * @return PHUIHeaderView
     * @throws Exception
     * @author 陈妙威
     */
    public function adjustPanelHeader(
        PhabricatorUser $viewer,
        PhabricatorDashboardPanel $panel,
        PhabricatorDashboardPanelRenderingEngine $engine,
        PHUIHeaderView $header) {

        $search_engine = $this->getSearchEngine($panel);
        $key = $panel->getProperty('key');
        $href = $search_engine->getQueryResultsPageURI($key);

        $icon = (new PHUIIconView())
            ->setIcon('fa-search');

        $button = (new PHUIButtonView())
            ->addClass("btn-xs")
            ->setTag('a')
            ->setText(\Yii::t("app", 'View All'))
            ->setIcon($icon)
            ->setHref($href)
            ->setColor(PhabricatorEnv::getEnvConfig('ui.widget-color'));

        $header->addActionLink($button);

        return $header;
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return null|PhabricatorApplicationSearchEngine
     * @throws Exception
     * @author 陈妙威
     */
    private function getSearchEngine(PhabricatorDashboardPanel $panel) {
        $class = $panel->getProperty('class');
        $engine = PhabricatorApplicationSearchEngine::getEngineByClassName($class);
        if (!$engine) {
            throw new Exception(
                pht(
                    'The application search engine "%s" is not known to Phabricator!',
                    $class));
        }

        if (!$engine->canUseInPanelContext()) {
            throw new Exception(
                pht(
                    'Application search engines of class "%s" can not be used to build '.
                    'dashboard panels.',
                    $class));
        }

        return $engine;
    }
}
