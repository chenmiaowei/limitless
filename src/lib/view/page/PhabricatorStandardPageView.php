<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/3
 * Time: 1:20 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\view\page;

use orangins\modules\aphlict\query\AphlictDropdownDataQuery;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\PhabricatorApplication;
use orangins\modules\notification\model\PhabricatorFeedStoryNotification;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use orangins\modules\settings\setting\PhabricatorSidebarToggleSetting;
use PhutilSafeHTML;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\response\AphrontResponse;
use orangins\lib\response\AphrontResponseProducerInterface;
use orangins\lib\response\AphrontWebpageResponse;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\lib\view\layout\PHUIApplicationMenuView;
use orangins\lib\view\page\menu\PhabricatorMainMenuView;
use orangins\lib\view\phui\PHUICrumbsView;
use orangins\lib\view\phui\PHUIListView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\modules\celerity\CelerityAPI;
use orangins\modules\conpherence\application\PhabricatorConpherenceApplication;
use orangins\modules\conpherence\assets\JavelinQuicksandBlacklistBehaviorAsset;
use orangins\modules\conpherence\view\ConpherenceDurableColumnView;
use orangins\modules\notification\client\PhabricatorNotificationServerRef;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\settings\setting\PhabricatorConpherenceColumnMinimizeSetting;
use orangins\modules\settings\setting\PhabricatorConpherenceColumnVisibleSetting;
use orangins\modules\settings\setting\PhabricatorMonospacedFontSetting;
use orangins\modules\settings\setting\PhabricatorTimezoneIgnoreOffsetSetting;
use orangins\modules\settings\setting\PhabricatorTitleGlyphsSetting;
use orangins\modules\widgets\javelin\JavelinDetectTimezoneAsset;
use orangins\modules\widgets\javelin\JavelinSetupCheckHttpsAsset;
use orangins\modules\widgets\javelin\JavelinWorkflowAsset;
use Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class OranginsPage
 * @package orangins\lib\view
 * @author 陈妙威
 */
class PhabricatorStandardPageView extends PhabricatorBarePageView implements AphrontResponseProducerInterface
{
    /**
     * @var PHUIPageHeaderView
     */
    public $header;
    /**
     * @var
     */
    private $baseURI;
    /**
     * @var
     */
    private $applicationName;
    /**
     * @var
     */
    private $glyph;
    /**
     * @var
     */
    private $menuContent;
    /**
     * @var bool
     */
    private $showChrome = true;
    /**
     * @var array
     */
    private $classes = array();
    /**
     * @var
     */
    private $disableConsole;
    /**
     * @var array
     */
    private $pageObjects = array();
    /**
     * @var
     */
    private $applicationMenu;
    /**
     * @var bool
     */
    private $showFooter = true;
    /**
     * @var bool
     */
    private $showDurableColumn = true;
    /**
     * @var array
     */
    private $quicksandConfig = array();
    /**
     * @var array
     */
    private $contentClass = array();
    /**
     * @var
     */
    private $tabs;
    /**
     * @var
     */
    private $crumbs;
    /**
     * @var AphrontSideNavFilterView
     */
    private $navigation;

    /**
     * @param PHUIPageHeaderView $header
     * @return $this
     * @author 陈妙威
     */
    public function setHeader(PHUIPageHeaderView $header = null)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @param $show_footer
     * @return $this
     * @author 陈妙威
     */
    public function setShowFooter($show_footer)
    {
        $this->showFooter = $show_footer;
        return $this;
    }

      /**
     * @param $show_footer
     * @return $this
     * @author 陈妙威
     */
    public function addContentClass($show_footer)
    {
        $this->contentClass[] = $show_footer;
        return $this;
    }



    /**
     * @return bool
     * @author 陈妙威
     */
    public function getShowFooter()
    {
        return $this->showFooter;
    }

