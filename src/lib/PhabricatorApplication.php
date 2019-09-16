<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/23
 * Time: 1:32 PM
 */

namespace orangins\lib;

use Filesystem;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\request\AphrontRequest;
use orangins\modules\meta\editor\PhabricatorApplicationEditor;
use orangins\modules\meta\models\PhabricatorApplicationApplicationTransaction;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;
use PhutilMethodNotImplementedException;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\modules\cache\PhabricatorCaches;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use PhutilClassMapQuery;
use Yii;
use Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorApplication
 * @package orangins\lib
 */
abstract class PhabricatorApplication extends Module
    implements PhabricatorPolicyInterface
//    ,PhabricatorApplicationTransactionInterface
{
    /**
     *
     */
    const GROUP_CORE = 'core';
    /**
     *
     */
    const GROUP_UTILITIES = 'util';
    /**
     *
     */
    const GROUP_ADMIN = 'admin';
    /**
     *
     */
    const GROUP_DEVELOPER = 'developer';

    /**
     * @var int
     */
    public static $applicationId = 0;

    /**
     * @var string
     */
    public $defaultRoute = '/home/index/index';

    /**
     * PhabricatorApplication constructor.
     * @throws PhutilMethodNotImplementedException
     */
    public function __construct()
    {
        parent::__construct($this->applicationId());
    }

    /**
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    public function init()
    {
        parent::init();
        $this->controllerNamespace = $this->controllerNamespace();
        $this->defaultRoute = $this->defaultRoute();
    }

    /**
     * @author 陈妙威
     * @return string
     * @throws PhutilMethodNotImplementedException
     */
    public function applicationId()
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @author 陈妙威
     * @return string
     * @throws PhutilMethodNotImplementedException
     */
    public function controllerNamespace()
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @author 陈妙威
     * @return string
     * @throws PhutilMethodNotImplementedException
     */
    public function defaultRoute()
    {
        throw new PhutilMethodNotImplementedException();
    }


    /**
     * @return array
     * @author 陈妙威
     */
    final public static function getApplicationGroups()
    {
        return array(
            self::GROUP_CORE => \Yii::t("app", 'Core Applications'),
            self::GROUP_UTILITIES => \Yii::t("app", 'Utilities'),
            self::GROUP_ADMIN => \Yii::t("app", 'Administration'),
            self::GROUP_DEVELOPER => \Yii::t("app", 'Developer Tools'),
        );
    }

    /**
     * Determine if an application is installed and available to a viewer, by
     * application class name.
     *
     * To check if an application is installed at all, use
     * @{method:isClassInstalled}.
     *
     * @param string Application class name.
     * @param PhabricatorUser $viewer Viewing user.
     * @return bool True if the class is installed for the viewer.
     * @task meta
     * @throws Exception
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     */
    final public static function isClassInstalledForViewer(
        $class,
        PhabricatorUser $viewer)
    {
        if ($viewer->isOmnipotent()) {
            return true;
        }

        $cache = PhabricatorCaches::getRequestCache();
        $viewer_fragment = $viewer->getCacheFragment();
        $key = 'app.' . $class . '.installed.' . $viewer_fragment;

        $result = $cache->getKey($key);
        if ($result === null) {
            if (!self::isClassInstalled($class)) {
                $result = false;
            } else {
                $application = self::getByClass($class);
                if (!$application->canUninstall()) {
                    // If the application can not be uninstalled, always allow viewers
                    // to see it. In particular, this allows logged-out viewers to see
                    // Settings and load global default settings even if the install
                    // does not allow public viewers.
                    $result = true;
                } else {
                    $result = PhabricatorPolicyFilter::hasCapability(
                        $viewer,
                        self::getByClass($class),
                        PhabricatorPolicyCapability::CAN_VIEW);
                }
            }

            $cache->setKey($key, $result);
        }

        return $result;
    }

    /**
     * @return PhabricatorApplication[]
     * @author 陈妙威
     */
    public static function getAllApplications()
    {
        static $applications;

        if ($applications === null) {
            $query = new PhutilClassMapQuery();
            $apps = $query
                ->setAncestorClass(__CLASS__)
                ->setSortMethod('getApplicationOrder')
                ->execute();

            // Reorder the applications into "application order". Notably, this
            // ensures their event handlers register in application order.
            $apps = mgroup($apps, 'getApplicationGroup');

            $group_order = array_keys(self::getApplicationGroups());
            $apps = array_select_keys($apps, $group_order) + $apps;

            $apps = array_mergev($apps);

            $applications = $apps;
        }

        return $applications;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getAllApplicationsWithShortNameKey()
    {
        static $applicationsWithShortNameKey;

        if ($applicationsWithShortNameKey === null) {
            $query = new PhutilClassMapQuery();
            $query->setUniqueMethod('getClassShortName');
            $apps = $query
                ->setAncestorClass(__CLASS__)
                ->setSortMethod('getApplicationOrder')
                ->execute();

            // Reorder the applications into "application order". Notably, this
            // ensures their event handlers register in application order.
            $apps = mgroup($apps, 'getApplicationGroup');

            $group_order = array_keys(self::getApplicationGroups());
            $apps = array_select_keys($apps, $group_order) + $apps;

            $apps = array_mergev($apps);

            $applicationsWithShortNameKey = $apps;
        }

        return $applicationsWithShortNameKey;
    }

    /**
     * @return PhabricatorApplication[]
     * @author 陈妙威
     */
    final public static function getAllInstalledApplications()
    {
        $all_applications = self::getAllApplicationsWithShortNameKey();
        $apps = array();
        foreach ($all_applications as $app) {
            if (!$app->isInstalled()) {
                continue;
            }

            $apps[] = $app;
        }

        return $apps;
    }


    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    final public function isInstalled()
    {
        if (!$this->canUninstall()) {
            return true;
        }

        $prototypes = PhabricatorEnv::getEnvConfig('orangins.show-prototypes');
        if (!$prototypes && $this->isPrototype()) {
            return false;
        }

        $uninstalled = PhabricatorEnv::getEnvConfig('orangins.uninstalled-applications');

        return empty($uninstalled[get_class($this)]);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isPrototype()
    {
        return false;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getApplicationOrder()
    {
        return PHP_INT_MAX;
    }

    /**
     * 获取ICON
     * @return string
     */
    public function getIcon()
    {
        return 'fa-puzzle-piece';
    }

    /**
     *
     * @param PhabricatorUser $viewer
     * @return bool
     */
    public function isPinnedByDefault(PhabricatorUser $viewer)
    {
        return false;
    }

    /**
     * @return string
     */
    public function getTitleGlyph()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getApplicationGroup()
    {
        return self::GROUP_CORE;
    }

    /**
     * 是否可以卸载
     * @return bool
     */
    public function canUninstall()
    {
        return true;
    }

    /**
     * Return `true` if this application is a normal application with a base
     * URI and a web interface.
     *
     * Launchable applications can be pinned to the home page, and show up in the
     * "Launcher" view of the Applications application. Making an application
     * unlaunchable prevents pinning and hides it from this view.
     *
     * Usually, an application should be marked unlaunchable if:
     *
     *   - it is available on every page anyway (like search); or
     *   - it does not have a web interface (like subscriptions); or
     *   - it is still pre-release and being intentionally buried.
     *
     * To hide applications more completely, use @{method:isUnlisted}.
     *
     * @return bool True if the application is launchable.
     */
    public function isLaunchable()
    {
        return true;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getRemarkupRules()
    {
        return array();
    }


    /**
     * 获取应用名称
     * @return string
     */
    abstract public function getName();

    /**
     * 获取应用简介
     * @return string
     */
    public function getShortDescription()
    {
        return Yii::t('app', '{0} Application', [$this->getName()]);
    }

    /**
     * 获取后台任务类的列表
     * @see PhabricatorWorker
     * @return string[]
     * @author 陈妙威
     */
    public function getWorkers()
    {
        return [];
    }

    /**
     * 获取用户配置类的列表
     * @see PhabricatorSelectSetting
     * @return string[]
     * @author 陈妙威
     */
    public function getSettings()
    {
        return [];
    }

    /**
     * 获取用户配置仪表类的列表
     * @see PhabricatorSettingsPanelView
     * @return string[]
     * @author 陈妙威
     */
    public function getSettingPanels()
    {
        return [];
    }

    /**
     * 获取用户配置仪表组类的列表
     * @see PhabricatorSettingsPanelGroup
     * @return string[]
     * @author 陈妙威
     */
    public function getSettingPanelGroups()
    {
        return [];
    }

    /**
     * @return array
     * @see PhabricatorEventListener
     * @author 陈妙威
     */
    public function getEventListeners()
    {
        return array();
    }


    /**
     * @param $class_name
     * @return PhabricatorApplication
     * @throws Exception
     * @author 陈妙威
     */
    final public static function getByClass($class_name)
    {
        $selected = null;
        $applications = self::getAllApplicationsWithShortNameKey();

        foreach ($applications as $application) {
            if (get_class($application) == $class_name) {
                $selected = $application;
                break;
            }
        }

        if (!$selected) {
            throw new Exception(\Yii::t("app", "No application '{0}'!", [$class_name]));
        }

        return $selected;
    }

    /**
     * @param string $path
     * @param array $params
     * @return string
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    public function getApplicationURI($path = null, $params = [])
    {
        if ($path === null && $this->defaultRoute) {
            return Url::to([$this->defaultRoute]);
        } else {
            if ($path === null) {
                $path = 'index/index';
            }
            $baseUri = $this->applicationId();
            $str = "/{$baseUri}/" . $path;
            $to = Url::to(ArrayHelper::merge([$str], $params));
            return $to;
        }
    }


    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */


    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getCapabilities()
    {
        return array_merge(
            array(
                PhabricatorPolicyCapability::CAN_VIEW,
                PhabricatorPolicyCapability::CAN_EDIT,
            ),
            array_keys($this->getCustomCapabilities()));
    }

    /**
     * @param $capability
     * @return mixed|string
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        $default = $this->getCustomPolicySetting($capability);
        if ($default) {
            return $default;
        }

        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                return PhabricatorPolicies::getMostOpenPolicy();
            case PhabricatorPolicyCapability::CAN_EDIT:
                return PhabricatorPolicies::POLICY_ADMIN;
            default:
                $spec = $this->getCustomCapabilitySpecification($capability);
                return ArrayHelper::getValue($spec, 'default', PhabricatorPolicies::POLICY_USER);
        }
    }

    /**
     * @param $capability
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final private function getCustomCapabilitySpecification($capability)
    {
        $custom = $this->getCustomCapabilities();
        if (!isset($custom[$capability])) {
            throw new Exception(\Yii::t("app", "Unknown capability '{0}'!", [$capability]));
        }
        return $custom[$capability];
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool|mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return false;
    }

    /**
     * @return string
     * @author 陈妙威
     * @throws \ReflectionException
     */
    public function getPHID()
    {
        return 'PHID-APPS-' . $this->getClassShortName();
    }

    /* -(  Policies  )----------------------------------------------------------- */

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getCustomCapabilities()
    {
        return array();
    }

    /**
     * @param $capability
     * @return mixed|null
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final private function getCustomPolicySetting($capability)
    {
        if (!$this->isCapabilityEditable($capability)) {
            return null;
        }

        $policy_locked = PhabricatorEnv::getEnvConfig('policy.locked');
        if (isset($policy_locked[$capability])) {
            return $policy_locked[$capability];
        }

        $config = PhabricatorEnv::getEnvConfig('orangins.application-settings');

        $app = ArrayHelper::getValue($config, $this->getPHID());
        if (!$app) {
            return null;
        }

        $policy = ArrayHelper::getValue($app, 'policy');
        if (!$policy) {
            return null;
        }

        return ArrayHelper::getValue($policy, $capability);
    }

    /**
     * @param $capability
     * @return bool|mixed
     * @throws Exception
     * @author 陈妙威
     */
    final public function isCapabilityEditable($capability)
    {
        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                return $this->canUninstall();
            case PhabricatorPolicyCapability::CAN_EDIT:
                return true;
            default:
                $spec = $this->getCustomCapabilitySpecification($capability);
                return ArrayHelper::getValue($spec, 'edit', true);
        }
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getQuicksandURIPatternBlacklist()
    {
        return array();
    }


    /**
     * @return array
     * @author 陈妙威
     */
    public function getApplicationSearchDocumentTypes()
    {
        return array();
    }


    /**
     * @param PhabricatorUser $viewer
     * @return array
     * @author 陈妙威
     */
    public function getHelpDocumentationArticles(PhabricatorUser $viewer)
    {
        return array();
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getMailCommandObjects()
    {
        return array();
    }

    /**
     * @param PhabricatorUser $viewer
     * @return array
     * @author 陈妙威
     */
    final public function getHelpMenuItems(PhabricatorUser $viewer)
    {
        $items = array();

        $articles = $this->getHelpDocumentationArticles($viewer);
        if ($articles) {
            foreach ($articles as $article) {
                $item = (new PhabricatorActionView())
                    ->setName($article['name'])
                    ->setHref($article['href'])
                    ->addSigil('help-item')
                    ->setOpenInNewWindow(true);
                $items[] = $item;
            }
        }

        $command_specs = $this->getMailCommandObjects();
        if ($command_specs) {
            foreach ($command_specs as $key => $spec) {
                $object = $spec['object'];

                $class = get_class($this);
                $href = '/applications/mailcommands/' . $class . '/' . $key . '/';
                $item = (new PhabricatorActionView())
                    ->setName($spec['name'])
                    ->setHref($href)
                    ->addSigil('help-item')
                    ->setOpenInNewWindow(true);
                $items[] = $item;
            }
        }

        if ($items) {
            $divider = (new PhabricatorActionView())
                ->addSigil('help-item')
                ->setType(PhabricatorActionView::TYPE_DIVIDER);
            array_unshift($items, $divider);
        }

        return array_values($items);
    }

    /**
     * Return `true` if this application should never appear in application lists
     * in the UI. Primarily intended for unit test applications or other
     * pseudo-applications.
     *
     * Few applications should be unlisted. For most applications, use
     * @{method:isLaunchable} to hide them from main launch views instead.
     *
     * @return bool True to remove application from UI lists.
     */
    public function isUnlisted()
    {
        return false;
    }


    /**
     * @throws \ReflectionException
     * @return string
     * @author 陈妙威
     */
    public function getClassShortName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    public function getTypeaheadURI()
    {
        $applicationId = $this->applicationId();
        return $this->isLaunchable() ? Url::to(["/{$applicationId}/"]) : null;
    }


    /**
     * Returns true if an application is first-party (developed by Phacility)
     * and false otherwise.
     *
     * @return bool True if this application is developed by Phacility.
     * @throws \ReflectionException
     */
    final public function isFirstParty()
    {
        $where = (new \ReflectionClass($this))->getFileName();
        $root = phutil_get_library_root('orangins');

        if (!Filesystem::isDescendant($where, $root)) {
            return false;
        }

        if (Filesystem::isDescendant($where, $root . '/extensions')) {
            return false;
        }

        return true;
    }


    /**
     * Determine if an application is installed, by application class name.
     *
     * To check if an application is installed //and// available to a particular
     * viewer, user @{method:isClassInstalledForViewer}.
     *
     * @param string  Application class name.
     * @return bool   True if the class is installed.
     * @throws Exception
     * @task meta
     */
    final public static function isClassInstalled($class)
    {
        return self::getByClass($class)->isInstalled();
    }


    /**
     * @return array
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public function getDefaultObjectTypePolicyMap()
    {
        $map = array();

        foreach ($this->getCustomCapabilities() as $capability => $spec) {
            if (empty($spec['template'])) {
                continue;
            }
            if (empty($spec['capability'])) {
                continue;
            }
            $default = $this->getPolicy($capability);
            $map[$spec['template']][$spec['capability']] = $default;
        }

        return $map;
    }

    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return PhabricatorApplicationEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorApplicationEditor();
    }

    /**
     * @return $this
     * @author 陈妙威
     */
    public function getApplicationTransactionObject()
    {
        return $this;
    }

    /**
     * @return PhabricatorApplicationApplicationTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorApplicationApplicationTransaction();
    }

    /**
     * @param PhabricatorApplicationTransactionView $timeline
     * @param AphrontRequest $request
     * @return PhabricatorApplicationTransactionView
     * @author 陈妙威
     */
    public function willRenderTimeline(
        PhabricatorApplicationTransactionView $timeline,
        AphrontRequest $request)
    {

        return $timeline;

    }
}