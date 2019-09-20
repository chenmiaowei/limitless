<?php

namespace orangins\modules\settings\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use orangins\modules\settings\query\PhabricatorUserPreferencesSearchEngine;
use yii\helpers\Url;

/**
 * Class PhabricatorSettingsListAction
 * @package orangins\modules\settings\actions
 * @author 陈妙威
 */
final class PhabricatorSettingsListAction
    extends PhabricatorAction
{

    /**
     * @return AphrontRedirectResponse
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        // If the viewer isn't an administrator, just redirect them to their own
        // settings panel.
        if (!$viewer->getIsAdmin()) {
            $settings_uri = Url::to(['/settings/index/user', 'username' => $viewer->getUsername()]);
            return (new AphrontRedirectResponse())
                ->setURI($settings_uri);
        }

        return (new PhabricatorUserPreferencesSearchEngine())
            ->setAction($this)
            ->buildResponse();
    }

    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();

        $viewer = $this->getViewer();
        if ($viewer->getIsAdmin()) {
            $builtin_global = PhabricatorUserPreferences::BUILTIN_GLOBAL_DEFAULT;
            $global_settings = PhabricatorUserPreferences::find()
                ->setViewer($viewer)
                ->withBuiltinKeys(
                    array(
                        $builtin_global,
                    ))
                ->execute();
            if (!$global_settings) {
                $action = (new PHUIListItemView())
                    ->setName(\Yii::t("app", 'Create Global Defaults'))
                    ->setHref('/settings/builtin/' . $builtin_global . '/')
                    ->setIcon('fa-plus');
                $crumbs->addAction($action);
            }
        }

        return $crumbs;
    }
}
