<?php

namespace orangins\modules\search\actions;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\export\engine\PhabricatorExportEngine;
use orangins\lib\export\format\PhabricatorExportFormat;
use orangins\lib\markup\view\PHUIRemarkupView;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\modules\dashboard\application\PhabricatorDashboardApplication;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\settings\editors\PhabricatorUserPreferencesEditor;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use PhutilInvalidStateException;
use orangins\lib\helpers\JavelinHtml;
use PhutilURI;
use orangins\lib\PhabricatorApplication;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\layout\PhabricatorAnchorView;
use orangins\lib\view\layout\PHUIApplicationMenuView;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\search\assets\JavelinReorderQueriesAsset;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\AphrontFormView;
use orangins\modules\search\exception\PhabricatorSearchConstraintException;
use PhutilSearchQueryCompilerSyntaxException;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use orangins\modules\settings\setting\PhabricatorPolicyFavoritesSetting;
use orangins\modules\typeahead\exception\PhabricatorTypeaheadInvalidTokenException;
use Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorApplicationSearchController
 * @package orangins\modules\search\controllers
 * @author 陈妙威
 */
final class PhabricatorApplicationSearchAction extends PhabricatorSearchBaseAction
{

    /**
     * @var
     */
    private $searchEngine;
    /**
     * @var
     */
    private $navigation;
    /**
     * @var
     */
    private $queryKey;
    /**
     * @var
     */
    private $preface;
    /**
     * @var
     */
    private $activeQuery;

