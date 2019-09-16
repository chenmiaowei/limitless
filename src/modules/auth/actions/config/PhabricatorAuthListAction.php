<?php

namespace orangins\modules\auth\actions\config;

use orangins\lib\env\PhabricatorEnv;
use orangins\modules\auth\capability\AuthManageProvidersCapability;
use orangins\modules\auth\guidance\PhabricatorAuthProvidersGuidanceContext;
use orangins\modules\auth\models\PhabricatorAuthProviderConfig;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\guides\guidance\PhabricatorGuidanceEngine;

/**
 * Class PhabricatorAuthListAction
 * @package orangins\modules\auth\actions\config
 * @author 陈妙威
 */
final class PhabricatorAuthListAction extends PhabricatorAuthProviderConfigAction
{

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $viewer = $this->getViewer();

        /** @var PhabricatorAuthProviderConfig[] $configs */
        $configs = PhabricatorAuthProviderConfig::find()
            ->setViewer($viewer)
            ->execute();

        $list = new PHUIObjectItemListView();
        $can_manage = $this->hasApplicationCapability(AuthManageProvidersCapability::CAPABILITY);

        foreach ($configs as $config) {
            $item = new PHUIObjectItemView();

            $id = $config->getID();

            $edit_uri = $this->getApplicationURI('config/edit', [
                'id' => $id
            ]);
            $enable_uri = $this->getApplicationURI('config/enable', [
                'id' => $id
            ]);
            $disable_uri = $this->getApplicationURI('config/disable', [
                'id' => $id
            ]);

            $provider = $config->getProvider();
            if ($provider) {
                $name = $provider->getProviderName();
            } else {
                $name = $config->getProviderType() . ' (' . $config->getProviderClass() . ')';
            }

            $item->setHeader($name);

            if ($provider) {
                $item->setHref($edit_uri);
            } else {
                $item->addAttribute(\Yii::t("app", 'Provider Implementation Missing!'));
            }

            $domain = null;
            if ($provider) {
                $domain = $provider->getProviderDomain();
                if ($domain !== 'self') {
                    $item->addAttribute($domain);
                }
            }

            if ($config->getShouldAllowRegistration()) {
                $item->addAttribute(\Yii::t("app", 'Allows Registration'));
            } else {
                $item->addAttribute(\Yii::t("app", 'Does Not Allow Registration'));
            }

            if ($config->getIsEnabled()) {
                $item->setStatusIcon('fa-check-circle green');
                $item->addAction(
                    (new PHUIListItemView())
                        ->setIcon('fa-times')
                        ->setHref($disable_uri)
                        ->setDisabled(!$can_manage)
                        ->addSigil('workflow'));
            } else {
                $item->setStatusIcon('fa-ban red');
                $item->addIcon('fa-ban grey', \Yii::t("app", 'Disabled'));
                $item->addAction(
                    (new PHUIListItemView())
                        ->setIcon('fa-plus')
                        ->setHref($enable_uri)
                        ->setDisabled(!$can_manage)
                        ->addSigil('workflow'));
            }

            $list->addItem($item);
        }

        $no_data_string = \Yii::t("app",
            '{0} You have not added authentication providers yet. Use "{1}" to add ' .
            'a provider, which will let users register new Phabricator accounts ' .
            'and log in.', [
                JavelinHtml::phutil_tag(
                    'strong',
                    array(),
                    \Yii::t("app", 'No Providers Configured:')),
                JavelinHtml::phutil_tag(
                    'a',
                    array(
                        'href' => $this->getApplicationURI('config/new'),
                    ),
                    \Yii::t("app", 'Add Authentication Provider'))
            ]);
        $list->setNoDataString($no_data_string);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'Auth Providers'));
        $crumbs->setBorder(true);

        $guidance_context = new PhabricatorAuthProvidersGuidanceContext();

        $guidance = (new PhabricatorGuidanceEngine())
            ->setViewer($viewer)
            ->setGuidanceContext($guidance_context)
            ->newInfoView();


        $list->setFlush(true);
        $list = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Providers'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->addBodyClass("p-0")
            ->appendChild($list);
        $view = (new PHUITwoColumnView())
            ->setFooter(array(
                $guidance,
                $list,
            ));


        $button = (new PHUIButtonView())
            ->setTag('a')
            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
            ->setHref($this->getApplicationURI('config/new'))
            ->setIcon('fa-plus')
            ->setDisabled(!$can_manage)
            ->setText(\Yii::t("app", 'Add Provider'));
        $title = \Yii::t("app", 'Auth Providers');
        $header = (new PHUIPageHeaderView())
            ->setHeader($title)
            ->setHeaderIcon('fa-key')
            ->addActionLink($button);


        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

}
