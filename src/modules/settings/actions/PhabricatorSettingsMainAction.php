<?php

namespace orangins\modules\settings\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\response\AphrontResponse;
use orangins\lib\response\AphrontResponseProducerInterface;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use orangins\modules\settings\panel\PhabricatorSettingsPanel;
use PhutilURI;
use Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorSettingsMainAction
 * @package orangins\modules\settings\actions
 * @author 陈妙威
 */
final class PhabricatorSettingsMainAction
    extends PhabricatorAction
{

    /**
     * @var
     */
    private $user;
    /**
     * @var
     */
    private $builtinKey;
    /**
     * @var
     */
    private $preferences;

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getUser()
    {
        return $this->user;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    private function isSelf()
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        $user_phid = $user->getPHID();

        $viewer_phid = $this->getViewer()->getPHID();
        return ($viewer_phid == $user_phid);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    private function isTemplate()
    {
        return ($this->builtinKey !== null);
    }

    /**
     * @return Aphront404Response|AphrontResponse|AphrontResponseProducerInterface|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \PhutilMethodNotImplementedException
     * @throws Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        // Redirect "/panel/XYZ/" to the viewer's personal settings panel. This
        // was the primary URI before global settings were introduced and allows
        // generation of viewer-agnostic URIs for email and logged-out users.
        $panel = $request->getURIData('panel');
        if ($panel) {
            $panel = phutil_escape_uri($panel);
            $username = $viewer->getUsername();

            $panel_uri = "/user/{$username}/page/{$panel}/";
            $panel_uri = $this->getApplicationURI($panel_uri);
            return (new AphrontRedirectResponse())->setURI($panel_uri);
        }

        $username = $request->getURIData('username');
        $builtin = $request->getURIData('builtin');

        $key = $request->getURIData('pageKey');

        if ($builtin) {
            $this->builtinKey = $builtin;

            $preferences = PhabricatorUserPreferences::find()
                ->setViewer($viewer)
                ->withBuiltinKeys(array($builtin))
                ->requireCapabilities(
                    array(
                        PhabricatorPolicyCapability::CAN_VIEW,
                        PhabricatorPolicyCapability::CAN_EDIT,
                    ))
                ->executeOne();
            if (!$preferences) {
                $preferences = (new PhabricatorUserPreferences())
                    ->attachUser(null)
                    ->setBuiltinKey($builtin);
            }
        } else {
            $user = PhabricatorUser::find()
                ->setViewer($viewer)
                ->withUsernames(array($username))
                ->requireCapabilities(
                    array(
                        PhabricatorPolicyCapability::CAN_VIEW,
                        PhabricatorPolicyCapability::CAN_EDIT,
                    ))
                ->executeOne();

            if (!$user) {
                return new Aphront404Response();
            }

            $preferences = PhabricatorUserPreferences::loadUserPreferences($user);
            $this->user = $user;
        }

        if (!$preferences) {
            return new Aphront404Response();
        }

        PhabricatorPolicyFilter::requireCapability(
            $viewer,
            $preferences,
            PhabricatorPolicyCapability::CAN_EDIT);

        $this->preferences = $preferences;

        $panels = $this->buildPanels($preferences);
        $nav = $this->renderSideNav($panels);

        $key = $nav->selectFilter($key, head($panels)->getPanelKey());

        /** @var PhabricatorSettingsPanel $var */
        $var = $panels[$key];
        $panel = $var
            ->setAction($this)
            ->setNavigation($nav);

        $response = $panel->processRequest($request);
        if (($response instanceof AphrontResponse) ||
            ($response instanceof AphrontResponseProducerInterface)) {
            return $response;
        }

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($panel->getPanelName());
        $crumbs->setBorder(true);

        if ($this->user) {
            $header_text = \Yii::t("app", 'Edit Settings ({0})', [$user->getUserName()]);
        } else {
            $header_text = \Yii::t("app", 'Edit Global Settings');
        }

        $header = (new PHUIHeaderView())
            ->setHeader($header_text);

        $title = $panel->getPanelName();

        $view = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setFixed(true)
            ->setNavigation($nav)
            ->setMainColumn($response);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);

    }

    /**
     * @param PhabricatorUserPreferences $preferences
     * @return array
     * @author 陈妙威
     * @throws Exception
     * @throws \Exception
     */
    private function buildPanels(PhabricatorUserPreferences $preferences)
    {
        $viewer = $this->getViewer();
        $panels = PhabricatorSettingsPanel::getAllDisplayPanels();

        $result = array();
        foreach ($panels as $key => $panel) {
            $panel
                ->setPreferences($preferences)
                ->setViewer($viewer);

            if ($this->user) {
                $panel->setUser($this->user);
            }

            if (!$panel->isEnabled()) {
                continue;
            }

            if ($this->isTemplate()) {
                if (!$panel->isTemplatePanel()) {
                    continue;
                }
            } else {
                if (!$this->isSelf() && !$panel->isManagementPanel()) {
                    continue;
                }

                if ($this->isSelf() && !$panel->isUserPanel()) {
                    continue;
                }
            }

            if (!empty($result[$key])) {
                throw new Exception(\Yii::t("app",
                    "Two settings panels share the same panel key ('%s'): %s, %s.",
                    $key,
                    get_class($panel),
                    get_class($result[$key])));
            }

            $result[$key] = $panel;
        }

        if (!$result) {
            throw new Exception(\Yii::t("app", 'No settings panels are available.'));
        }

        return $result;
    }

    /**
     * @param PhabricatorSettingsPanel[] $panels
     * @return AphrontSideNavFilterView
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderSideNav(array $panels)
    {
        $nav = new AphrontSideNavFilterView();

        if ($this->isTemplate()) {
            $url = ['builtin' => $this->builtinKey];
        } else {
            $user = $this->getUser();
            $url = ['username' => $user->getUsername()];
        }

        $nav->setBaseURI(new PhutilURI($this->getApplicationURI('index/index')));

        $group_key = null;
        foreach ($panels as $panel) {
            if ($panel->getPanelGroupKey() != $group_key) {
                $group_key = $panel->getPanelGroupKey();
                $group = $panel->getPanelGroup();
                $panel_name = $group->getPanelGroupName();
                if ($panel_name) {
                    $nav->addLabel($panel_name);
                }
            }

            $nav->addFilter($panel->getPanelKey(), $panel->getPanelName(), Url::to(ArrayHelper::merge(['/settings/index/user'], $url, [
                "pageKey" => $panel->getPanelKey(),
            ])));
        }

        return $nav;
    }

    /**
     * @return null
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        if ($this->preferences) {
            $panels = $this->buildPanels($this->preferences);
            return $this->renderSideNav($panels)->getMenu();
        }
        return parent::buildApplicationMenu();
    }

    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \yii\base\Exception
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();

        $user = $this->getUser();
        if (!$this->isSelf() && $user) {
            $username = $user->getUsername();
            $crumbs->addTextCrumb($username, Url::to(['/people/index/view', 'username' => $username]));
        }

        return $crumbs;
    }

}
