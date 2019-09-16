<?php

namespace orangins\modules\conduit\settings;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUITagView;
use orangins\modules\conduit\models\PhabricatorConduitToken;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\settings\panel\PhabricatorSettingsPanel;
use orangins\modules\settings\panelgroup\PhabricatorSettingsLogsPanelGroup;
use PhutilInvalidStateException;
use ReflectionException;
use yii\helpers\Url;

/**
 * Class PhabricatorConduitTokensSettingsPanel
 * @author 陈妙威
 */
final class PhabricatorConduitTokensSettingsPanel
    extends PhabricatorSettingsPanel
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isManagementPanel()
    {
//        if ($this->getUser()->getIsMailingList()) {
//            return false;
//        }

        return true;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelKey()
    {
        return 'apitokens';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return \Yii::t("app", 'Conduit API Tokens');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPanelGroupKey()
    {
        return PhabricatorSettingsLogsPanelGroup::PANELGROUPKEY;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isEnabled()
    {
        return true;
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws \yii\base\Exception
     * @throws \ReflectionException*@throws \Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function processRequest(AphrontRequest $request)
    {
        $viewer = $this->getViewer();
        $user = $this->getUser();

        /** @var PhabricatorConduitToken[] $tokens */
        $tokens = PhabricatorConduitToken::find()
            ->setViewer($viewer)
            ->withObjectPHIDs(array($user->getPHID()))
            ->withExpired(false)
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->execute();

        $rows = array();
        foreach ($tokens as $token) {

            $rows[] = array(
                JavelinHtml::phutil_tag(
                    'a',
                    array(
                        'href' => Url::to(['/conduit/token/edit', 'id' => $token->getID()]),
                        'sigil' => 'workflow',
                    ),
                    $token->getPublicTokenName()),
                PhabricatorConduitToken::getTokenTypeName($token->getTokenType()),
                ($token->getExpires()
                    ? OranginsViewUtil::phabricator_datetime($token->getExpires(), $viewer)
                    : \Yii::t("app", 'Never')),
                OranginsViewUtil::phabricator_datetime($token->created_at, $viewer),
                JavelinHtml::phutil_implode_html("\n", [
                    (new PHUIButtonView())
                        ->setTag("a")
                        ->setText(\Yii::t("app", 'IP白名单'))
                        ->setColor(PhabricatorEnv::getEnvConfig('ui.widget-color'))
                        ->setWorkflow(true)
                        ->setSize("btn-xs")
                        ->setHref(Url::to(['/conduit/token/ip', 'id' => $token->getID()])),
                    (new PHUIButtonView())
                        ->setTag("a")
                        ->setText(\Yii::t("app", 'Terminate'))
                        ->setColor(PHUITagView::COLOR_DANGER_800)
                        ->setWorkflow(true)
                        ->setSize("btn-xs")
                        ->setHref(Url::to(['/conduit/token/terminate', 'id' => $token->getID()]))
                ])
            );
        }

        $table = new AphrontTableView($rows);
        $table->setNoDataString(\Yii::t("app", "You don't have any active API tokens."));
        $table->setHeaders(
            array(
                \Yii::t("app", 'Token'),
                \Yii::t("app", 'Type'),
                \Yii::t("app", 'Expires'),
                \Yii::t("app", 'Created'),
                \Yii::t("app", 'Actions'),
            ));
        $table->setColumnClasses(
            array(
                'wide pri',
                '',
                'right',
                'right',
                'action text-center',
            ));

        $generate_button = (new PHUIButtonView())
            ->setText(\Yii::t("app", 'Generate Token'))
            ->setHref(Url::to(['/conduit/token/edit', 'objectPHID' => $user->getPHID()]))
            ->setTag('a')
            ->setWorkflow(true)
            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
            ->setIcon('fa-plus');

        $terminate_button = (new PHUIButtonView())
            ->setText(\Yii::t("app", 'Terminate Tokens'))
            ->setHref(Url::to(['/conduit/token/terminate', 'objectPHID' => $user->getPHID()]))
            ->setTag('a')
            ->setWorkflow(true)
            ->setIcon('fa-exclamation-triangle')
            ->setColor(PHUIButtonView::COLOR_DANGER_800);

        $header = (new PHUIHeaderView())
            ->setHeader(\Yii::t("app", 'Active API Tokens'))
            ->addActionLink($generate_button)
            ->addActionLink($terminate_button);

        $panel = (new PHUIObjectBoxView())
            ->setHeader($header)
            ->setBackground(PHUIObjectBoxView::WHITE_CONFIG)
            ->appendChild($table);

        return $panel;
    }

}
