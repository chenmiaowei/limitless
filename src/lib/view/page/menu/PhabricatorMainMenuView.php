<?php

namespace orangins\lib\view\page\menu;

use orangins\modules\aphlict\assets\JavelinAphlictDropdownBehaviorAsset;
use orangins\modules\aphlict\query\AphlictDropdownDataQuery;
use orangins\lib\actions\PhabricatorAction;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\file\favicon\PhabricatorFaviconRefQuery;
use orangins\modules\notification\application\PhabricatorNotificationsApplication;
use orangins\modules\settings\panel\PhabricatorEmailAddressesSettingsPanel;
use orangins\modules\widgets\javelin\JavelinSidebarToggleAsset;
use PhutilSafeHTML;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\lib\view\phui\PHUIListView;
use orangins\modules\cache\PhabricatorCaches;
use orangins\modules\config\check\PhabricatorSetupCheck;
use orangins\modules\config\customer\PhabricatorCustomLogoConfigType;
use orangins\modules\file\favicon\PhabricatorFaviconRef;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\people\models\PhabricatorUser;
use orangins\lib\view\AphrontView;
use yii\helpers\Url;

/**
 * Class PhabricatorMainMenuView
 * @package orangins\lib\view\page\menu
 * @author 陈妙威
 */
final class PhabricatorMainMenuView extends AphrontView
{


    /**
     * @var PhabricatorAction
     */
    private $action;
    /**
     * @var
     */
    private $applicationMenu;

    /**
     * @param PHUIListView $application_menu
     * @return $this
     * @author 陈妙威
     */
    public function setApplicationMenu(PHUIListView $application_menu)
    {
        $this->applicationMenu = $application_menu;
        return $this;
    }

    /**
     * @return PHUIListView
     * @author 陈妙威
     */
    public function getApplicationMenu()
    {
        return $this->applicationMenu;
    }

