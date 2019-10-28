<?php

namespace orangins\modules\herald\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITagView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\config\HeraldRuleTypeConfig;
use orangins\modules\herald\models\HeraldRule;
use orangins\modules\herald\models\HeraldRuleTransaction;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use PhutilInvalidStateException;
use PhutilMethodNotImplementedException;
use ReflectionException;
use Throwable;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;

/**
 * Class HeraldRuleViewController
 * @package orangins\modules\herald\actions
 * @author 陈妙威
 */
final class HeraldRuleViewController extends HeraldController
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
     * @return mixed|PhabricatorStandardPageView|Aphront404Response
     * @throws PhutilInvalidStateException
     * @throws PhutilMethodNotImplementedException
     * @throws ReflectionException
     * @throws Throwable
     * @throws InvalidConfigException
     * @throws UnknownPropertyException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');

        $rule = HeraldRule::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->needConditionsAndActions(true)
            ->needValidateAuthors(true)
            ->executeOne();
        if (!$rule) {
            return new Aphront404Response();
        }

        $header = (new PHUIPageHeaderView())
            ->setUser($viewer)
            ->setHeader($rule->getName())
            ->setPolicyObject($rule)
            ->setHeaderIcon('fa-bullhorn');

        if ($rule->getIsDisabled()) {
            $header->setStatus('fa-ban', PHUITagView::COLOR_DANGER, pht('Disabled'));
        } else if (!$rule->hasValidAuthor()) {
            $header->setStatus('fa-user', PHUITagView::COLOR_DANGER, pht('Author Not Active'));
        } else {
            $header->setStatus('fa-check', PHUITagView::COLOR_PRIMARY, pht('Active'));
        }

        $curtain = $this->buildCurtain($rule);
        $details = $this->buildPropertySectionView($rule);
        $description = $this->buildDescriptionView($rule);

        $id = $rule->getID();

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb("H{$id}");
        $crumbs->setBorder(true);

        $timeline = $this->buildTransactionTimeline(
            $rule, HeraldRuleTransaction::find());
        $timeline->setShouldTerminate(true);

        $title = $rule->getName();

        $view = (new PHUITwoColumnView())
            ->setCurtain($curtain)
            ->setMainColumn($timeline)
            ->addPropertySection(pht('Details'), $details)
            ->addPropertySection(pht('Description'), $description);

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

    /**
     * @param HeraldRule $rule
     * @return mixed
     * @throws InvalidConfigException
     * @throws PhutilInvalidStateException
     * @throws PhutilMethodNotImplementedException
     * @throws ReflectionException
     * @author 陈妙威
     */
    private function buildCurtain(HeraldRule $rule)
    {
        $viewer = $this->getViewer();

        $id = $rule->getID();

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $rule,
            PhabricatorPolicyCapability::CAN_EDIT);

        $curtain = $this->newCurtainView($rule);

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(pht('Edit Rule'))
                ->setHref($this->getApplicationURI("index/edit", ['id' => $id]))
                ->setIcon('fa-pencil')
                ->setDisabled(!$can_edit)
                ->setWorkflow(!$can_edit));

        if ($rule->getIsDisabled()) {
            $disable_uri = $this->getApplicationURI("index/disable", ['id' => $id, 'action' => 'enable']);
            $disable_icon = 'fa-check';
            $disable_name = pht('Enable Rule');
        } else {
            $disable_uri = $this->getApplicationURI("index/disable", ['id' => $id, 'action' => 'disable']);
            $disable_icon = 'fa-ban';
            $disable_name = pht('Disable Rule');
        }

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setHref($disable_uri)
                ->setIcon($disable_icon)
                ->setName($disable_name)
                ->setDisabled(!$can_edit)
                ->setWorkflow(true));

        return $curtain;
    }

    /**
     * @param HeraldRule $rule
     * @return mixed
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws UnknownPropertyException
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildPropertySectionView(
        HeraldRule $rule)
    {

        $viewer = $this->getRequest()->getViewer();
        $view = (new PHUIPropertyListView())
            ->setUser($viewer);

        $view->addProperty(
            pht('Rule Type'),
            idx(HeraldRuleTypeConfig::getRuleTypeMap(), $rule->getRuleType()));

        if ($rule->isPersonalRule()) {
            $view->addProperty(
                pht('Author'),
                $viewer->renderHandle($rule->getAuthorPHID()));
        }

        $adapter = HeraldAdapter::getAdapterForContentType($rule->getContentType());
        if ($adapter) {
            $view->addProperty(
                pht('Applies To'),
                idx(
                    HeraldAdapter::getEnabledAdapterMap($viewer),
                    $rule->getContentType()));

            if ($rule->isObjectRule()) {
                $view->addProperty(
                    pht('Trigger Object'),
                    $viewer->renderHandle($rule->getTriggerObjectPHID()));
            }
        }

        return $view;
    }

    /**
     * @param HeraldRule $rule
     * @return PHUIPropertyListView|null |null
     * @throws PhutilInvalidStateException
     * @throws UnknownPropertyException
     * @author 陈妙威
     */
    private function buildDescriptionView(HeraldRule $rule)
    {
        $viewer = $this->getRequest()->getViewer();
        $view = (new PHUIPropertyListView())
            ->setUser($viewer);

        $adapter = HeraldAdapter::getAdapterForContentType($rule->getContentType());
        if ($adapter) {
            $handles = $viewer->loadHandles(HeraldAdapter::getHandlePHIDs($rule));
            $rule_text = $adapter->renderRuleAsText($rule, $handles, $viewer);
            $view->addTextContent($rule_text);
            return $view;
        }
        return null;
    }

}
