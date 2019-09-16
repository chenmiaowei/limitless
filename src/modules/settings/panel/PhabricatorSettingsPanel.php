<?php

namespace orangins\modules\settings\panel;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\OranginsObject;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\settings\editors\PhabricatorUserPreferencesEditor;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use orangins\modules\settings\panelgroup\PhabricatorSettingsPanelGroup;
use PhutilClassMapQuery;
use PhutilSortVector;
use Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Defines a settings panel. Settings panels appear in the Settings application,
 * and behave like lightweight controllers -- generally, they render some sort
 * of form with options in it, and then update preferences when the user
 * submits the form. By extending this class, you can add new settings
 * panels.
 *
 * @task config   Panel Configuration
 * @task panel    Panel Implementation
 * @task internal Internals
 */
abstract class PhabricatorSettingsPanel extends OranginsObject
{

    /**
     * @var PhabricatorUser
     */
    private $user;
    /**
     * @var PhabricatorUser
     */
    private $viewer;
    /**
     * @var PhabricatorAction
     */
    private $action;
    /**
     * @var
     */
    private $navigation;
    /**
     * @var
     */
    private $overrideURI;
    /**
     * @var
     */
    private $preferences;

    /**
     * @param PhabricatorUser $user
     * @return $this
     * @author 陈妙威
     */
    public function setUser(PhabricatorUser $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return PhabricatorUser
     * @author 陈妙威
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param $override_uri
     * @return $this
     * @author 陈妙威
     */
    public function setOverrideURI($override_uri)
    {
        $this->overrideURI = $override_uri;
        return $this;
    }

    /**
     * @param PhabricatorAction $controller
     * @return $this
     * @author 陈妙威
     */
    final public function setAction(PhabricatorAction $controller)
    {
        $this->action = $controller;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getAction()
    {
        return $this->action;
    }

    /**
     * @param AphrontSideNavFilterView $navigation
     * @return $this
     * @author 陈妙威
     */
    final public function setNavigation(AphrontSideNavFilterView $navigation)
    {
        $this->navigation = $navigation;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getNavigation()
    {
        return $this->navigation;
    }

    /**
     * @param PhabricatorUserPreferences $preferences
     * @return static
     * @author 陈妙威
     */
    public function setPreferences(PhabricatorUserPreferences $preferences)
    {
        $this->preferences = $preferences;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPreferences()
    {
        return $this->preferences;
    }

    /**
     * @return \list
     * @throws \Exception
     * @author 陈妙威
     */
    final public static function getAllPanels()
    {
        $panels = (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getPanelKey')
            ->execute();

        $settings = \Yii::$app->params['settings'];
        foreach ($panels as $k => $panel) {
            $get_class = get_class($panel);
            if(!in_array($get_class, $settings)) {
                unset($panels[$k]);
            }
        }
        return msortv($panels, 'getPanelOrderVector');
    }

    /**
     * @return PhabricatorSettingsPanel[]
     * @author 陈妙威
     * @throws \Exception
     */
    final public static function getAllDisplayPanels()
    {
        $panels = array();
        $groups = PhabricatorSettingsPanelGroup::getAllPanelGroupsWithPanels();
        foreach ($groups as $group) {
            foreach ($group->getPanels() as $key => $panel) {
                $panels[$key] = $panel;
            }
        }

        return $panels;
    }

    /**
     * @return object
     * @author 陈妙威
     * @throws \Exception
     */
    final public function getPanelGroup()
    {
        $group_key = $this->getPanelGroupKey();

        $groups = PhabricatorSettingsPanelGroup::getAllPanelGroupsWithPanels();
        $group = ArrayHelper::getValue($groups, $group_key);
        if (!$group) {
            throw new Exception(
                \Yii::t("app",
                    'No settings panel group with key "%s" exists!',
                    $group_key));
        }

        return $group;
    }


    /* -(  Panel Configuration  )------------------------------------------------ */


    /**
     * Return a unique string used in the URI to identify this panel, like
     * "example".
     *
     * @return string Unique panel identifier (used in URIs).
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @task config
     */
    public function getPanelKey()
    {
        return $this->getPhobjectClassConstant('PANELKEY');
    }


    /**
     * Return a human-readable description of the panel's contents, like
     * "Example Settings".
     *
     * @return string Human-readable panel name.
     * @task config
     */
    abstract public function getPanelName();


    /**
     * Return a panel group key constant for this panel.
     *
     * @return const Panel group key.
     * @task config
     */
    abstract public function getPanelGroupKey();


    /**
     * Return false to prevent this panel from being displayed or used. You can
     * do, e.g., configuration checks here, to determine if the feature your
     * panel controls is unavailable in this install. By default, all panels are
     * enabled.
     *
     * @return bool True if the panel should be shown.
     * @task config
     */
    public function isEnabled()
    {
        return true;
    }


    /**
     * Return true if this panel is available to users while editing their own
     * settings.
     *
     * @return bool True to enable management on behalf of a user.
     * @task config
     */
    public function isUserPanel()
    {
        return true;
    }


    /**
     * Return true if this panel is available to administrators while managing
     * bot and mailing list accounts.
     *
     * @return bool True to enable management on behalf of accounts.
     * @task config
     */
    public function isManagementPanel()
    {
        return false;
    }


    /**
     * Return true if this panel is available while editing settings templates.
     *
     * @return bool True to allow editing in templates.
     * @task config
     */
    public function isTemplatePanel()
    {
        return false;
    }


    /* -(  Panel Implementation  )----------------------------------------------- */


    /**
     * Process a user request for this settings panel. Implement this method like
     * a lightweight controller. If you return an @{class:AphrontResponse}, the
     * response will be used in whole. If you return anything else, it will be
     * treated as a view and composed into a normal settings page.
     *
     * Generally, render your settings panel by returning a form, then return
     * a redirect when the user saves settings.
     *
     * @param   AphrontRequest  Incoming request.
     * @return  wild            Response to request, either as an
     *                          @{class:AphrontResponse} or something which can
     *                          be composed into a @{class:AphrontView}.
     * @task panel
     */
    abstract public function processRequest(AphrontRequest $request);


    /**
     * Get the URI for this panel.
     *
     * @param string? Optional path to append.
     * @return string Relative URI for the panel.
     * @throws Exception
     * @throws \ReflectionException
     * @task panel
     */
    final public function getPanelURI($path = '')
    {
        $path = ltrim($path, '/');

        if ($this->overrideURI) {
            return rtrim($this->overrideURI, '/') . '/' . $path;
        }

        $key = $this->getPanelKey();
        $key = phutil_escape_uri($key);

        $user = $this->getUser();
        if ($user) {
            if ($user->isLoggedIn()) {
                return Url::to([
                    '/settings/index/user'
                    , 'username' => $user->getUsername()
                    , 'pageKey' => $key
                    , 'formSaved' => $path
                    ]);
            } else {
                // For logged-out users, we can't put their username in the URI. This
                // page will prompt them to login, then redirect them to the correct
                // location.
                return "/settings/panel/{$key}/";
            }
        } else {
            $builtin = $this->getPreferences()->getBuiltinKey();
            return "/settings/builtin/{$builtin}/page/{$key}/{$path}";
        }
    }


    /* -(  Internals  )---------------------------------------------------------- */


    /**
     * Generates a key to sort the list of panels.
     *
     * @return string Sortable key.
     * @task internal
     * @throws \Exception
     */
    final public function getPanelOrderVector()
    {
        return (new PhutilSortVector())
            ->addString($this->getPanelName());
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function newDialog()
    {
        return $this->getAction()->newDialog();
    }

    /**
     * @param PhabricatorUserPreferences $preferences
     * @param $key
     * @param $value
     * @throws Exception
     * @throws \PhutilInvalidStateException
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
    protected function writeSetting(
        PhabricatorUserPreferences $preferences,
        $key,
        $value)
    {
        $viewer = $this->getViewer();
        $request = $this->getAction()->getRequest();

        $editor = (new PhabricatorUserPreferencesEditor())
            ->setActor($viewer)
            ->setContentSourceFromRequest($request)
            ->setContinueOnNoEffect(true)
            ->setContinueOnMissingFields(true);

        $xactions = array();
        $xactions[] = $preferences->newTransaction($key, $value);
        $editor->applyTransactions($preferences, $xactions);
    }


    /**
     * @param $title
     * @param $content
     * @param array $actions
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    public function newBox($title, $content, $actions = array())
    {
        $header = (new PHUIHeaderView())
            ->setHeader($title);

        foreach ($actions as $action) {
            $header->addActionLink($action);
        }

        $view = (new PHUIObjectBoxView())
            ->setHeader($header)
            ->appendChild($content)
            ->setBackground(PHUIObjectBoxView::WHITE_CONFIG);

        return $view;
    }

}