    /**
     * @return PhabricatorAction
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param PhabricatorAction $action
     * @return self
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    private static function getFavicons()
    {
        $refs = array();

        $refs['favicon'] = (new PhabricatorFaviconRef())
            ->setWidth(64)
            ->setHeight(64);

        $refs['message_favicon'] = (new PhabricatorFaviconRef())
            ->setWidth(64)
            ->setHeight(64)
            ->setEmblems(
                array(
                    'dot-pink',
                    null,
                    null,
                    null,
                ));

        (new PhabricatorFaviconRefQuery())
            ->withRefs($refs)
            ->execute();

        return OranginsUtil::mpull($refs, 'getURI');
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        $viewer = $this->getViewer();

//        require_celerity_resource('phabricator-main-menu-view');

        $header_id = JavelinHtml::generateUniqueNodeId();
        $menu_bar = array();
        $alerts = array();
        $search_button = '';
        $app_button = '';
        $aural = null;
        $dropdowns = null;

        $is_full = $this->isFullSession($viewer);

        if ($is_full) {
            list($menu, $dropdowns, $aural) = $this->renderNotificationMenu();
            if (array_filter($menu)) {
                $alerts[] = $menu;
            }
            $app_button = $this->renderApplicationMenuButton();
//            $search_button = $this->renderSearchMenuButton($header_id);
        } else if (!$viewer->isLoggedIn()) {
            $app_button = $this->renderApplicationMenuButton();
//            if (PhabricatorEnv::getEnvConfig('policy.allow-public')) {
//                $search_button = $this->renderSearchMenuButton($header_id);
//            }
        }

//        if ($search_button) {
//        $search_menu = $this->renderPhabricatorSearchMenu();
//        } else {
//            $search_menu = null;
//        }

        if ($alerts) {
            $alerts = JavelinHtml::phutil_tag(
                'ul',
                array(
                    'class' => 'navbar-nav phabricator-main-menu-alerts',
                    'aural' => false,
                ),
                $alerts);
        }

        if ($aural) {
            $aural = JavelinHtml::phutil_tag(
                'span',
                array(
                    'aural' => true,
                ),
                JavelinHtml::phutil_implode_html(' ', $aural));
        }

        $extensions = PhabricatorMainMenuBarExtension::getAllEnabledExtensions();
        foreach ($extensions as $extension) {
            $extension
                ->setViewer($viewer)
                ->setIsFullSession($is_full);

            $action = $this->getAction();
            if ($action) {
                $extension->setAction($action);
                $application = $action->controller->getCurrentApplication();
                if ($application) {
                    $extension->setApplication($application);
                }
            }
        }

        if (!$is_full) {
            foreach ($extensions as $key => $extension) {
                if ($extension->shouldRequireFullSession()) {
                    unset($extensions[$key]);
                }
            }
        }

        foreach ($extensions as $key => $extension) {
            if (!$extension->isExtensionEnabledForViewer($extension->getViewer())) {
                unset($extensions[$key]);
            }
        }

        $menus = array();
        foreach ($extensions as $extension) {
            foreach ($extension->buildMainMenus() as $menu) {
                $menus[] = $menu;
            }
        }

        // Because we display these with "float: right", reverse their order before
        // rendering them into the document so that the extension order and display
        // order are the same.
        $menus = array_reverse($menus);

        foreach ($menus as $menu) {
            $menu_bar[] = JavelinHtml::phutil_tag("li", ["class" => "nav-item"], $menu);
        }

        $classes = array();
        $classes[] = 'navbar navbar-expand-md navbar-dark fixed-top bg-' . PhabricatorEnv::getEnvConfig("ui.header-color") . ' navbar-static phabricator-main-menu';


//        <div class="d-md-none">
//			<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-mobile">
//				<i class="icon-tree5"></i>
//			</button>
//			<button class="navbar-toggler sidebar-mobile-main-toggle" type="button">
//				<i class="icon-paragraph-justify3"></i>
//			</button>
//		</div>

        $mobileMenuButton = JavelinHtml::phutil_tag("div", [
            "class" => "d-md-none"
        ], [
            JavelinHtml::phutil_tag("button", [
                "class" => "navbar-toggler",
                "type" => "button",
                "data-toggle" => "collapse",
                "data-target" => "#navbar-mobile",
            ], new PhutilSafeHTML('<i class="icon-tree5"></i>')),
            JavelinHtml::phutil_tag("button", [
                "class" => "navbar-toggler sidebar-mobile-main-toggle",
                "type" => "button",
            ], new PhutilSafeHTML('<i class="icon-paragraph-justify3"></i>')),
        ]);


//        <ul class="navbar-nav">
//				<li class="nav-item">
//					<a href="#" class="navbar-nav-link sidebar-control sidebar-main-toggle d-none d-md-block legitRipple">
//						<i class="icon-paragraph-justify3"></i>
//					</a>
//				</li>
//			</ul>

        $toggleButton = JavelinHtml::phutil_tag("ul", [
            "class" => "navbar-nav",
        ], JavelinHtml::phutil_tag("li", [
            "class" => "nav-item"
        ], JavelinHtml::phutil_tag("a", [
            "href" => "#",
            "class" => "navbar-nav-link sidebar-control sidebar-main-toggle d-none d-md-block legitRipple",
            'sigil' => 'sidebar-main-toggle'
        ], new PhutilSafeHTML('<i class="icon-paragraph-justify3"></i>'))));


        JavelinHtml::initBehavior(new JavelinSidebarToggleAsset(), [
            'update_uri' => Url::to(['/people/index/sidebar-toggle'])
        ]);

        $phutilSafeHTML = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => implode(' ', $classes),
                'id' => $header_id,
            ),
            array(
//                $app_button,
//                $search_button,
                $this->renderPhabricatorLogo(),
                $mobileMenuButton,
                JavelinHtml::phutil_tag("div", [
                    "class" => "collapse navbar-collapse",
                    "id" => "navbar-mobile"
                ], [
                    $toggleButton,
//                    $search_menu,
                    $alerts,
                    $dropdowns,
                    JavelinHtml::phutil_tag("ul", ["class" => "navbar-nav ml-auto"], $menu_bar),
                    $aural,
                ])
            ));
        return $phutilSafeHTML;
    }

    /**
     * @return PhabricatorMainMenuSearchView|null
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderSearch()
    {
        $viewer = $this->getViewer();

        $result = null;

        $keyboard_config = array(
            'helpURI' => '/help/keyboardshortcut/',
        );

        if ($viewer->isLoggedIn()) {
            $show_search = $viewer->isUserActivated();
        } else {
            $show_search = PhabricatorEnv::getEnvConfig('policy.allow-public');
        }

        if ($show_search) {
            $search = new PhabricatorMainMenuSearchView();
            $search->setViewer($viewer);

            $application = null;
            $action = $this->getAction();
            if ($action) {
                $application = $action->controller->getCurrentApplication();
            }
            if ($application) {
                $search->setApplication($application);
            }

            $result = $search;
            $keyboard_config['searchID'] = $search->getID();
        }

        $keyboard_config['pht'] = array(
            '/' => \Yii::t("app", 'Give keyboard focus to the search box.'),
            '?' => \Yii::t("app", 'Show keyboard shortcut help for the current page.'),
        );

//        Javelin::initBehavior(
//            'phabricator-keyboard-shortcuts',
//            $keyboard_config);
//
        if ($result) {
            $result = (new PHUIListItemView())
                ->addClass('phabricator-main-menu-search')
                ->appendChild($result);
        }

        return $result;
    }

    /**
     * @return null
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function renderApplicationMenuButton()
    {
        $dropdown = $this->renderApplicationMenu();
        if (!$dropdown) {
            return null;
        }

        return (new PHUIButtonView())
            ->setTag('a')
            ->setHref('#')
            ->setIcon('fa-bars')
            ->addClass('phabricator-core-user-menu')
            ->addClass('phabricator-core-user-mobile-menu')
            ->setNoCSS(true)
            ->setDropdownMenu($dropdown)
            ->setAuralLabel(\Yii::t("app", 'Page Menu'));
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    private function renderApplicationMenu()
    {
        $viewer = $this->getViewer();
        $view = $this->getApplicationMenu();
        if ($view) {
            $items = $view->getItems();
            $view = (new PhabricatorActionListView())
                ->setViewer($viewer);
            foreach ($items as $item) {
                $view->addAction(
                    (new PhabricatorActionView())
                        ->setName($item->getName())
                        ->setHref($item->getHref())
                        ->setType($item->getType()));
            }
        }
        return $view;
    }

    /**
     * @param $header_id
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function renderSearchMenuButton($header_id)
    {
        $button_id = JavelinHtml::generateUniqueNodeId();
        return JavelinHtml::phutil_tag(
            'a',
            array(
                'class' => 'phabricator-main-menu-search-button ' .
                    'phabricator-expand-application-menu',
                'sigil' => 'jx-toggle-class',
                'meta' => array(
                    'map' => array(
                        $header_id => 'phabricator-search-menu-expanded',
                        $button_id => 'menu-icon-selected',
                    ),
                ),
            ),
            JavelinHtml::phutil_tag(
                'span',
                array(
                    'class' => 'phabricator-menu-button-icon mr-1 ' .
                        'fa fa-search',
                    'id' => $button_id,
                ),
                ''));
    }

    /**
     * @return PHUIListView
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderPhabricatorSearchMenu()
    {

        $view = new PHUIListView();
        $view->addClass('ml-auto phabricator-search-menu');

        $search = $this->renderSearch();
        if ($search) {
            $view->addMenuItem($search);
        }

        return $view;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderPhabricatorLogo()
    {
        $custom_header = PhabricatorCustomLogoConfigType::getLogoImagePHID();

        $logo_style = array();
        if ($custom_header) {
            $cache = PhabricatorCaches::getImmutableCache();
            $cache_key_logo = 'ui.custom-header.logo-phid.v3.' . $custom_header;

            $logo_uri = $cache->getKey($cache_key_logo);
            if (!$logo_uri) {
                // NOTE: If the file policy has been changed to be restrictive, we'll
                // miss here and just show the default logo. The cache will fill later
                // when someone who can see the file loads the page. This might be a
                // little spooky, see T11982.
                $files = PhabricatorFile::find()
                    ->setViewer($this->getViewer())
                    ->withPHIDs(array($custom_header))
                    ->execute();
                $file = OranginsUtil::head($files);
                if ($file) {
                    $logo_uri = $file->getViewURI();
                    $cache->setKey($cache_key_logo, $logo_uri);
                }
            }

            if ($logo_uri) {
                $logo_style[] = 'background-size: 40px 40px;';
                $logo_style[] = 'background-position: 0 0;';
                $logo_style[] = 'background-image: url(' . $logo_uri . ')';
            }
        }

        $logo_node = JavelinHtml::phutil_tag(
            'span',
            array(
                'class' => 'fa fa-eye font-size-sm mr-2 phabricator-main-menu-eye',
                'style' => implode(' ', $logo_style),
            ));


        $wordmark_text = PhabricatorCustomLogoConfigType::getLogoWordmark();
        if (!strlen($wordmark_text)) {
            $wordmark_text = PhabricatorEnv::getEnvConfig("orangins.site-name");
        }

        $wordmark_node = JavelinHtml::phutil_tag(
            'span',
            array(
                'class' => 'font-size-sm 
                phabricator-wordmark',
            ),
            $wordmark_text);

        return JavelinHtml::phutil_tag(
            'a',
            array(
                'class' => 'navbar-brand',
                'href' => \Yii::$app->getHomeUrl(),
            ),
            array(
                JavelinHtml::phutil_tag(
                    'span',
                    array(
                        'aural' => true,
                    ),
                    \Yii::t("app", 'Home')),
                $logo_node,
                $wordmark_node,
            ));
    }

    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderNotificationMenu()
    {
        $viewer = $this->getViewer();

//        require_celerity_resource('phabricator-notification-css');
//        require_celerity_resource('phabricator-notification-menu-css');

        $container_classes = array('navbar-nav-link legitRipple alert-notifications');
        $aural = array();

        $dropdown_query = (new AphlictDropdownDataQuery())
            ->setViewer($viewer);
        $dropdown_data = $dropdown_query->execute();

//        $message_tag = '';
//        $message_notification_dropdown = '';
//        $conpherence_app = PhabricatorConpherenceApplication::class;
//        $conpherence_data = $dropdown_data[$conpherence_app];
//        if ($conpherence_data['isInstalled']) {
//            $message_id = JavelinHtml::generateUniqueNodeId();
//            $message_count_id = JavelinHtml::generateUniqueNodeId();
//            $message_dropdown_id = JavelinHtml::generateUniqueNodeId();
//
//            $message_count_number = $conpherence_data['rawCount'];
//
//            if ($message_count_number) {
//                $aural[] = JavelinHtml::phutil_tag(
//                    'a',
//                    array(
//                        'href' => '/conpherence/',
//                    ),
//                    \Yii::t("app",
//                        '%s unread messages.',
//                        new PhutilNumber($message_count_number)));
//            } else {
//                $aural[] = \Yii::t("app", 'No messages.');
//            }
//
//            $message_count_tag = JavelinHtml::phutil_tag(
//                'span',
//                array(
//                    'id' => $message_count_id,
//                    'class' => 'phabricator-main-menu-message-count',
//                ),
//                $conpherence_data['count']);
//
//            $message_icon_tag = JavelinHtml::phutil_tag(
//                'span',
//                array(
//                    'class' => 'phabricator-main-menu-message-icon mr-1 ' .
//                        'fa fa-comments',
//                    'sigil' => 'menu-icon',
//                ),
//                '');
//
//            if ($message_count_number) {
//                $container_classes[] = 'message-unread';
//            }
//
//            $message_tag = JavelinHtml::phutil_tag("li", [
//                "class" => "nav-item"
//            ], JavelinHtml::phutil_tag(
//                'a',
//                array(
//                    'href' => '/conpherence/',
//                    'class' => implode(' ', $container_classes),
//                    'id' => $message_id,
//                ),
//                array(
//                    $message_icon_tag,
//                    $message_count_tag,
//                )));
//
//            JavelinHtml::initBehavior(
//                new JavelinAphlictDropdownBehaviorAsset(),
//                array(
//                    'bubbleID' => $message_id,
//                    'countID' => $message_count_id,
//                    'dropdownID' => $message_dropdown_id,
//                    'loadingText' => \Yii::t("app", 'Loading...'),
//                    'uri' => Url::to(['/conpherence/index/panel']),
//                    'countType' => $conpherence_data['countType'],
//                    'countNumber' => $message_count_number,
//                    'unreadClass' => 'message-unread',
//                ) + self::getFavicons());
//
//            $message_notification_dropdown = JavelinHtml::phutil_tag(
//                'div',
//                array(
//                    'id' => $message_dropdown_id,
//                    'class' => 'phabricator-notification-menu',
//                    'sigil' => 'phabricator-notification-menu',
//                    'style' => 'display: none;',
//                ),
//                '');
//        }

//        $bubble_tag = '';
//        $notification_dropdown = '';
//        $notification_app = PhabricatorNotificationsApplication::class;
//        $notification_data = $dropdown_data[$notification_app];
//        if ($notification_data['isInstalled']) {
//            $count_id = JavelinHtml::generateUniqueNodeId();
//            $dropdown_id = JavelinHtml::generateUniqueNodeId();
//            $bubble_id = JavelinHtml::generateUniqueNodeId();
//
//            $count_number = $notification_data['rawCount'];
//
//            if ($count_number) {
//                $aural[] = JavelinHtml::phutil_tag(
//                    'a',
//                    array(
//                        'href' => '/notification/',
//                    ),
//                    \Yii::t("app",
//                        '{0} unread notifications.', [
//                            $count_number
//                        ]));
//            } else {
//                $aural[] = \Yii::t("app", 'No notifications.');
//            }
//
//            $count_tag = JavelinHtml::phutil_tag(
//                'span',
//                array(
//                    'id' => $count_id,
//                    'class' => 'phabricator-main-menu-alert-count',
//                ),
//                $notification_data['count']);
//
//            $icon_tag = JavelinHtml::phutil_tag(
//                'span',
//                array(
//                    'class' => 'phabricator-main-menu-alert-icon mr-1 ' .
//                        'fa fa-bell',
//                    'sigil' => 'menu-icon',
//                ),
//                '');
//
//            if ($count_number) {
//                $container_classes[] = 'alert-unread';
//            }
//
//            $bubble_tag = JavelinHtml::phutil_tag("li", [
//                "class" => "nav-item"
//            ], JavelinHtml::phutil_tag(
//                'a',
//                array(
//                    'href' => '/notification/',
//                    'class' => implode(' ', $container_classes),
//                    'id' => $bubble_id,
//                ),
//                array($icon_tag, $count_tag)));
//
//            JavelinHtml::initBehavior(
//                new JavelinAphlictDropdownBehaviorAsset(),
//                array(
//                    'bubbleID' => $bubble_id,
//                    'countID' => $count_id,
//                    'dropdownID' => $dropdown_id,
//                    'loadingText' => \Yii::t("app", 'Loading...'),
//                    'uri' => Url::to(['/notification/index/panel']),
//                    'countType' => $notification_data['countType'],
//                    'countNumber' => $count_number,
//                    'unreadClass' => 'alert-unread',
//                ) + self::getFavicons());
//
//            $notification_dropdown = JavelinHtml::phutil_tag(
//                'div',
//                array(
//                    'id' => $dropdown_id,
//                    'class' => 'phabricator-notification-menu',
//                    'sigil' => 'phabricator-notification-menu',
//                    'style' => 'display: none;',
//                ),
//                '');
//        }
//
//        // Admin Level Urgent Notification Channel
//        $setup_tag = '';
//        $setup_notification_dropdown = '';
//        if ($viewer && $viewer->getIsAdmin()) {
//            $open = PhabricatorSetupCheck::getOpenSetupIssueKeys();
//            if ($open) {
//                $setup_id = JavelinHtml::generateUniqueNodeId();
//                $setup_count_id = JavelinHtml::generateUniqueNodeId();
//                $setup_dropdown_id = JavelinHtml::generateUniqueNodeId();
//
//                $setup_count_number = count($open);
//
//                if ($setup_count_number) {
//                    $aural[] = JavelinHtml::phutil_tag(
//                        'a',
//                        array(
//                            'href' => '/config/issue/',
//                        ),
//                        \Yii::t("app",
//                            '{0} unresolved issues.', [
//                                $setup_count_number
//                            ]));
//                } else {
//                    $aural[] = \Yii::t("app", 'No issues.');
//                }
//
//                $setup_count_tag = JavelinHtml::phutil_tag(
//                    'span',
//                    array(
//                        'id' => $setup_count_id,
//                        'class' => 'phabricator-main-menu-setup-count',
//                    ),
//                    $setup_count_number);
//
//                $setup_icon_tag = JavelinHtml::phutil_tag(
//                    'span',
//                    array(
//                        'class' => 'phabricator-main-menu-setup-icon mr-2 ' .
//                            'fa fa-exclamation-circle',
//                        'sigil' => 'menu-icon',
//                    ),
//                    '');
//
//                if ($setup_count_number) {
//                    $container_classes[] = 'navbar-nav-link legitRipple setup-unread';
//                }
//
//                $setup_tag = JavelinHtml::phutil_tag("li", [
//                    "class" => "nav-item",
//                ], JavelinHtml::phutil_tag(
//                    'a',
//                    array(
//                        'href' => '/config/issue/',
//                        'class' => implode(' ', $container_classes),
//                        'id' => $setup_id,
//                    ),
//                    array(
//                        $setup_icon_tag,
//                        $setup_count_tag,
//                    )));
//
//                JavelinHtml::initBehavior(
//                    new JavelinAphlictDropdownBehaviorAsset(),
//                    array(
//                        'bubbleID' => $setup_id,
//                        'countID' => $setup_count_id,
//                        'dropdownID' => $setup_dropdown_id,
//                        'loadingText' => \Yii::t("app", 'Loading...'),
//                        'uri' => Url::to(['/config/issue/panel']),
//                        'countType' => null,
//                        'countNumber' => null,
//                        'unreadClass' => 'setup-unread',
//                    ) + self::getFavicons());
//
//                $setup_notification_dropdown = JavelinHtml::phutil_tag(
//                    'div',
//                    array(
//                        'id' => $setup_dropdown_id,
//                        'class' => 'phabricator-notification-menu',
//                        'sigil' => 'phabricator-notification-menu',
//                        'style' => 'display: none;',
//                    ),
//                    '');
//            }
//        }

//        $user_dropdown = null;
        $user_tag = null;
//        if ($viewer->isLoggedIn()) {
//            if (!$viewer->getIsEmailVerified()) {
//                $bubble_id = JavelinHtml::generateUniqueNodeId();
//                $count_id = JavelinHtml::generateUniqueNodeId();
//                $dropdown_id = JavelinHtml::generateUniqueNodeId();
//
//                $settings_uri = (new PhabricatorEmailAddressesSettingsPanel())
//                    ->setViewer($viewer)
//                    ->setUser($viewer)
//                    ->getPanelURI();
//
//                $user_icon = JavelinHtml::phutil_tag(
//                    'span',
//                    array(
//                        'class' => 'phabricator-main-menu-setup-icon mr-2 ' .
//                            'fa fa-user',
//                        'sigil' => 'menu-icon',
//                    ));
//
//                $user_count = JavelinHtml::phutil_tag(
//                    'span',
//                    array(
//                        'class' => 'phabricator-main-menu-setup-count',
//                        'id' => $count_id,
//                    ),
//                    1);
//
//                $user_tag = JavelinHtml::phutil_tag("li", [
//                    "class" => "nav-item"
//                ], JavelinHtml::phutil_tag(
//                    'a',
//                    array(
//                        'href' => $settings_uri,
//                        'class' => 'navbar-nav-link legitRipple setup-unread',
//                        'id' => $bubble_id,
//                    ),
//                    array(
//                        $user_icon,
//                        $user_count,
//                    )));
//
////                JavelinHtml::initBehavior(
////                    new JavelinAphlictDropdownBehaviorAsset(),
////                    array(
////                        'bubbleID' => $bubble_id,
////                        'countID' => $count_id,
////                        'dropdownID' => $dropdown_id,
////                        'loadingText' => \Yii::t("app", 'Loading...'),
////                        'uri' => Url::to(['/settings/index/issue']),
////                        'unreadClass' => 'setup-unread',
////                    ));
//
////                $user_dropdown = JavelinHtml::phutil_tag(
////                    'div',
////                    array(
////                        'id' => $dropdown_id,
////                        'class' => 'phabricator-notification-menu',
////                        'sigil' => 'phabricator-notification-menu',
////                        'style' => 'display: none;',
////                    ));
//            }
//        }

        $dropdowns = array(
//            $notification_dropdown,
//            $message_notification_dropdown,
//            $setup_notification_dropdown,
//            $user_dropdown,
        );

        return array(
            array(
//                $bubble_tag,
//                $message_tag,
//                $setup_tag,
                $user_tag,
            ),
            $dropdowns,
            $aural,
        );
    }

    /**
     * @param PhabricatorUser $viewer
     * @return bool
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    private function isFullSession(PhabricatorUser $viewer)
    {
        if (!$viewer->isLoggedIn()) {
            return false;
        }

        if (!$viewer->isUserActivated()) {
            return false;
        }

        if (!$viewer->hasSession()) {
            return false;
        }

        $session = $viewer->getSession();
        if ($session->getIsPartial()) {
            return false;
        }

        if (!$session->getSignedLegalpadDocuments()) {
            return false;
        }

        $mfa_key = 'security.require-multi-factor-auth';
        $need_mfa = PhabricatorEnv::getEnvConfig($mfa_key);
        if ($need_mfa) {
            $have_mfa = $viewer->getIsEnrolledInMultiFactor();
            if (!$have_mfa) {
                return false;
            }
        }

        return true;
    }

}