    /**
     * @param $has_results
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public static function newOverheatedError($has_results)
    {
        $overheated_link = phutil_tag(
            'a',
            array(
                'href' => 'https://phurl.io/u/overheated',
                'target' => '_blank',
            ),
            pht('Learn More'));

        if ($has_results) {
            $message = pht(
                'This query took too long, so only some results are shown. %s',
                $overheated_link);
        } else {
            $message = pht(
                'This query took too long. %s',
                $overheated_link);
        }

        return $message;
    }

    /**
     * @param $preface
     * @return $this
     * @author 陈妙威
     */
    public function setPreface($preface)
    {
        $this->preface = $preface;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPreface()
    {
        return $this->preface;
    }

    /**
     * @param $query_key
     * @return $this
     * @author 陈妙威
     */
    public function setQueryKey($query_key)
    {
        $this->queryKey = $query_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getQueryKey()
    {
        return $this->queryKey;
    }

    /**
     * @param AphrontSideNavFilterView $navigation
     * @return $this
     * @author 陈妙威
     */
    public function setNavigation(AphrontSideNavFilterView $navigation)
    {
        $this->navigation = $navigation;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getNavigation()
    {
        return $this->navigation;
    }

    /**
     * @param PhabricatorApplicationSearchEngine $search_engine
     * @return $this
     * @author 陈妙威
     */
    public function setSearchEngine(PhabricatorApplicationSearchEngine $search_engine)
    {
        $this->searchEngine = $search_engine;
        return $this;
    }

    /**
     * @return PhabricatorApplicationSearchEngine
     * @author 陈妙威
     */
    protected function getSearchEngine()
    {
        return $this->searchEngine;
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    protected function getActiveQuery()
    {
        if (!$this->activeQuery) {
            throw new Exception(\Yii::t("app", 'There is no active query yet.'));
        }

        return $this->activeQuery;
    }

    /**
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function validateDelegatingController()
    {
        $parent = $this->getController();

        if (!$parent) {
            throw new Exception(
                \Yii::t("app", 'You must delegate to this controller, not invoke it directly.'));
        }

        $engine = $this->getSearchEngine();
        if (!$engine) {
            throw new PhutilInvalidStateException('setEngine');
        }

        $engine->setViewer($this->getRequest()->getViewer());
        $parent = $this->getController();
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $this->validateDelegatingController();

        $query_action = $this->getRequest()->getURIData('queryAction');
        if ($query_action == 'export') {
            return $this->processExportRequest();
        }

        $key = $this->getQueryKey();
        if ($key == 'edit') {
            return $this->processEditRequest();
        } else {
            return $this->processSearchRequest();
        }
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws \AphrontQueryException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function processSearchRequest()
    {
        $parent = $this->getDelegatingAction();
        $request = $this->getRequest();
        $user = $request->getViewer();
        $engine = $this->getSearchEngine();
        $nav = $this->getNavigation();
        if (!$nav) {
            $nav = $this->buildNavigation();
        }

        if ($request->isFormPost()) {
            $saved_query = $engine->buildSavedQueryFromRequest($request);
            $engine->saveQuery($saved_query);
            return (new AphrontRedirectResponse())
                ->setURI($engine->getQueryResultsPageURI($saved_query->getQueryKey()) . '#R');
        }

        $named_query = null;
        $run_query = true;
        $query_key = $this->queryKey;
        if ($this->queryKey == 'advanced') {
            $run_query = false;
            $query_key = $request->getStr('query');
        } else if (!strlen($this->queryKey)) {
            $found_query_data = false;

            if ($request->isHTTPGet() || $request->isQuicksand()) {
                // If this is a GET request and it has some query data, don't
                // do anything unless it's only before= or after=. We'll build and
                // execute a query from it below. This allows external tools to build
                // URIs like "/query/?users=a,b".
                $pt_data = $request->getPassthroughRequestData();

                $exempt = array(
                    'before' => true,
                    'after' => true,
                    'nux' => true,
                    'overheated' => true,
                    'r' => true,
                    'XDEBUG_SESSION_START' => true,
                );

                foreach ($pt_data as $pt_key => $pt_value) {
                    if (isset($exempt[$pt_key])) {
                        continue;
                    }

                    $found_query_data = true;
                    break;
                }
            }

            if (!$found_query_data) {
                // Otherwise, there's no query data so just run the user's default
                // query for this application.
                $query_key = $engine->getDefaultQueryKey();
            }
        }

        if ($engine->isBuiltinQuery($query_key)) {
            $saved_query = $engine->buildSavedQueryFromBuiltin($query_key);
            $named_query = ArrayHelper::getValue($engine->loadEnabledNamedQueries(), $query_key);
        } else if ($query_key) {
            $saved_query = PhabricatorSavedQuery::find()
                ->setViewer($user)
                ->withQueryKeys(array($query_key))
                ->executeOne();

            if (!$saved_query) {
                return new Aphront404Response();
            }

            $named_query = ArrayHelper::getValue($engine->loadEnabledNamedQueries(), $query_key);
        } else {
            $saved_query = $engine->buildSavedQueryFromRequest($request);

            // Save the query to generate a query key, so "Save Custom Query..." and
            // other features like "Bulk Edit" and "Export Data" work correctly.
            $engine->saveQuery($saved_query);
        }

        $this->activeQuery = $saved_query;

        $nav->selectFilter('query/' . $saved_query->getQueryKey(), 'query/advanced');

        $form = (new AphrontFormView())
            ->setViewer($user)
            ->addClass(PHUI::PADDING_LARGE)
            ->setAction($request->getPath());

        $engine->buildSearchForm($form, $saved_query);

        $errors = $engine->getErrors();
        if ($errors) {
            $run_query = false;
        }

        $submit = (new AphrontFormSubmitControl())
            ->setValue(\Yii::t("app", 'Search'));

        if ($run_query && !$named_query && $user->isLoggedIn() && PhabricatorEnv::getEnvConfig('orangins.show-save-query')) {
            $save_button = (new PHUIButtonView())
                ->setTag('a')
                ->setHref(Url::to(['/search/index/edit', 'queryKey' => $saved_query->getQueryKey()]))
                ->setText(\Yii::t("app", 'Save Query'))
                ->addClass(PHUI::MARGIN_MEDIUM_LEFT)
                ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
                ->setIcon('fa-floppy-o');
            $submit->addButton($save_button);
        }

        // TODO: A "Create Dashboard Panel" action goes here somewhere once
        // we sort out T5307.

        $form->appendChild($submit);
        $body = array();

        if ($this->getPreface()) {
            $body[] = $this->getPreface();
        }

        if ($named_query) {
            $title = $named_query->getQueryName();
        } else {
            $title = \Yii::t("app", 'Advanced Search');
        }

        $header = (new PHUIPageHeaderView())
            ->setHeader($title)
            ->setProfileHeader(true);

        $box = (new PHUIObjectBoxView())
            ->addBodyClass(PHUI::PADDING_NONE)
            ->addClass('application-search-results');

        if ($run_query || $named_query) {
            $box->setShowHide(
                \Yii::t("app", '搜索查询'),
                \Yii::t("app", 'Hide Query'),
                $form,
                $this->getApplicationURI('query/advanced/?query=' . $query_key),
                (!$named_query ? true : false));
        } else {
            $box->setForm($form);
        }

        $body[] = $box;
        $more_crumbs = null;

        if ($run_query) {
            $exec_errors = array();

            $box->setAnchor(
                (new PhabricatorAnchorView())
                    ->setAnchorName('R'));

            try {
                $engine->setRequest($request);

                $query = $engine->buildQueryFromSavedQuery($saved_query);

                $pager = $engine->newPagerForSavedQuery($saved_query);
                $pager->readFromRequest($request);

                $objects = $engine->executeQuery($query, $pager);

                $force_nux = $request->getBool('nux');
                if (!$objects || $force_nux) {
                    $nux_view = $this->renderNewUserView($engine, $force_nux);
                } else {
                    $nux_view = null;
                }

                $is_overflowing =
                    $pager->willShowPagingControls() &&
                    $engine->getResultBucket($saved_query);

                $force_overheated = $request->getBool('overheated');
                $is_overheated = $query->getIsOverheated() || $force_overheated;

                if ($nux_view) {
                    $box->appendChild($nux_view);
                } else {
                    $list = $engine->renderResults($objects, $saved_query);

                    if (!($list instanceof PhabricatorApplicationSearchResultView)) {
                        throw new Exception(
                            \Yii::t("app",
                                'SearchEngines must render a "{0}" object, but this engine ' .
                                '(of class "{1}") rendered something else.',
                                [
                                    'PhabricatorApplicationSearchResultView',
                                    get_class($engine)
                                ]));
                    }

                    if ($list->getObjectList()) {
                        $box->setObjectList($list->getObjectList());
                    }
                    if ($list->getTable()) {
                        $box->setTable($list->getTable());
                    }
                    if ($list->getInfoView()) {
                        $box->setInfoView($list->getInfoView());
                    }
                    if ($list->getFooter()) {
                        $box->setFoooter($list->getFooter());
                    }

                    if ($is_overflowing) {
                        $box->appendChild($this->newOverflowingView());
                    }

                    if ($list->getContent()) {
                        $box->appendChild($list->getContent());
                    }

                    if ($is_overheated) {
                        $box->appendChild($this->newOverheatedView($objects));
                    }

                    $result_header = $list->getHeader();
                    if ($result_header) {
                        $box->setHeader($result_header);
                        $header = $result_header;
                    }

                    $actions = $list->getActions();
                    if ($actions) {
                        foreach ($actions as $action) {
                            $header->addActionLink($action);
                        }
                    }

                    $use_actions = $engine->newUseResultsActions($saved_query);

                    // TODO: Eventually, modularize all this stuff.
                    $builtin_use_actions = $this->newBuiltinUseActions();
                    if ($builtin_use_actions) {
                        foreach ($builtin_use_actions as $builtin_use_action) {
                            $use_actions[] = $builtin_use_action;
                        }
                    }

                    if ($use_actions) {
//                        $use_dropdown = $this->newUseResultsDropdown(
//                            $saved_query,
//                            $use_actions);
//                        $header->addActionLink($use_dropdown);

                        foreach ($use_actions as $use_action) {
                            $action_button = (new PHUIButtonView())
                                ->setTag('a')
                                ->setHref($use_action->getHref())
                                ->setText($use_action->getName())
                                ->setWorkflow($use_action->getWorkflow())
                                ->setIcon($use_action->getIcon());

                            $header->addActionLink($action_button);
                        }
                    }

                    $more_crumbs = $list->getCrumbs();

                    if ($pager->willShowPagingControls()) {
                        $pager_box = (new PHUIBoxView())
                            ->setColor(PHUIBoxView::BACKGROUND_GREY)
                            ->addClass('application-search-pager')
                            ->appendChild($pager);
                        $body[] = $pager_box;
                    }
                }
            } catch (PhabricatorTypeaheadInvalidTokenException $ex) {
                $exec_errors[] = \Yii::t("app",
                    'This query specifies an invalid parameter. Review the ' .
                    'query parameters and correct errors.');
            } catch (PhutilSearchQueryCompilerSyntaxException $ex) {
                $exec_errors[] = $ex->getMessage();
            } catch (PhabricatorSearchConstraintException $ex) {
                $exec_errors[] = $ex->getMessage();
            }

            // The engine may have encountered additional errors during rendering;
            // merge them in and show everything.
            foreach ($engine->getErrors() as $error) {
                $exec_errors[] = $error;
            }

            $errors = $exec_errors;
        }

        if ($errors) {
            $box->setFormErrors($errors, \Yii::t("app", 'Query Errors'));
        }

        $crumbs = $parent
            ->buildApplicationCrumbs()
            ->setBorder(true);

        if ($more_crumbs) {
            $query_uri = $engine->getQueryResultsPageURI($saved_query->getQueryKey());
            $crumbs->addTextCrumb($title, $query_uri);

            foreach ($more_crumbs as $crumb) {
                $crumbs->addCrumb($crumb);
            }
        } else {
            $crumbs->addTextCrumb($title);
        }

//        require_celerity_resource('application-search-view-css');

        return $this->newPage()
            ->setHeader($header)
            ->setApplicationMenu($this->buildApplicationMenu())
            ->setTitle(\Yii::t("app", 'Query: {0}', [$title]))
            ->setCrumbs($crumbs)
            ->setNavigation($nav)
            ->addClass('application-search-view')
            ->appendChild($body);
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function processExportRequest()
    {
        $viewer = $this->getViewer();
        $engine = $this->getSearchEngine();
        $request = $this->getRequest();

        if (!$this->canExport()) {
            return new Aphront404Response();
        }

        $query_key = $this->getQueryKey();
        if ($engine->isBuiltinQuery($query_key)) {
            $saved_query = $engine->buildSavedQueryFromBuiltin($query_key);
        } else if ($query_key) {
            $saved_query = PhabricatorSavedQuery::find()
                ->setViewer($viewer)
                ->withQueryKeys(array($query_key))
                ->executeOne();
        } else {
            $saved_query = null;
        }

        if (!$saved_query) {
            return new Aphront404Response();
        }

        $cancel_uri = $engine->getQueryResultsPageURI($query_key);

        $named_query = ArrayHelper::getValue($engine->loadEnabledNamedQueries(), $query_key);

        if ($named_query) {
            $filename = $named_query->getQueryName();
            $sheet_title = $named_query->getQueryName();
        } else {
            $filename = $engine->getResultTypeDescription();
            $sheet_title = $engine->getResultTypeDescription();
        }
        $filename = phutil_utf8_strtolower($filename);
        $filename = PhabricatorFile::normalizeFileName($filename);

        $all_formats = PhabricatorExportFormat::getAllExportFormats();

        $available_options = array();
        $unavailable_options = array();
        $formats = array();
        $unavailable_formats = array();
        foreach ($all_formats as $key => $format) {
            if ($format->isExportFormatEnabled()) {
                $available_options[$key] = $format->getExportFormatName();
                $formats[$key] = $format;
            } else {
                $unavailable_options[$key] = \Yii::t("app", '{0} (Not Available)', [
                    $format->getExportFormatName()
                ]);
                $unavailable_formats[$key] = $format;
            }
        }
        $format_options = $available_options + $unavailable_options;

        // Try to default to the format the user used last time. If you just
        // exported to Excel, you probably want to export to Excel again.
        $format_key = $this->readExportFormatPreference();
        if (!$format_key || !isset($formats[$format_key])) {
            $format_key = head_key($format_options);
        }

        // Check if this is a large result set or not. If we're exporting a
        // large amount of data, we'll build the actual export file in the daemons.

        $threshold = 1000;
        $query = $engine->buildQueryFromSavedQuery($saved_query);
        $pager = $engine->newPagerForSavedQuery($saved_query);
        $pager->setPageSize($threshold + 1);
        $objects = $engine->executeQuery($query, $pager);
        $object_count = count($objects);
        $is_large_export = ($object_count > $threshold);

        $errors = array();

        $e_format = null;
        if ($request->isFormPost()) {
            $format_key = $request->getStr('format');

            if (isset($unavailable_formats[$format_key])) {
                $unavailable = $unavailable_formats[$format_key];
                $instructions = $unavailable->getInstallInstructions();

                $markup = (new PHUIRemarkupView($viewer, $instructions))
                    ->setRemarkupOption(
                        PHUIRemarkupView::OPTION_PRESERVE_LINEBREAKS,
                        false);

                return $this->newDialog()
                    ->setTitle(\Yii::t("app", 'Export Format Not Available'))
                    ->appendChild($markup)
                    ->addCancelButton($cancel_uri, \Yii::t("app", 'Done'));
            }

            $format = ArrayHelper::getValue($formats, $format_key);

            if (!$format) {
                $e_format = \Yii::t("app", 'Invalid');
                $errors[] = \Yii::t("app", 'Choose a valid export format.');
            }

            if (!$errors) {
                $this->writeExportFormatPreference($format_key);

                $export_engine = (new PhabricatorExportEngine())
                    ->setViewer($viewer)
                    ->setSearchEngine($engine)
                    ->setSavedQuery($saved_query)
                    ->setTitle($sheet_title)
                    ->setFilename($filename)
                    ->setExportFormat($format);

                if ($is_large_export) {
                    $job = $export_engine->newBulkJob($request);

                    return (new AphrontRedirectResponse())->setURI($job->getMonitorURI());
                } else {
                    $file = $export_engine->exportFile();
                    return $file->newDownloadResponse();
                }
            }
        }

        $export_form = (new AphrontFormView())
            ->setViewer($viewer)
            ->appendControl(
                (new AphrontFormSelectControl())
                    ->setName('format')
                    ->setLabel(\Yii::t("app", 'Format'))
                    ->setError($e_format)
                    ->setValue($format_key)
                    ->setOptions($format_options));

        if ($is_large_export) {
            $submit_button = \Yii::t("app", 'Continue');
        } else {
            $submit_button = \Yii::t("app", 'Download Data');
        }

        return $this->newDialog()
            ->addClass("wmin-600")
            ->setTitle(\Yii::t("app", 'Export Results'))
            ->setErrors($errors)
            ->appendForm($export_form)
            ->addCancelButton($cancel_uri)
            ->addSubmitButton($submit_button);
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    private function processEditRequest()
    {
        $parent = $this->getDelegatingAction();
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $engine = $this->getSearchEngine();

        $nav = $this->getNavigation();
        if (!$nav) {
            $nav = $this->buildNavigation();
        }

        $named_queries = $engine->loadAllNamedQueries();
        $can_global = $viewer->getIsAdmin();
        $groups = array(
            'personal' => array(
                'name' => \Yii::t("app", 'Personal Saved Queries'),
                'items' => array(),
                'edit' => true,
            ),
            'global' => array(
                'name' => \Yii::t("app", 'Global Saved Queries'),
                'items' => array(),
                'edit' => $can_global,
            ),
        );

        foreach ($named_queries as $named_query) {
            if ($named_query->isGlobal()) {
                $group = 'global';
            } else {
                $group = 'personal';
            }

            $groups[$group]['items'][] = $named_query;
        }

        $default_key = $engine->getDefaultQueryKey();

        $lists = array();
        foreach ($groups as $group) {
            $lists[] = $this->newQueryListView(
                $group['name'],
                $group['items'],
                $default_key,
                $group['edit']);
        }

        $crumbs = $parent
            ->buildApplicationCrumbs()
            ->addTextCrumb(\Yii::t("app", 'Saved Queries'), $engine->getQueryManagementURI())
            ->setBorder(true);

        $nav->selectFilter('query/edit');

        $header = (new PHUIPageHeaderView())
            ->setHeader(\Yii::t("app", 'Saved Queries'))
            ->setProfileHeader(true);

        $view = (new PHUITwoColumnView())
            ->setFooter($lists);

        return $this->newPage()
            ->setApplicationMenu($this->buildApplicationMenu())
            ->setTitle(\Yii::t("app", 'Saved Queries'))
            ->setHeader($header)
            ->setCrumbs($crumbs)
            ->setNavigation($nav)
            ->appendChild($view);
    }

    /**
     * @param $list_name
     * @param array $named_queries
     * @param $default_key
     * @param $can_edit
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     * @throws \ReflectionException
     */
    private function newQueryListView($list_name, array $named_queries, $default_key, $can_edit)
    {

        $engine = $this->getSearchEngine();
        $viewer = $this->getViewer();

        $list = (new PHUIObjectItemListView())
            ->setViewer($viewer);

        if ($can_edit) {
//            $list_id = JavelinHtml::celerity_generate_unique_node_id();
//            $list->setID($list_id);
            $ID = $list->getID();
            JavelinHtml::initBehavior(
                new JavelinReorderQueriesAsset(),
                array(
                    'listID' => $ID,
                    'orderURI' => Url::to(['/search/index/order', 'engine' => $engine->getClassShortName()]),
                ));
        }

        foreach ($named_queries as $named_query) {
            $className = $engine->getClassShortName();
            $key = $named_query->getQueryKey();

            $item = (new PHUIObjectItemView())
                ->addClass("cursor-move")
                ->setHeader($named_query->getQueryName())
                ->setHref($engine->getQueryResultsPageURI($key));

            if ($named_query->getIsDisabled()) {
                if ($can_edit) {
                    $item->setDisabled(true);
                } else {
                    // If an item is disabled and you don't have permission to edit it,
                    // just skip it.
                    continue;
                }
            }

            if ($can_edit) {
                if ($named_query->getIsBuiltin() && $named_query->getIsDisabled()) {
                    $icon = 'fa-plus';
                    $disable_name = \Yii::t("app", 'Enable');
                } else {
                    $icon = 'fa-times';
                    if ($named_query->getIsBuiltin()) {
                        $disable_name = \Yii::t("app", 'Disable');
                    } else {
                        $disable_name = \Yii::t("app", 'Delete');
                    }
                }

                if ($named_query->getID()) {
                    $disable_href = Url::to(['/search/index/delete', 'id' => $named_query->getID()]);
                } else {
                    $disable_href = Url::to(['/search/index/delete', 'queryKey' => $key, 'engine' => $className]);
                }

                $item->addAction(
                    (new PHUIListItemView())
                        ->setIcon($icon)
                        ->setHref($disable_href)
                        ->setRenderNameAsTooltip(true)
                        ->setName($disable_name)
                        ->setWorkflow(true));
            }

            $default_disabled = $named_query->getIsDisabled();
            $default_icon = 'fa-thumb-tack';

            if ($default_key === $key) {
                $default_color = 'text-success';
            } else {
                $default_color = null;
            }

            $item->addAction(
                (new PHUIListItemView())
                    ->setIcon("{$default_icon} {$default_color}")
                    ->setHref(Url::to(['/search/index/default/', 'queryKey' => $key, 'engine' => $className]))
                    ->setRenderNameAsTooltip(true)
                    ->setName(\Yii::t("app", 'Make Default'))
                    ->setWorkflow(true)
                    ->setDisabled($default_disabled));

            if ($can_edit) {
                if ($named_query->getIsBuiltin()) {
                    $edit_icon = 'fa-lock lightgreytext';
                    $edit_disabled = true;
                    $edit_name = \Yii::t("app", 'Builtin');
                    $edit_href = null;
                } else {
                    $edit_icon = 'fa-pencil';
                    $edit_disabled = false;
                    $edit_name = \Yii::t("app", 'Edit');
                    $edit_href = Url::to(['/search/index/edit', 'id' => $named_query->getID()]);
                }

                $item->addAction(
                    (new PHUIListItemView())
                        ->setIcon($edit_icon)
                        ->setHref($edit_href)
                        ->setRenderNameAsTooltip(true)
                        ->setName($edit_name)
                        ->setDisabled($edit_disabled));
            }

            $item->setGrippable($can_edit);
            $item->addSigil('named-query');
            $item->setMetadata(
                array(
                    'queryKey' => $named_query->getQueryKey(),
                ));

            $list->addItem($item);
        }

        $list->setNoDataString(\Yii::t("app", 'No saved queries.'));

        return (new PHUIObjectBoxView())
            ->setHeaderText($list_name)
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->addBodyClass(PHUI::PADDING_NONE)
            ->setObjectList($list);
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        $menu = $this->getDelegatingAction()
            ->buildApplicationMenu();

        if ($menu instanceof PHUIApplicationMenuView) {
            $menu->setSearchEngine($this->getSearchEngine());
        }

        return $menu;
    }

    /**
     * @return AphrontSideNavFilterView
     * @author 陈妙威
     * @throws Exception
     * @throws \ReflectionException
     * @throws PhutilInvalidStateException
     * @throws \Exception
     */
    private function buildNavigation()
    {
        $viewer = $this->getViewer();
        $engine = $this->getSearchEngine();

        $nav = (new AphrontSideNavFilterView())
            ->setViewer($viewer)
            ->setBaseURI(new PhutilURI($this->getApplicationURI()));

        $engine->addNavigationItems($nav->getMenu());

        return $nav;
    }

    /**
     * @param PhabricatorApplicationSearchEngine $engine
     * @param $force_nux
     * @return null
     * @author 陈妙威
     */
    private function renderNewUserView(
        PhabricatorApplicationSearchEngine $engine,
        $force_nux)
    {

        // Don't render NUX if the user has clicked away from the default page.
        if (strlen($this->getQueryKey())) {
            return null;
        }

        // Don't put NUX in panels because it would be weird.
        if ($engine->isPanelContext()) {
            return null;
        }

        // Try to render the view itself first, since this should be very cheap
        // (just returning some text).
        $nux_view = $engine->renderNewUserView();

        if (!$nux_view) {
            return null;
        }

        $query = $engine->newQuery();
        if (!$query) {
            return null;
        }

        // Try to load any object at all. If we can, the application has seen some
        // use so we just render the normal view.
        if (!$force_nux) {
            $object = $query
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->setLimit(1)
                ->execute();
            if ($object) {
                return null;
            }
        }

        return $nux_view;
    }

    /**
     * @param PhabricatorSavedQuery $query
     * @param array $dropdown_items
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function newUseResultsDropdown(
        PhabricatorSavedQuery $query,
        array $dropdown_items)
    {

        $viewer = $this->getViewer();

        $action_list = (new PhabricatorActionListView())
            ->setViewer($viewer);
        foreach ($dropdown_items as $dropdown_item) {
            $action_list->addAction($dropdown_item);
        }

        return (new PHUIButtonView())
            ->setTag('a')
            ->setHref('#')
            ->setText(\Yii::t("app", 'Use Results'))
            ->setIcon('fa-bars')
            ->setDropdownMenu($action_list)
            ->addClass('dropdown');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function newOverflowingView()
    {
        $message = \Yii::t("app",
            'The query matched more than one page of results. Results are ' .
            'paginated before bucketing, so later pages may contain additional ' .
            'results in any bucket.');

        return (new PHUIInfoView())
            ->setSeverity(PHUIInfoView::SEVERITY_WARNING)
            ->setFlush(true)
            ->setTitle(\Yii::t("app", 'Buckets Overflowing'))
            ->setErrors(
                array(
                    $message,
                ));
    }

    /**
     * @param array $results
     * @return mixed
     * @author 陈妙威
     */
    private function newOverheatedView(array $results)
    {
        if ($results) {
            $message = \Yii::t("app",
                'Most objects matching your query are not visible to you, so ' .
                'filtering results is taking a long time. Only some results are ' .
                'shown. Refine your query to find results more quickly.');
        } else {
            $message = \Yii::t("app",
                'Most objects matching your query are not visible to you, so ' .
                'filtering results is taking a long time. Refine your query to ' .
                'find results more quickly.');
        }

        return (new PHUIInfoView())
            ->setSeverity(PHUIInfoView::SEVERITY_WARNING)
            ->setFlush(true)
            ->setTitle(\Yii::t("app", 'Query Overheated'))
            ->setErrors(
                array(
                    $message,
                ));
    }

    /**
     * @return array
     * @throws Exception
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    private function newBuiltinUseActions()
    {
        $actions = array();
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $is_dev = PhabricatorEnv::getEnvConfig('phabricator.developer-mode');

        $engine = $this->getSearchEngine();
        $engine_class = get_class($engine);

        $query_key = $this->getActiveQuery()->getQueryKey();

        $can_use = $engine->canUseInPanelContext();
        $is_installed = PhabricatorApplication::isClassInstalledForViewer(
            PhabricatorDashboardApplication::class,
            $viewer);

        if ($can_use && $is_installed) {
            $actions[] = (new PhabricatorActionView())
                ->setIcon('fa-dashboard')
                ->setName(\Yii::t("app", 'Add to Dashboard'))
                ->setWorkflow(true)
                ->setHref(Url::to([
                    "/dashboard/panel/install",
                    "engineKey" => $engine_class,
                    "queryKey" => $query_key,
                ]));
//                ->setHref("/dashboard/panel/install/{$engine_class}/{$query_key}/");
        }

        if ($this->canExport()) {
            $export_uri = $engine->getExportURI($query_key);
            $actions[] = (new PhabricatorActionView())
                ->setIcon('fa-download')
                ->setName(\Yii::t("app", 'Export Data'))
                ->setWorkflow(true)
                ->setHref($export_uri);
        }

        if ($is_dev) {
            $engine = $this->getSearchEngine();
            $nux_uri = $engine->getQueryBaseURI();
            $nux_uri = (new PhutilURI($nux_uri))
                ->setQueryParam('nux', true);

            $actions[] = (new PhabricatorActionView())
                ->setIcon('fa-user-plus')
                ->setName(\Yii::t("app", 'DEV: New User State'))
                ->setHref($nux_uri);
        }

        if ($is_dev) {
            $overheated_uri = $this->getRequest()->getRequestURI()
                ->setQueryParam('overheated', true);

            $actions[] = (new PhabricatorActionView())
                ->setIcon('fa-fire')
                ->setName(\Yii::t("app", 'DEV: Overheated State'))
                ->setHref($overheated_uri);
        }

        return $actions;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    private function canExport()
    {
        $engine = $this->getSearchEngine();
        if (!$engine->canExport()) {
            return false;
        }

        // Don't allow logged-out users to perform exports. There's no technical
        // or policy reason they can't, but we don't normally give them access
        // to write files or jobs. For now, just err on the side of caution.

        $viewer = $this->getViewer();
        if (!$viewer->getPHID()) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function readExportFormatPreference()
    {
        $viewer = $this->getViewer();
        $export_key = PhabricatorPolicyFavoritesSetting::SETTINGKEY;
        return $viewer->getUserSetting($export_key);
    }

    /**
     * @param $value
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function writeExportFormatPreference($value)
    {
        $viewer = $this->getViewer();
        $request = $this->getRequest();

        if (!$viewer->isLoggedIn()) {
            return;
        }

        $export_key = PhabricatorPolicyFavoritesSetting::SETTINGKEY;
        $preferences = PhabricatorUserPreferences::loadUserPreferences($viewer);

        $editor = (new PhabricatorUserPreferencesEditor())
            ->setActor($viewer)
            ->setContentSourceFromRequest($request)
            ->setContinueOnNoEffect(true)
            ->setContinueOnMissingFields(true);

        $xactions = array();
        $xactions[] = $preferences->newTransaction($export_key, $value);
        $editor->applyTransactions($preferences, $xactions);
    }

}
