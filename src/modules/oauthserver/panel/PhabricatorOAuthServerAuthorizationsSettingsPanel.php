<?php

namespace orangins\modules\oauthserver\panel;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\PhabricatorApplication;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\modules\oauthserver\application\PhabricatorOAuthServerApplication;
use orangins\modules\oauthserver\models\PhabricatorOAuthClientAuthorization;
use orangins\modules\settings\panel\PhabricatorSettingsPanel;
use orangins\modules\settings\panelgroup\PhabricatorSettingsLogsPanelGroup;

/**
 * Class PhabricatorOAuthServerAuthorizationsSettingsPanel
 * @package orangins\modules\oauthserver\panel
 * @author 陈妙威
 */
final class PhabricatorOAuthServerAuthorizationsSettingsPanel
    extends PhabricatorSettingsPanel
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelKey()
    {
        return 'oauthorizations';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelName()
    {
        return pht('OAuth Authorizations');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelMenuIcon()
    {
        return 'fa-exchange';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPanelGroupKey()
    {
        return PhabricatorSettingsLogsPanelGroup::PANELGROUPKEY;
    }

    /**
     * @return bool
     * @throws \Exception
     * @author 陈妙威
     */
    public function isEnabled()
    {
        return PhabricatorApplication::isClassInstalled(
            PhabricatorOAuthServerApplication::className());
    }

    /**
     * @param AphrontRequest $request
     * @return Aphront404Response|AphrontDialogResponse|AphrontRedirectResponse|PHUIObjectBoxView|\orangins\modules\settings\panel\wild
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function processRequest(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        // TODO: It would be nice to simply disable this panel, but we can't do
        // viewer-based checks for enabled panels right now.

        $app_class = PhabricatorOAuthServerApplication::className();
        $installed = PhabricatorApplication::isClassInstalledForViewer(
            $app_class,
            $viewer);
        if (!$installed) {
            $dialog = (new  AphrontDialogView())
                ->setUser($viewer)
                ->setTitle(pht('OAuth Not Available'))
                ->appendParagraph(
                    pht('You do not have access to OAuth authorizations.'))
                ->addCancelButton('/settings/');
            return (new  AphrontDialogResponse())->setDialog($dialog);
        }

        $authorizations = PhabricatorOAuthClientAuthorization::find()
            ->setViewer($viewer)
            ->withUserPHIDs(array($viewer->getPHID()))
            ->execute();
        $authorizations = mpull($authorizations, null, 'getID');

        $panel_uri = $this->getPanelURI();

        $revoke = $request->getInt('revoke');
        if ($revoke) {
            if (empty($authorizations[$revoke])) {
                return new Aphront404Response();
            }

            if ($request->isFormPost()) {
                $authorizations[$revoke]->delete();
                return (new  AphrontRedirectResponse())->setURI($panel_uri);
            }

            $dialog = (new  AphrontDialogView())
                ->setUser($viewer)
                ->setTitle(pht('Revoke Authorization?'))
                ->appendParagraph(
                    pht(
                        'This application will no longer be able to access Phabricator ' .
                        'on your behalf.'))
                ->addSubmitButton(pht('Revoke Authorization'))
                ->addCancelButton($panel_uri);

            return (new  AphrontDialogResponse())->setDialog($dialog);
        }

        $highlight = $request->getInt('id');

        $rows = array();
        $rowc = array();
        foreach ($authorizations as $authorization) {
            if ($highlight == $authorization->getID()) {
                $rowc[] = 'highlighted';
            } else {
                $rowc[] = null;
            }

            $button = JavelinHtml::phutil_tag(
                'a',
                array(
                    'href' => $this->getPanelURI('?revoke=' . $authorization->getID()),
                    'class' => 'small button button-grey',
                    'sigil' => 'workflow',
                ),
                pht('Revoke'));

            $rows[] = array(
                phutil_tag(
                    'a',
                    array(
                        'href' => $authorization->getClient()->getViewURI(),
                    ),
                    $authorization->getClient()->getName()),
                $authorization->getScopeString(),
                OranginsViewUtil::phabricator_datetime($authorization->created_at, $viewer),
                OranginsViewUtil::phabricator_datetime($authorization->getDateModified(), $viewer),
                $button,
            );
        }

        $table = new AphrontTableView($rows);
        $table->setNoDataString(
            pht("You haven't authorized any OAuth applications."));

        $table->setRowClasses($rowc);
        $table->setHeaders(
            array(
                pht('Application'),
                pht('Scope'),
                pht('Created'),
                pht('Updated'),
                null,
            ));

        $table->setColumnClasses(
            array(
                'pri',
                'wide',
                'right',
                'right',
                'action',
            ));

        $header = (new  PHUIHeaderView())
            ->setHeader(pht('OAuth Application Authorizations'));

        $panel = (new  PHUIObjectBoxView())
            ->setHeader($header)
            ->setBackground(PHUIObjectBoxView::WHITE_CONFIG)
            ->appendChild($table);

        return $panel;
    }

}
