<?php

namespace orangins\modules\herald\actions;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\markup\view\PHUIRemarkupView;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\herald\models\HeraldRule;
use orangins\modules\herald\models\HeraldWebhook;
use orangins\modules\herald\query\HeraldWebhookRequestQuery;
use orangins\modules\herald\query\HeraldWebhookTransactionQuery;
use orangins\modules\herald\view\HeraldRuleListView;
use orangins\modules\herald\view\HeraldWebhookRequestListView;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use PhutilURI;

/**
 * Class HeraldWebhookViewController
 * @package orangins\modules\herald\actions
 * @author 陈妙威
 */
final class HeraldWebhookViewController
    extends HeraldWebhookController
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return Aphront404Response|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $hook = HeraldWebhook::find()
            ->setViewer($viewer)
            ->withIDs(array($request->getURIData('id')))
            ->executeOne();
        if (!$hook) {
            return new Aphront404Response();
        }

        $header = $this->buildHeaderView($hook);

        $warnings = null;
        if ($hook->isInErrorBackoff($viewer)) {
            $message = pht(
                'Many requests to this webhook have failed recently (at least %s ' .
                'errors in the last %s seconds). New requests are temporarily paused.',
                $hook->getErrorBackoffThreshold(),
                $hook->getErrorBackoffWindow());

            $warnings = (new PHUIInfoView())
                ->setSeverity(PHUIInfoView::SEVERITY_WARNING)
                ->setErrors(
                    array(
                        $message,
                    ));
        }

        $curtain = $this->buildCurtain($hook);
        $properties_view = $this->buildPropertiesView($hook);

        $timeline = $this->buildTransactionTimeline(
            $hook,
            new HeraldWebhookTransactionQuery());
        $timeline->setShouldTerminate(true);

        $requests = (new HeraldWebhookRequestQuery())
            ->setViewer($viewer)
            ->withWebhookPHIDs(array($hook->getPHID()))
            ->setLimit(20)
            ->execute();

        $warnings = array();
        if (PhabricatorEnv::getEnvConfig('phabricator.silent')) {
            $message = pht(
                'Phabricator is currently configured in silent mode, so it will not ' .
                'publish webhooks. To adjust this setting, see ' .
                '@{config:phabricator.silent} in Config.');

            $warnings[] = (new PHUIInfoView())
                ->setTitle(pht('Silent Mode'))
                ->setSeverity(PHUIInfoView::SEVERITY_WARNING)
                ->appendChild(new PHUIRemarkupView($viewer, $message));
        }

        $requests_table = (new HeraldWebhookRequestListView())
            ->setViewer($viewer)
            ->setRequests($requests)
            ->setHighlightID($request->getURIData('requestID'));

        $requests_view = (new PHUIObjectBoxView())
            ->setHeaderText(pht('Recent Requests'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setTable($requests_table);

        $rules_view = $this->newRulesView($hook);

        $hook_view = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setMainColumn(
                array(
                    $warnings,
                    $properties_view,
                    $rules_view,
                    $requests_view,
                    $timeline,
                ))
            ->setCurtain($curtain);

        $crumbs = $this->buildApplicationCrumbs()
            ->addTextCrumb(pht('Webhook %d', $hook->getID()))
            ->setBorder(true);

        return $this->newPage()
            ->setTitle(
                array(
                    pht('Webhook %d', $hook->getID()),
                    $hook->getName(),
                ))
            ->setCrumbs($crumbs)
            ->setPageObjectPHIDs(
                array(
                    $hook->getPHID(),
                ))
            ->appendChild($hook_view);
    }

    /**
     * @param HeraldWebhook $hook
     * @return PHUIHeaderView
     * @author 陈妙威
     */
    private function buildHeaderView(HeraldWebhook $hook)
    {
        $viewer = $this->getViewer();

        $title = $hook->getName();

        $status_icon = $hook->getStatusIcon();
        $status_color = $hook->getStatusColor();
        $status_name = $hook->getStatusDisplayName();

        $header = (new PHUIHeaderView())
            ->setHeader($title)
            ->setViewer($viewer)
            ->setPolicyObject($hook)
            ->setStatus($status_icon, $status_color, $status_name)
            ->setHeaderIcon('fa-cloud-upload');

        return $header;
    }


    /**
     * @param HeraldWebhook $hook
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function buildCurtain(HeraldWebhook $hook)
    {
        $viewer = $this->getViewer();
        $curtain = $this->newCurtainView($hook);

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $hook,
            PhabricatorPolicyCapability::CAN_EDIT);

        $id = $hook->getID();
        $edit_uri = $this->getApplicationURI("webhook/edit/{$id}/");
        $test_uri = $this->getApplicationURI("webhook/test/{$id}/");

        $key_view_uri = $this->getApplicationURI("webhook/key/view/{$id}/");
        $key_cycle_uri = $this->getApplicationURI("webhook/key/cycle/{$id}/");

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(pht('Edit Webhook'))
                ->setIcon('fa-pencil')
                ->setDisabled(!$can_edit)
                ->setWorkflow(!$can_edit)
                ->setHref($edit_uri));

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(pht('New Test Request'))
                ->setIcon('fa-cloud-upload')
                ->setDisabled(!$can_edit)
                ->setWorkflow(true)
                ->setHref($test_uri));

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(pht('View HMAC Key'))
                ->setIcon('fa-key')
                ->setDisabled(!$can_edit)
                ->setWorkflow(true)
                ->setHref($key_view_uri));

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(pht('Regenerate HMAC Key'))
                ->setIcon('fa-refresh')
                ->setDisabled(!$can_edit)
                ->setWorkflow(true)
                ->setHref($key_cycle_uri));

        return $curtain;
    }


    /**
     * @param HeraldWebhook $hook
     * @return PHUIObjectBoxView
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildPropertiesView(HeraldWebhook $hook)
    {
        $viewer = $this->getViewer();

        $properties = (new PHUIPropertyListView())
            ->setViewer($viewer);

        $properties->addProperty(
            pht('URI'),
            $hook->getWebhookURI());

        $properties->addProperty(
            pht('Status'),
            $hook->getStatusDisplayName());

        return (new PHUIObjectBoxView())
            ->setHeaderText(pht('Details'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->appendChild($properties);
    }

    /**
     * @param HeraldWebhook $hook
     * @return PHUIObjectBoxView
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    private function newRulesView(HeraldWebhook $hook)
    {
        $viewer = $this->getViewer();

        $rules = HeraldRule::find()
            ->setViewer($viewer)
            ->withDisabled(false)
            ->withAffectedObjectPHIDs(array($hook->getPHID()))
            ->needValidateAuthors(true)
            ->setLimit(10)
            ->execute();

        $list = (new HeraldRuleListView())
            ->setViewer($viewer)
            ->setRules($rules)
            ->newObjectList();

        $list->setNoDataString(pht('No active Herald rules call this webhook.'));

        $more_href = new PhutilURI(
            '/herald/',
            array('affectedPHID' => $hook->getPHID()));

        $more_link = (new PHUIButtonView())
            ->setTag('a')
            ->setIcon('fa-list-ul')
            ->setText(pht('View All Rules'))
            ->setHref($more_href);

        $header = (new PHUIHeaderView())
            ->setHeader(pht('Called By Herald Rules'))
            ->addActionLink($more_link);

        return (new PHUIObjectBoxView())
            ->setHeader($header)
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->appendChild($list);
    }

}
