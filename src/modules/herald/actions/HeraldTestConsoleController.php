<?php

namespace orangins\modules\herald\actions;

use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormRadioButtonControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\herald\adapter\HeraldAdapter;
use orangins\modules\herald\engine\HeraldEngine;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;

/**
 * Class HeraldTestConsoleController
 * @package orangins\modules\herald\actions
 * @author 陈妙威
 */
final class HeraldTestConsoleController extends HeraldController
{

    /**
     * @var
     */
    private $testObject;
    /**
     * @var
     */
    private $testAdapter;

    /**
     * @param $test_object
     * @return $this
     * @author 陈妙威
     */
    public function setTestObject($test_object)
    {
        $this->testObject = $test_object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTestObject()
    {
        return $this->testObject;
    }

    /**
     * @param HeraldAdapter $test_adapter
     * @return $this
     * @author 陈妙威
     */
    public function setTestAdapter(HeraldAdapter $test_adapter)
    {
        $this->testAdapter = $test_adapter;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTestAdapter()
    {
        return $this->testAdapter;
    }

    /**
     * @return mixed|\orangins\lib\view\page\PhabricatorStandardPageView|null
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $response = $this->loadTestObject($request);
        if ($response) {
            return $response;
        }

        $response = $this->loadAdapter($request);
        if ($response) {
            return $response;
        }

        $object = $this->getTestObject();
        $adapter = $this->getTestAdapter();
        $source = $this->newContentSource($object);

        $adapter
            ->setContentSource($source)
            ->setIsNewObject(false)
            ->setActingAsPHID($viewer->getPHID())
            ->setViewer($viewer);

        $rules = HeraldRule::find()
            ->setViewer($viewer)
            ->withContentTypes(array($adapter->getAdapterContentType()))
            ->withDisabled(false)
            ->needConditionsAndActions(true)
            ->needAppliedToPHIDs(array($object->getPHID()))
            ->needValidateAuthors(true)
            ->execute();

        $engine = (new HeraldEngine())
            ->setDryRun(true);

        $effects = $engine->applyRules($rules, $adapter);
        $engine->applyEffects($effects, $adapter, $rules);

        $xscript = $engine->getTranscript();

        return (new AphrontRedirectResponse())
            ->setURI('/herald/transcript/' . $xscript->getID() . '/');
    }

    /**
     * @param AphrontRequest $request
     * @return \orangins\lib\view\page\PhabricatorStandardPageView|null
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    private function loadTestObject(AphrontRequest $request)
    {
        $viewer = $this->getViewer();

        $e_name = true;
        $v_name = null;
        $errors = array();

        if ($request->isFormPost()) {
            $v_name = trim($request->getStr('object_name'));
            if (!$v_name) {
                $e_name = pht('Required');
                $errors[] = pht('An object name is required.');
            }

            if (!$errors) {
                $object = (new PhabricatorObjectQuery())
                    ->setViewer($viewer)
                    ->withNames(array($v_name))
                    ->executeOne();

                if (!$object) {
                    $e_name = pht('Invalid');
                    $errors[] = pht('No object exists with that name.');
                }
            }

            if (!$errors) {
                $this->setTestObject($object);
                return null;
            }
        }

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->appendRemarkupInstructions(
                pht(
                    'Enter an object to test rules for, like a Diffusion commit (e.g., ' .
                    '`rX123`) or a Differential revision (e.g., `D123`). You will be ' .
                    'shown the results of a dry run on the object.'))
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(pht('Object Name'))
                    ->setName('object_name')
                    ->setError($e_name)
                    ->setValue($v_name))
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->setValue(pht('Continue')));

        return $this->buildTestConsoleResponse($form, $errors);
    }

    /**
     * @param AphrontRequest $request
     * @return \orangins\lib\view\page\PhabricatorStandardPageView|null
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function loadAdapter(AphrontRequest $request)
    {
        $viewer = $this->getViewer();
        $object = $this->getTestObject();

        $adapter_key = $request->getStr('adapter');

        $adapters = HeraldAdapter::getAllAdapters();

        $can_select = array();
        $display_adapters = array();
        foreach ($adapters as $key => $adapter) {
            if (!$adapter->isTestAdapterForObject($object)) {
                continue;
            }

            if (!$adapter->isAvailableToUser($viewer)) {
                continue;
            }

            $display_adapters[$key] = $adapter;

            if ($adapter->canCreateTestAdapterForObject($object)) {
                $can_select[$key] = $adapter;
            }
        }

        if ($request->isFormPost() && $adapter_key) {
            if (isset($can_select[$adapter_key])) {
                $adapter = $can_select[$adapter_key]->newTestAdapter(
                    $viewer,
                    $object);
                $this->setTestAdapter($adapter);
                return null;
            }
        }

        $form = (new AphrontFormView())
            ->addHiddenInput('object_name', $request->getStr('object_name'))
            ->setViewer($viewer);

        $cancel_uri = $this->getApplicationURI();

        if (!$display_adapters) {
            $form
                ->appendRemarkupInstructions(
                    pht('//There are no available Herald events for this object.//'))
                ->appendControl(
                    (new AphrontFormSubmitControl())
                        ->addCancelButton($cancel_uri));
        } else {
            $adapter_control = (new AphrontFormRadioButtonControl())
                ->setLabel(pht('Event'))
                ->setName('adapter')
                ->setValue(head_key($can_select));

            foreach ($display_adapters as $adapter_key => $adapter) {
                $is_disabled = empty($can_select[$adapter_key]);

                $adapter_control->addButton(
                    $adapter_key,
                    $adapter->getAdapterContentName(),
                    $adapter->getAdapterTestDescription(),
                    null,
                    $is_disabled);
            }

            $form
                ->appendControl($adapter_control)
                ->appendControl(
                    (new AphrontFormSubmitControl())
                        ->setValue(pht('Run Test')));
        }

        return $this->buildTestConsoleResponse($form, array());
    }

    /**
     * @param $form
     * @param array $errors
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    private function buildTestConsoleResponse($form, array $errors)
    {
        $box = (new PHUIObjectBoxView())
            ->setFormErrors($errors)
            ->setForm($form);

        $crumbs = id($this->buildApplicationCrumbs())
            ->addTextCrumb(pht('Test Console'))
            ->setBorder(true);

        $title = pht('Test Console');

        $header = (new PHUIHeaderView())
            ->setHeader($title)
            ->setHeaderIcon('fa-desktop');

        $view = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setFooter($box);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    private function newContentSource($object)
    {
        $viewer = $this->getViewer();

        // Try using the content source associated with the most recent transaction
        // on the object.

        $query = PhabricatorApplicationTransactionQuery::newQueryForObject($object);

        $xaction = $query
            ->setViewer($viewer)
            ->withObjectPHIDs(array($object->getPHID()))
            ->setLimit(1)
            ->setOrder('newest')
            ->executeOne();
        if ($xaction) {
            return $xaction->getContentSource();
        }

        // If we couldn't find a transaction (which should be rare), fall back to
        // building a new content source from the test console request itself.

        $request = $this->getRequest();
        return PhabricatorContentSource::newFromRequest($request);
    }

}
