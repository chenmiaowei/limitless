<?php

namespace orangins\modules\oauthserver\actions\client;

use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\modules\oauthserver\models\PhabricatorOAuthServerClient;
use orangins\modules\oauthserver\models\PhabricatorOAuthServerTransaction;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;

/**
 * Class PhabricatorOAuthClientViewController
 * @package orangins\modules\oauthserver\actions\client
 * @author 陈妙威
 */
final class PhabricatorOAuthClientViewController
    extends PhabricatorOAuthClientController
{

    /**
     * @return Aphront404Response|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \Throwable
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $client = PhabricatorOAuthServerClient::find()
            ->setViewer($viewer)
            ->withIDs(array($request->getURIData('id')))
            ->executeOne();
        if (!$client) {
            return new Aphront404Response();
        }

        $header = $this->buildHeaderView($client);
        $actions = $this->buildActionView($client);
        $properties = $this->buildPropertyListView($client);
        $properties->setActionList($actions);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($client->getName());

        $timeline = $this->buildTransactionTimeline(
            $client,
            PhabricatorOAuthServerTransaction::find());
        $timeline->setShouldTerminate(true);

        $box = (new  PHUIObjectBoxView())
            ->setHeader($header)
            ->addPropertyList($properties);

        $title = pht('OAuth Application: %s', $client->getName());

        return $this->newPage()
            ->setCrumbs($crumbs)
            ->setTitle($title)
            ->appendChild(
                array(
                    $box,
                    $timeline,
                ));
    }

    /**
     * @param PhabricatorOAuthServerClient $client
     * @return PHUIHeaderView
     * @author 陈妙威
     */
    private function buildHeaderView(PhabricatorOAuthServerClient $client)
    {
        $viewer = $this->getViewer();

        $header = (new  PHUIHeaderView())
            ->setUser($viewer)
            ->setHeader(pht('OAuth Application: %s', $client->getName()))
            ->setPolicyObject($client);

        if ($client->getIsDisabled()) {
            $header->setStatus('fa-ban', 'indigo', pht('Disabled'));
        } else {
            $header->setStatus('fa-check', 'green', pht('Enabled'));
        }

        return $header;
    }

    /**
     * @param PhabricatorOAuthServerClient $client
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function buildActionView(PhabricatorOAuthServerClient $client)
    {
        $viewer = $this->getViewer();

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $client,
            PhabricatorPolicyCapability::CAN_EDIT);

        $id = $client->getID();

        $view = (new  PhabricatorActionListView())
            ->setUser($viewer);

        $view->addAction(
            (new  PhabricatorActionView())
                ->setName(pht('Edit Application'))
                ->setIcon('fa-pencil')
                ->setWorkflow(!$can_edit)
                ->setDisabled(!$can_edit)
                ->setHref($client->getEditURI()));

        $view->addAction(
            (new  PhabricatorActionView())
                ->setName(pht('Show Application Secret'))
                ->setIcon('fa-eye')
                ->setHref($this->getApplicationURI("client/secret", [
                    "id" => $id
                ]))
                ->setDisabled(!$can_edit)
                ->setWorkflow(true));

        $is_disabled = $client->getIsDisabled();
        if ($is_disabled) {
            $disable_text = pht('Enable Application');
            $disable_icon = 'fa-check';
        } else {
            $disable_text = pht('Disable Application');
            $disable_icon = 'fa-ban';
        }

        $disable_uri = $this->getApplicationURI("client/disable", [
            "id" => $id
        ]);

        $view->addAction(
            (new  PhabricatorActionView())
                ->setName($disable_text)
                ->setIcon($disable_icon)
                ->setWorkflow(true)
                ->setDisabled(!$can_edit)
                ->setHref($disable_uri));

        $view->addAction(
            (new  PhabricatorActionView())
                ->setName(pht('Generate Test Token'))
                ->setIcon('fa-plus')
                ->setWorkflow(true)
                ->setHref($this->getApplicationURI("client/test", [
                    "id" => $id
                ])));

        return $view;
    }

    /**
     * @param PhabricatorOAuthServerClient $client
     * @return PHUIPropertyListView
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function buildPropertyListView(PhabricatorOAuthServerClient $client)
    {
        $viewer = $this->getRequest()->getViewer();

        $view = (new  PHUIPropertyListView())
            ->setUser($viewer);

        $view->addProperty(
            pht('Client PHID'),
            $client->getPHID());

        $view->addProperty(
            pht('Redirect URI'),
            $client->getRedirectURI());

        $view->addProperty(
            pht('Created'),
            OranginsViewUtil::phabricator_datetime($client->created_at, $viewer));

        return $view;
    }
}
