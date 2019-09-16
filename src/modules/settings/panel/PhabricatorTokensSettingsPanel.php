<?php

namespace orangins\modules\settings\panel;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\modules\auth\models\PhabricatorAuthTemporaryToken;
use orangins\modules\auth\query\PhabricatorAuthTemporaryTokenQuery;
use orangins\modules\settings\panelgroup\PhabricatorSettingsLogsPanelGroup;

/**
 * Class PhabricatorTokensSettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorTokensSettingsPanel extends PhabricatorSettingsPanel
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelKey()
    {
        return 'tokens';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return \Yii::t("app",'Temporary Tokens');
    }

    /**
     * @return const|string
     * @author 陈妙威
     */
    public function getPanelGroupKey()
    {
        return PhabricatorSettingsLogsPanelGroup::PANELGROUPKEY;
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function processRequest(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        $tokens = PhabricatorAuthTemporaryToken::find()
            ->setViewer($viewer)
            ->withTokenResources(array($viewer->getPHID()))
            ->execute();

        $rows = array();
        foreach ($tokens as $token) {

            if ($token->isRevocable()) {
                
                $button = JavelinHtml::phutil_tag(
                    'a',
                    array(
                        'href' => '/auth/token/revoke/' . $token->getID() . '/',
                        'class' => 'small button button-grey',
                        'sigil' => 'workflow',
                    ),
                    \Yii::t("app",'Revoke'));
            } else {
                $button = JavelinHtml::phutil_tag(
                    'a',
                    array(
                        'class' => 'small button button-grey disabled',
                    ),
                    \Yii::t("app",'Revoke'));
            }

            if ($token->getTokenExpires() >= time()) {
                $expiry = OranginsViewUtil::phabricator_datetime($token->getTokenExpires(), $viewer);
            } else {
                $expiry = \Yii::t("app",'Expired');
            }

            $rows[] = array(
                $token->getTokenReadableTypeName(),
                $expiry,
                $button,
            );
        }

        $table = new AphrontTableView($rows);
        $table->setNoDataString(\Yii::t("app","You don't have any active tokens."));
        $table->setHeaders(
            array(
                \Yii::t("app",'Type'),
                \Yii::t("app",'Expires'),
                \Yii::t("app",''),
            ));
        $table->setColumnClasses(
            array(
                'wide',
                'right',
                'action',
            ));

        $button = (new PHUIButtonView())
            ->setTag('a')
            ->setIcon('fa-warning')
            ->setText(\Yii::t("app",'Revoke All'))
            ->setHref('/auth/token/revoke/all/')
            ->setWorkflow(true)
            ->setColor(PHUIButtonView::COLOR_DANGER);

        return $this->newBox(\Yii::t("app",'Temporary Tokens'), $table, array($button));
    }

}