    /**
     * @param $application_menu
     * @return $this
     * @author 陈妙威
     */
    public function setApplicationMenu($application_menu)
    {
        // NOTE: For now, this can either be a PHUIListView or a
        // PHUIApplicationMenuView.

        $this->applicationMenu = $application_menu;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationMenu()
    {
        return $this->applicationMenu;
    }

    /**
     * @param $application_name
     * @return $this
     * @author 陈妙威
     */
    public function setApplicationName($application_name)
    {
        $this->applicationName = $application_name;
        return $this;
    }

    /**
     * @param $disable
     * @return $this
     * @author 陈妙威
     */
    public function setDisableConsole($disable)
    {
        $this->disableConsole = $disable;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return $this->applicationName;
    }

    /**
     * @param $base_uri
     * @return $this
     * @author 陈妙威
     */
    public function setBaseURI($base_uri)
    {
        $this->baseURI = $base_uri;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBaseURI()
    {
        return $this->baseURI;
    }

    /**
     * @param $show_chrome
     * @return $this
     * @author 陈妙威
     */
    public function setShowChrome($show_chrome)
    {
        $this->showChrome = $show_chrome;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getShowChrome()
    {
        return $this->showChrome;
    }

    /**
     * @param $class
     * @return $this
     * @author 陈妙威
     */
    public function addClass($class)
    {
        $this->classes[] = $class;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function setPageObjectPHIDs(array $phids)
    {
        $this->pageObjects = $phids;
        return $this;
    }

    /**
     * @param $show
     * @return $this
     * @author 陈妙威
     */
    public function setShowDurableColumn($show)
    {
        $this->showDurableColumn = $show;
        return $this;
    }

    /**
     * @return bool
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function getShowDurableColumn()
    {
        return false;
        $request = $this->getRequest();
        if (!$request) {
            return false;
        }

        $viewer = $request->getViewer();
        if (\Yii::$app->user->getIsGuest()) {
            return false;
        }

        $conpherence_installed = PhabricatorApplication::isClassInstalledForViewer(
            PhabricatorConpherenceApplication::class,
            $viewer);
        if (!$conpherence_installed) {
            return false;
        }

        if ($this->isQuicksandBlacklistURI()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    private function isQuicksandBlacklistURI()
    {
        $request = $this->getRequest();
        if (!$request) {
            return false;
        }

        $patterns = $this->getQuicksandURIPatternBlacklist();
        $path = $request->getPathInfo();
        foreach ($patterns as $pattern) {
            if (preg_match('(^' . $pattern . '$)', $path)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     * @throws Exception
     * @throws \ReflectionException

     * @author 陈妙威
     */
    public function getDurableColumnVisible()
    {
        $column_key = PhabricatorConpherenceColumnVisibleSetting::SETTINGKEY;
        return (bool)$this->getUserPreference($column_key, false);
    }

    /**
     * @return bool
     * @throws Exception
     * @throws \ReflectionException

     * @author 陈妙威
     */
    public function getDurableColumnMinimize()
    {
        $column_key = PhabricatorConpherenceColumnMinimizeSetting::SETTINGKEY;
        return (bool)$this->getUserPreference($column_key, false);
    }

    /**
     * @param array $config
     * @return $this
     * @author 陈妙威
     */
    public function addQuicksandConfig(array $config)
    {
        $this->quicksandConfig = $config + $this->quicksandConfig;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getQuicksandConfig()
    {
        return $this->quicksandConfig;
    }

    /**
     * @param PHUICrumbsView $crumbs
     * @return $this
     * @author 陈妙威
     */
    public function setCrumbs(PHUICrumbsView $crumbs)
    {
        $this->crumbs = $crumbs;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCrumbs()
    {
        return $this->crumbs;
    }

    /**
     * @param PHUIListView $tabs
     * @return $this
     * @author 陈妙威
     */
    public function setTabs(PHUIListView $tabs)
    {
        $tabs->setType(PHUIListView::TABBAR_LIST);
        $tabs->addClass('phabricator-standard-page-tabs');
        $this->tabs = $tabs;
        return $this;
    }

    /**
     * @return PHUIPageHeaderView
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTabs()
    {
        return $this->tabs;
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
     * @return AphrontSideNavFilterView
     * @author 陈妙威
     */
    public function getNavigation()
    {
        return $this->navigation;
    }

    /**
     * @return string
     * @throws Exception
     * @throws \ReflectionException

     * @author 陈妙威
     */
    public function getTitle()
    {
        $glyph_key = PhabricatorTitleGlyphsSetting::SETTINGKEY;
        $glyph_on = PhabricatorTitleGlyphsSetting::VALUE_TITLE_GLYPHS;
        $glyph_setting = $this->getUserPreference($glyph_key, $glyph_on);

        $use_glyph = ($glyph_setting == $glyph_on);

        $title = parent::getTitle();

        $prefix = null;
        if ($use_glyph) {
            $prefix = $this->getGlyph();
        } else {
            $application_name = $this->getApplicationName();
            if (strlen($application_name)) {
                $prefix = '[' . $application_name . ']';
            }
        }

        if (strlen($prefix)) {
            $title = $prefix . ' ' . $title;
        }

        return $title;
    }


    /**
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException*@throws Exception
     * @author 陈妙威
     */
    protected function willRenderPage()
    {
        parent::willRenderPage();

        if (!$this->getRequest()) {
            throw new Exception(
                \Yii::t("app",
                    'You must set the {0} to render a {1}.',
                    [
                        'Request',
                        __CLASS__
                    ]));
        }

//        $console = $this->getConsole();

//        require_celerity_resource('phabricator-core-css');
//        require_celerity_resource('phabricator-zindex-css');
//        require_celerity_resource('phui-button-css');
//        require_celerity_resource('phui-spacing-css');
//        require_celerity_resource('phui-form-css');
//        require_celerity_resource('phabricator-standard-page-view');
//        require_celerity_resource('conpherence-durable-column-view');
//        require_celerity_resource('font-lato');

        JavelinHtml::initBehavior(new JavelinWorkflowAsset(), array());

        $request = $this->getRequest();
        $user = null;
        if ($request) {
            $user = $request->getViewer();
        }

        if ($user) {
            if ($user->isUserActivated()) {
                $offset = $user->getTimeZoneOffset();

                $ignore_key = PhabricatorTimezoneIgnoreOffsetSetting::SETTINGKEY;
                $ignore = $user->getUserSetting($ignore_key);

                JavelinHtml::initBehavior(
                    new JavelinDetectTimezoneAsset(),
                    array(
                        'offset' => $offset,
                        'uri' => '/settings/index/timezone/',
                        'timezoneUpdateURI' => Url::to(['/settings/index/adjust']),
                        'message' => \Yii::t("app",
                            'Your browser timezone setting differs from the timezone ' .
                            'setting in your profile, click to reconcile.'),
                        'ignoreKey' => $ignore_key,
                        'ignore' => $ignore,
                    ));

                if ($user->getIsAdmin()) {
                    $server_https = $request->getIsSecureConnection();
                    $server_protocol = $server_https ? 'HTTPS' : 'HTTP';
                    $client_protocol = $server_https ? 'HTTP' : 'HTTPS';

                    $doc_name = 'Configuring a Preamble Script';
                    $doc_href = PhabricatorEnv::getDoclink($doc_name);

                    JavelinHtml::initBehavior(
                        new JavelinSetupCheckHttpsAsset(),
                        array(
                            'server_https' => $server_https,
                            'doc_name' => \Yii::t("app", 'See Documentation'),
                            'doc_href' => $doc_href,
                            'message' => \Yii::t("app",
                                'Phabricator thinks you are using {0}, but your ' .
                                'client is convinced that it is using {1}. This is a serious ' .
                                'misconfiguration with subtle, but significant, consequences.',
                                [
                                    $server_protocol, $client_protocol
                                ]),
                        ));
                }
            }

//            Javelin::initBehavior('lightbox-attachments');
        }

//        Javelin::initBehavior('aphront-form-disable-on-submit');
//        Javelin::initBehavior('toggle-class', array());
//        Javelin::initBehavior('history-install');
//        Javelin::initBehavior('phabricator-gesture');
//
//        $current_token = null;
//        if ($user) {
//            $current_token = $user->getCSRFToken();
//        }

//        Javelin::initBehavior(
//            'refresh-csrf',
//            array(
//                'tokenName' => AphrontRequest::getCSRFTokenName(),
//                'header' => AphrontRequest::getCSRFHeaderName(),
//                'viaHeader' => AphrontRequest::getViaHeaderName(),
//                'current' => $current_token,
//            ));
//
//        Javelin::initBehavior('device');
//
//        Javelin::initBehavior(
//            'high-security-warning',
//            $this->getHighSecurityWarningConfig());
//
//        if (PhabricatorEnv::isReadOnly()) {
//            Javelin::initBehavior(
//                'read-only-warning',
//                array(
//                    'message' => PhabricatorEnv::getReadOnlyMessage(),
//                    'uri' => PhabricatorEnv::getReadOnlyURI(),
//                ));
//        }

//        if ($console) {
//            require_celerity_resource('aphront-dark-console-css');
//
//            $headers = array();
//            if (DarkConsoleXHProfPluginAPI::isProfilerStarted()) {
//                $headers[DarkConsoleXHProfPluginAPI::getProfilerHeader()] = 'page';
//            }
//            if (DarkConsoleServicesPlugin::isQueryAnalyzerRequested()) {
//                $headers[DarkConsoleServicesPlugin::getQueryAnalyzerHeader()] = true;
//            }
//
//            Javelin::initBehavior(
//                'dark-console',
//                $this->getConsoleConfig());
//        }

        if ($user) {
            $viewer = $user;
        } else {
            $viewer = new PhabricatorUser();
        }

        $menu = (new PhabricatorMainMenuView())
            ->setViewer($viewer);

        if ($this->getAction()) {
            $menu->setAction($this->getAction());
        }

        $application_menu = $this->getApplicationMenu();
        if ($application_menu) {
            if ($application_menu instanceof PHUIApplicationMenuView) {
                $crumbs = $this->getCrumbs();
                if ($crumbs) {
                    $application_menu->setCrumbs($crumbs);
                }

                $application_menu = $application_menu->buildListView();
            }

            $menu->setApplicationMenu($application_menu);
        }

        $this->menuContent = $menu->render();
    }


    /**
     * @return string
     * @throws Exception
     * @throws \ReflectionException

     * @author 陈妙威
     */
    protected function getHead()
    {
        $monospaced = null;

        $request = $this->getRequest();
        if ($request) {
            $user = $request->getViewer();
            if ($user) {
                $monospaced = $user->getUserSetting(PhabricatorMonospacedFontSetting::SETTINGKEY);
            }
        }

        $response = CelerityAPI::getStaticResourceResponse();

        $font_css = null;
        if (!empty($monospaced)) {
            // We can't print this normally because escaping quotation marks will
            // break the CSS. Instead, filter it strictly and then mark it as safe.
            $monospaced = new PhutilSafeHTML(
                PhabricatorMonospacedFontSetting::filterMonospacedCSSRule(
                    $monospaced));

            $font_css = JavelinHtml::hsprintf(
                '<style type="text/css">' .
                '.PhabricatorMonospaced, ' .
                '.phabricator-remarkup .remarkup-code-block ' .
                '.remarkup-code { font: %s !important; } ' .
                '</style>',
                $monospaced);
        }

//        return JavelinHtml::hsprintf(
//            '%s%s%s',
//            parent::getHead(),
//            $font_css,
//            $response->renderSingleResource('javelin-magical-init', 'phabricator'));


        return JavelinHtml::hsprintf(
            '%s',
            parent::getHead());


    }

    /**
     * @param $glyph
     * @return $this
     * @author 陈妙威
     */
    public function setGlyph($glyph)
    {
        $this->glyph = $glyph;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getGlyph()
    {
        return $this->glyph;
    }

    /**
     * @param $response
     * @return mixed
     * @author 陈妙威
     */
    protected function willSendResponse($response)
    {
        $request = $this->getRequest();
        $response = parent::willSendResponse($response);

        $console = $request->getApplicationConfiguration()->getConsole();

        if ($console) {
            $response = PhutilSafeHTML::applyFunction(
                'str_replace',
                JavelinHtml::hsprintf('<darkconsole />'),
                $console->render($request),
                $response);
        }

        return $response;
    }

    /**
     * @return \PhutilSafeHTML
     * @throws \yii\base\Exception
     * @throws \ReflectionException

     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function getBody()
    {
        $user = null;
        $request = $this->getRequest();
        if ($request) {
            $user = $request->getViewer();
        }

        $header_chrome = null;
        if ($this->getShowChrome()) {
            $header_chrome = $this->menuContent;
        }

        $classes = array();
        $classes[] = 'main-page-frame';
        $developer_warning = null;
//        if (PhabricatorEnv::getEnvConfig('phabricator.developer-mode') &&
//            DarkConsoleErrorLogPluginAPI::getErrors()) {
//            $developer_warning = phutil_tag_div(
//                'aphront-developer-error-callout',
//                \Yii::t("app",
//                    'This page raised PHP errors. Find them in DarkConsole ' .
//                    'or the error log.'));
//        }

//        $main_page = JavelinHtml::phutil_tag(
//            'div',
//            array(
//                'id' => 'phabricator-standard-page',
//                'class' => 'phabricator-standard-page',
//            ),
//            array(
//                $developer_warning,
//                $header_chrome,
//                JavelinHtml::phutil_tag(
//                    'div',
//                    array(
//                        'id' => 'phabricator-standard-page-body',
//                        'class' => 'page-content phabricator-standard-page-body',
//                    ),
//                    $this->renderPageBodyContent()),
//            ));
        $main_page = JavelinHtml::phutil_tag("div", [
            'id' => 'phabricator-standard-page',
            'class' => 'phabricator-standard-page',
        ], array(
            JavelinHtml::phutil_implode_html("\n", array(
                $developer_warning,
                $header_chrome,
                JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'id' => 'phabricator-standard-page-body',
                        'class' => 'page-content phabricator-standard-page-body',
                    ),
                    $this->renderPageBodyContent()),
            ))
        ));
        $durable_column = null;
        if ($this->getShowDurableColumn()) {
            $is_visible = $this->getDurableColumnVisible();
            $is_minimize = $this->getDurableColumnMinimize();
            $durable_column = (new ConpherenceDurableColumnView())
                ->setSelectedConpherence(null)
                ->setViewer($user)
                ->setQuicksandConfig($this->buildQuicksandConfig())
                ->setVisible($is_visible)
                ->setMinimize($is_minimize)
                ->setInitialLoad(true);
            if ($is_minimize) {
                $this->classes[] = 'minimize-column';
            }
        }

        JavelinHtml::initBehavior(new JavelinQuicksandBlacklistBehaviorAsset(), array(
            'patterns' => $this->getQuicksandURIPatternBlacklist(),
        ));

//        return JavelinHtml::phutil_tag(
//            'div',
//            array(
//                'class' => implode(' ', $classes),
//                'id' => 'main-page-frame',
//            ),
//            array(
//                $main_page,
//                $durable_column,
//            ));

        return JavelinHtml::phutil_implode_html("\n", array(
            $main_page,
            $durable_column,
        ));
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     * @throws \PhutilInvalidStateException
     */
    private function renderPageBodyContent()
    {
//        $console = $this->getConsole();

        $body = parent::getBody();

        $header = $this->getHeader();
        $footer = $this->renderFooter();

        $nav = $this->getNavigation();
        $tabs = $this->getTabs();
        if ($nav) {
            if ($header) {
                $nav->setContentHeader($header);
            }
            $crumbs = $this->getCrumbs();
            if ($crumbs) {
                $nav->setCrumbs($crumbs);
            }
            $nav->appendChild($body);
            $nav->appendFooter($footer);
            $content = JavelinHtml::phutil_implode_html('', array($nav->render()));
        } else {
            $content = array();
            $crumbs = $this->getCrumbs();
            if ($header) {
                if ($crumbs) {
                    $header->setCrumbs($crumbs);
                }
                $content[] = $header;
            } else {
                if ($crumbs) {
                    if ($this->getTabs()) {
                        $crumbs->setBorder(true);
                    }
                    $content[] = $crumbs;
                }
            }


            $tabs = $this->getTabs();
            if ($tabs) {
                $content[] = $tabs;
            }

            $contentClass = ['content'];
            $contentClass = ArrayHelper::merge($contentClass, $this->contentClass);
            $content[] = JavelinHtml::phutil_tag("div", [
                "class" => implode(" ", $contentClass),
            ], $body);
            $content[] = $footer;

            $content = JavelinHtml::phutil_tag("div", [
                "class" => "content-wrapper",
            ], $content);
        }

        return array(
//            ($console ? JavelinHtml::hsprintf('<darkconsole />') : null),
            $content,
        );
    }

    /**
     * @return array|string
     * @throws Exception
     * @author 陈妙威
     */
    protected function getTail()
    {
        $request = $this->getRequest();
        $user = $request->getViewer();

        $tail = array(
            parent::getTail(),
        );

        $response = CelerityAPI::getStaticResourceResponse();

        if ($request->getIsSecureConnection()) {
            $with_protocol = 'https';
        } else {
            $with_protocol = 'http';
        }

        $servers = PhabricatorNotificationServerRef::getEnabledClientServers($with_protocol);

        if ($servers) {
            if ($user && $user->isLoggedIn()) {
                // TODO: We could tell the browser about all the servers and let it
                // do random reconnects to improve reliability.
                shuffle($servers);
                $server = OranginsUtil::head($servers);

                $client_uri = $server->getWebsocketURI();

                Javelin::initBehavior(
                    'aphlict-listen',
                    array(
                        'websocketURI' => (string)$client_uri,
                    ) + $this->buildAphlictListenConfigData());

                CelerityAPI::getStaticResourceResponse()
                    ->addContentSecurityPolicyURI('connect-src', $client_uri);
            }
        }

//        $tail[] = $response->renderHTMLFooter($this->getFrameable());

        return $tail;
    }

    /**
     * @return null|string
     * @throws Exception
     * @author 陈妙威
     */
    protected function getBodyClasses()
    {
        $classes = array();
        $classes[] = 'navbar-top';

        if (!$this->getShowChrome()) {
            $classes[] = 'phabricator-chromeless-page';
        }
        $agent = $this->getRequest()->getHeaders()->get("User-Agent");

        // Try to guess the device resolution based on UA strings to avoid a flash
        // of incorrectly-styled content.
        $device_guess = 'device-desktop';
        if (preg_match('@iPhone|iPod|(Android.*Chrome/[.0-9]* Mobile)@', $agent)) {
            $device_guess = 'device-phone device';
        } else if (preg_match('@iPad|(Android.*Chrome/)@', $agent)) {
            $device_guess = 'device-tablet device';
        }

        $classes[] = $device_guess;

        if (preg_match('@Windows@', $agent)) {
            $classes[] = 'platform-windows';
        } else if (preg_match('@Macintosh@', $agent)) {
            $classes[] = 'platform-mac';
        } else if (preg_match('@X11@', $agent)) {
            $classes[] = 'platform-linux';
        }

        if ($this->getRequest()->getStr('__print__')) {
            $classes[] = 'printable';
        }

        if ($this->getRequest()->getStr('__aural__')) {
            $classes[] = 'audible';
        }

        $classes[] = 'phui-theme-' . PhabricatorEnv::getEnvConfig('ui.header-color');
        foreach ($this->classes as $class) {
            $classes[] = $class;
        }

        $viewer = $this->getUser();
        if ($viewer && $viewer->getPHID()) {
            $scope_key = PhabricatorSidebarToggleSetting::SETTINGKEY;
            $current_value = $viewer->getUserSetting($scope_key);
            if(!$current_value) {
                $classes[] = 'sidebar-xs';
            }
        }

        return implode(' ', $classes);
    }

    /**
     * @return null
     * @author 陈妙威
     */
    private function getConsole()
    {
        if ($this->disableConsole) {
            return null;
        }
        return $this->getRequest()->getApplicationConfiguration()->getConsole();
    }

    /**
     * @return array
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function getConsoleConfig()
    {
        $user = $this->getRequest()->getViewer();

        $headers = array();
        if (DarkConsoleXHProfPluginAPI::isProfilerStarted()) {
            $headers[DarkConsoleXHProfPluginAPI::getProfilerHeader()] = 'page';
        }
        if (DarkConsoleServicesPlugin::isQueryAnalyzerRequested()) {
            $headers[DarkConsoleServicesPlugin::getQueryAnalyzerHeader()] = true;
        }

        if ($user) {
            $setting_tab = PhabricatorDarkConsoleTabSetting::SETTINGKEY;
            $setting_visible = PhabricatorDarkConsoleVisibleSetting::SETTINGKEY;
            $tab = $user->getUserSetting($setting_tab);
            $visible = $user->getUserSetting($setting_visible);
        } else {
            $tab = null;
            $visible = true;
        }

        return array(
            // NOTE: We use a generic label here to prevent input reflection
            // and mitigate compression attacks like BREACH. See discussion in
            // T3684.
            'uri' => \Yii::t("app", 'Main Request'),
            'selected' => $tab,
            'visible' => $visible,
            'headers' => $headers,
        );
    }

    /**
     * @return array
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    private function getHighSecurityWarningConfig()
    {
        $user = $this->getRequest()->getViewer();

        $show = false;
        if ($user->hasSession()) {
            $hisec = ($user->getSession()->getHighSecurityUntil() - time());
            if ($hisec > 0) {
                $show = true;
            }
        }

        return array(
            'show' => $show,
            'uri' => '/auth/session/downgrade/',
            'message' => \Yii::t("app",
                'Your session is in high security mode. When you ' .
                'finish using it, click here to leave.'),
        );
    }

    /**
     * @return null
     * @throws Exception
     * @author 陈妙威
     */
    private function renderFooter()
    {
        if (!$this->getShowChrome()) {
            return null;
        }

        if (!$this->getShowFooter()) {
            return null;
        }

        $items = PhabricatorEnv::getEnvConfig('ui.footer-items');
        if (!$items) {
            return null;
        }

        $foot = array();
        foreach ($items as $item) {
            $name = ArrayHelper::getValue($item, 'name', \Yii::t("app", 'Unnamed Footer Item'));

            $href = ArrayHelper::getValue($item, 'href');
            if (!PhabricatorEnv::isValidURIForLink($href)) {
                $href = null;
            }

            if ($href !== null) {
                $tag = 'a';
            } else {
                $tag = 'span';
            }

            $foot[] = JavelinHtml::phutil_tag(
                $tag,
                array(
                    'href' => $href,
                ),
                $name);
        }
        $foot = JavelinHtml::phutil_implode_html(" \xC2\xB7 ", $foot);

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phabricator-standard-page-footer grouped',
            ),
            $foot);
    }

    /**
     * @return array
     * @throws Exception
     * @throws \ReflectionException

     * @throws \PhutilInvalidStateException
     * @thr'ows \Seld\JsonLint\ParsingException
     * @author 陈妙威
     */
    public function renderForQuicksand()
    {
        parent::willRenderPage();
        $response = $this->renderPageBodyContent();
        $response = $this->willSendResponse($response);

        $extra_config = $this->getQuicksandConfig();

        return array(
                'content' => JavelinHtml::hsprintf('%s', $response),
            ) + $this->buildQuicksandConfig()
            + $extra_config;
    }

    /**
     * @return array
     * @throws Exception
     * @throws \ReflectionException

     * @author 陈妙威
     */
    private function buildQuicksandConfig()
    {
        $viewer = $this->getRequest()->getViewer();
        $action = $this->getAction();

        $dropdown_query = (new AphlictDropdownDataQuery())
            ->setViewer($viewer);
        $dropdown_query->execute();

        $hisec_warning_config = $this->getHighSecurityWarningConfig();

        $console_config = null;
        $console = $this->getConsole();
        if ($console) {
            $console_config = $this->getConsoleConfig();
        }

        $upload_enabled = false;
        if ($action) {
            $upload_enabled = $action->isGlobalDragAndDropUploadEnabled();
        }

        $application_class = null;
        $application_search_icon = null;
        $application_help = null;
        $action = $this->getAction();
        if ($action) {
            $application = $action->controller->getCurrentApplication();
            if ($application) {
                $application_class = get_class($application);
                if ($application->getApplicationSearchDocumentTypes()) {
                    $application_search_icon = $application->getIcon();
                }

                $help_items = $application->getHelpMenuItems($viewer);
                if ($help_items) {
                    $help_list = (new PhabricatorActionListView())
                        ->setViewer($viewer);
                    foreach ($help_items as $help_item) {
                        $help_list->addAction($help_item);
                    }
                    $application_help = $help_list->getDropdownMenuMetadata();
                }
            }
        }

        return array(
                'title' => $this->getTitle(),
                'bodyClasses' => $this->getBodyClasses(),
                'aphlictDropdownData' => array(
                    $dropdown_query->getNotificationData(),
                    $dropdown_query->getConpherenceData(),
                ),
                'globalDragAndDrop' => $upload_enabled,
                'hisecWarningConfig' => $hisec_warning_config,
                'consoleConfig' => $console_config,
                'applicationClass' => $application_class,
                'applicationSearchIcon' => $application_search_icon,
                'helpItems' => $application_help,
            ) + $this->buildAphlictListenConfigData();
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws Exception
     */
    private function buildAphlictListenConfigData()
    {
        $user = $this->getRequest()->getViewer();
        $subscriptions = $this->pageObjects;
        $subscriptions[] = $user->getPHID();

        return array(
            'pageObjects' => array_fill_keys($this->pageObjects, true),
            'subscriptions' => $subscriptions,
        );
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getQuicksandURIPatternBlacklist()
    {
        $applications = PhabricatorApplication::getAllApplicationsWithShortNameKey();

        $blacklist = array();
        foreach ($applications as $application) {
            $blacklist[] = $application->getQuicksandURIPatternBlacklist();
        }

        // See T4340. Currently, Phortune and Auth both require pulling in external
        // Javascript (for Stripe card management and Recaptcha, respectively).
        // This can put us in a position where the user loads a page with a
        // restrictive Content-Security-Policy, then uses Quicksand to navigate to
        // a page which needs to load external scripts. For now, just blacklist
        // these entire applications since we aren't giving up anything
        // significant by doing so.

        $blacklist[] = array(
            '/phortune/.*',
            '/auth/.*',
        );

        return OranginsUtil::array_mergev($blacklist);
    }

    /**
     * @param $key
     * @param null $default
     * @return null
     * @throws Exception
     * @throws \ReflectionException

     * @author 陈妙威
     */
    private function getUserPreference($key, $default = null)
    {
        $request = $this->getRequest();
        if (!$request) {
            return $default;
        }

        $user = $request->getViewer();
        if (!$user) {
            return $default;
        }

        return $user->getUserSetting($key);
    }

    /**
     * @return AphrontResponse
     * @throws Exception
     * @throws \ReflectionException

     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function produceAphrontResponse()
    {
        $action = $this->getAction();

        if (!$this->getApplicationMenu()) {
            $application_menu = $action->buildApplicationMenu();
            if ($application_menu) {
                $this->setApplicationMenu($application_menu);
            }
        }

        $viewer = $this->getUser();
        if ($viewer && $viewer->getPHID()) {
            $object_phids = $this->pageObjects;
            foreach ($object_phids as $object_phid) {
                PhabricatorFeedStoryNotification::updateObjectNotificationViews(
                    $viewer,
                    $object_phid);
            }
        }

        if ($this->getRequest()->isQuicksand()) {
            $content = $this->renderForQuicksand();
            $response = (new AphrontAjaxResponse())
                ->setContent($content);
        } else {
            $content = $this->render();

            $response = (new AphrontWebpageResponse())
                ->setContent($content)
                ->setFrameable($this->getFrameable());

            $static = CelerityAPI::getStaticResourceResponse();
            foreach ($static->getContentSecurityPolicyURIMap() as $kind => $uris) {
                foreach ($uris as $uri) {
                    $response->addContentSecurityPolicyURI($kind, $uri);
                }
            }
        }

        return $response;
    }
}