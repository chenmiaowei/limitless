<?php

namespace orangins\modules\settings\panel;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\infrastructure\util\PhabricatorHash;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\modules\auth\constants\PhabricatorCookies;
use orangins\modules\auth\models\PhabricatorAuthSession;
use orangins\modules\people\models\PhabricatorExternalAccount;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\settings\panelgroup\PhabricatorSettingsLogsPanelGroup;

/**
 * Class PhabricatorSessionsSettingsPanel
 * @package orangins\modules\settings\panel
 * @author 陈妙威
 */
final class PhabricatorSessionsSettingsPanel extends PhabricatorSettingsPanel
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelKey()
    {
        return 'sessions';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return \Yii::t("app",'Sessions');
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
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception*@throws \Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function processRequest(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        $accounts = PhabricatorExternalAccount::find()
            ->setViewer($viewer)
            ->withUserPHIDs(array($viewer->getPHID()))
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->execute();

        $identity_phids = mpull($accounts, 'getPHID');
        $identity_phids[] = $viewer->getPHID();

        /** @var PhabricatorAuthSession[] $sessions */
        $sessions = PhabricatorAuthSession::find()
            ->setViewer($viewer)
            ->withIdentityPHIDs($identity_phids)
            ->execute();

        $handles = (new PhabricatorHandleQuery())
            ->setViewer($viewer)
            ->withPHIDs($identity_phids)
            ->execute();

        $current_key = PhabricatorHash::weakDigest(
            $request->getCookie(PhabricatorCookies::COOKIE_SESSION));

        $rows = array();
        $rowc = array();
        foreach ($sessions as $session) {
            $is_current = phutil_hashes_are_identical(
                $session->getSessionKey(),
                $current_key);
            if ($is_current) {
                $rowc[] = 'highlighted';
                $button = JavelinHtml::phutil_tag(
                    'a',
                    array(
                        'class' => 'small button button-grey disabled',
                    ),
                    \Yii::t("app",'Current'));
            } else {
                $rowc[] = null;
                $button = JavelinHtml::phutil_tag(
                    'a',
                    array(
                        'href' => '/auth/session/terminate/' . $session->getID() . '/',
                        'class' => 'small button button-grey',
                        'sigil' => 'workflow',
                    ),
                    \Yii::t("app",'Terminate'));
            }

            $hisec = ($session->getHighSecurityUntil() - time());

            $rows[] = array(
                $handles[$session->getUserPHID()]->renderLink(),
                substr($session->getSessionKey(), 0, 6),
                $session->getType(),
                ($hisec > 0)
                    ? phutil_format_relative_time($hisec)
                    : null,
                OranginsViewUtil::phabricator_datetime($session->getSessionStart(), $viewer),
                OranginsViewUtil::phabricator_date($session->getSessionExpires(), $viewer),
                $button,
            );
        }

        $table = new AphrontTableView($rows);
        $table->setNoDataString(\Yii::t("app","You don't have any active sessions."));
        $table->setRowClasses($rowc);
        $table->setHeaders(
            array(
                \Yii::t("app",'Identity'),
                \Yii::t("app",'Session'),
                \Yii::t("app",'Type'),
                \Yii::t("app",'HiSec'),
                \Yii::t("app",'Created'),
                \Yii::t("app",'Expires'),
                \Yii::t("app",''),
            ));
        $table->setColumnClasses(
            array(
                'wide',
                'n',
                '',
                'right',
                'right',
                'right',
                'action',
            ));

        $buttons = array();
        $buttons[] = (new PHUIButtonView())
            ->setTag('a')
            ->setIcon('fa-warning')
            ->setText(\Yii::t("app",'Terminate All Sessions'))
            ->setHref('/auth/session/terminate/all/')
            ->setWorkflow(true)
            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"));

        $hisec = ($viewer->getSession()->getHighSecurityUntil() - time());
        if ($hisec > 0) {
            $buttons[] = (new PHUIButtonView())
                ->setTag('a')
                ->setIcon('fa-lock')
                ->setText(\Yii::t("app",'Leave High Security'))
                ->setHref('/auth/session/downgrade/')
                ->setWorkflow(true)
                ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"));
        }

        return $this->newBox(\Yii::t("app",'Active Login Sessions'), $table, $buttons);
    }

}
