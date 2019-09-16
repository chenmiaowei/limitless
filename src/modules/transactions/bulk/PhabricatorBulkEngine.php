<?php

namespace orangins\modules\transactions\bulk;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\daemon\workers\editor\PhabricatorWorkerBulkJobEditor;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJob;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerBulkJobTransaction;
use orangins\lib\OranginsObject;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\PHUIFormInsetView;
use orangins\lib\view\form\PHUIFormLayoutView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\people\models\PhabricatorUser;
use Exception;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\transactions\assets\JavelinBulkEditorBehaviorAsset;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorBulkEngine
 * @package orangins\modules\transactions\bulk
 * @author 陈妙威
 */
abstract class PhabricatorBulkEngine extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var PhabricatorAction
     */
    private $action;
    /**
     * @var array
     */
    private $context = array();
    /**
     * @var
     */
    private $objectList;
    /**
     * @var PhabricatorSavedQuery
     */
    private $savedQuery;
    /**
     * @var
     */
    private $editableList;
    /**
     * @var
     */
    private $targetList;

    /**
     * @var
     */
    private $rootFormID;

    /**
     * @return PhabricatorApplicationSearchEngine
     * @author 陈妙威
     */
    abstract public function newSearchEngine();

    /**
     * @return PhabricatorEditEngine
     * @author 陈妙威
     */
    abstract public function newEditEngine();

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCancelURI()
    {
        $saved_query = $this->savedQuery;
        if ($saved_query) {
            $path = '/query/' . $saved_query->getQueryKey() . '/';
        } else {
            $path = '/';
        }

        return $this->getQueryURI($path);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getDoneURI()
    {
        if ($this->objectList !== null) {
            $ids = mpull($this->objectList, 'getID');
            $path = '/?ids=' . implode(',', $ids);
        } else {
            $path = '/';
        }

        return $this->getQueryURI($path);
    }

    /**
     * @param string $path
     * @return string
     * @author 陈妙威
     */
    protected function getQueryURI($path = '/')
    {
        $viewer = $this->getViewer();

        $newSearchEngine = $this->newSearchEngine();
        $engine = $newSearchEngine
            ->setViewer($viewer);

        return $engine->getQueryBaseURI() . ltrim($path, '/');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getBulkURI()
    {
        $saved_query = $this->savedQuery;
        if ($saved_query) {
            $path = '/query/' . $saved_query->getQueryKey() . '/';
        } else {
            $path = '/';
        }

        return $this->getBulkBaseURI($path);
    }

    /**
     * @param $path
     * @return string
     * @author 陈妙威
     */
    protected function getBulkBaseURI($path)
    {
        return $this->getQueryURI('bulk/' . ltrim($path, '/'));
    }

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param PhabricatorAction $controller
     * @return $this
     * @author 陈妙威
     */
    final public function setAction(PhabricatorAction $controller)
    {
        $this->action = $controller;
        return $this;
    }

    /**
     * @return PhabricatorAction
     * @author 陈妙威
     */
    final public function getAction()
    {
        return $this->action;
    }

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     */
    final public function addContextParameter($key)
    {
        $this->context[$key] = true;
        return $this;
    }

    /**
     * @return null
     * @throws Exception
     * @author 陈妙威
     */
    final public function buildResponse()
    {
        $viewer = $this->getViewer();
        $controller = $this->getAction();
        $request = $controller->getRequest();

        $response = $this->loadObjectList();
        if ($response) {
            return $response;
        }

        if ($request->isFormPost() && $request->getBool('bulkEngine')) {
            return $this->buildEditResponse();
        }

        $list_view = $this->newBulkObjectList();

        $header = (new PHUIPageHeaderView())
            ->setHeader(\Yii::t("app", 'Bulk Editor'))
            ->setHeaderIcon('fa-pencil-square-o');

        $list_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Working Set'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setObjectList($list_view);

        $form_view = $this->newBulkActionForm();

        $form_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Actions'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setForm($form_view);

        $complete_form = JavelinHtml::phabricator_form(
            $viewer,
            array(
                'action' => $this->getBulkURI(),
                'method' => 'POST',
                'id' => $this->getRootFormID(),
            ),
            array(
                $this->newContextInputs(),
                $list_box,
                $form_box,
            ));

        $column_view = (new PHUITwoColumnView())
            ->setFooter($complete_form);

        // TODO: This is a bit hacky and inflexible.
        $crumbs = $controller->buildApplicationCrumbsForEditEngine();
        $crumbs->addTextCrumb(\Yii::t("app", 'Query'), $this->getCancelURI());
        $crumbs->addTextCrumb(\Yii::t("app", 'Bulk Editor'));

        return $controller->newPage()
            ->setHeader($header)
            ->setTitle(\Yii::t("app", 'Bulk Edit'))
            ->setCrumbs($crumbs)
            ->appendChild($column_view);
    }

    /**
     * @return null
     * @author 陈妙威
     * @throws Exception
     */
    private function loadObjectList()
    {
        $viewer = $this->getViewer();
        $controller = $this->getAction();
        $request = $controller->getRequest();

        $search_engine = $this->newSearchEngine()
            ->setViewer($viewer);

        $query_key = $request->getURIData('queryKey');
        if (strlen($query_key)) {
            if ($search_engine->isBuiltinQuery($query_key)) {
                $saved = $search_engine->buildSavedQueryFromBuiltin($query_key);
            } else {
                $saved = PhabricatorSavedQuery::find()
                    ->setViewer($viewer)
                    ->withQueryKeys(array($query_key))
                    ->executeOne();
                if (!$saved) {
                    return new Aphront404Response();
                }
            }
        } else {
            // TODO: For now, since we don't deal gracefully with queries which
            // match a huge result set, just bail if we don't have any query
            // parameters instead of querying for a trillion tasks and timing out.
            $request_data = $request->getPassthroughRequestData();
            if (!$request_data) {
                throw new Exception(
                    \Yii::t("app",
                        'Expected a query key or a set of query constraints.'));
            }

            $saved = $search_engine->buildSavedQueryFromRequest($request);
            $search_engine->saveQuery($saved);
        }

        $object_query = $search_engine->buildQueryFromSavedQuery($saved)
            ->setViewer($viewer);
        $object_list = $object_query->execute();
        $object_list = mpull($object_list, null, 'getPHID');

        // If the user has submitted the bulk edit form, select only the objects
        // they checked.
        if ($request->getBool('bulkEngine')) {
            $target_phids = $request->getArr('bulkTargetPHIDs');

            // NOTE: It's possible that the underlying query result set has changed
            // between the time we ran the query initially and now: for example, the
            // query was for "Open Tasks" and some tasks were closed while the user
            // was making action selections.

            // This could result in some objects getting dropped from the working set
            // here: we'll have target PHIDs for them, but they will no longer be
            // part of the object list. For now, just go with this since it doesn't
            // seem like a big problem and may even be desirable.

            $this->targetList = array_select_keys($object_list, $target_phids);
        } else {
            $this->targetList = $object_list;
        }

        $this->objectList = $object_list;
        $this->savedQuery = $saved;

        // Filter just the editable objects. We show all the objects which the
        // query matches whether they're editable or not, but indicate which ones
        // can not be edited to the user.

        $editable_list = (new PhabricatorPolicyFilter())
            ->setViewer($viewer)
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->apply($object_list);
        $this->editableList = mpull($editable_list, null, 'getPHID');

        return null;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function newBulkObjectList()
    {
        $viewer = $this->getViewer();

        $objects = $this->objectList;
        $objects = mpull($objects, null, 'getPHID');

        $handles = $viewer->loadHandles(array_keys($objects));

        $status_closed = PhabricatorObjectHandle::STATUS_CLOSED;

        $list = (new PHUIObjectItemListView())
            ->setViewer($viewer)
            ->setFlush(true);

        foreach ($objects as $phid => $object) {
            $handle = $handles[$phid];

            $is_closed = ($handle->getStatus() === $status_closed);
            $can_edit = isset($this->editableList[$phid]);
            $is_disabled = ($is_closed || !$can_edit);
            $is_selected = isset($this->targetList[$phid]);

            $item = (new PHUIObjectItemView())
                ->setHeader($handle->getFullName())
                ->setHref($handle->getURI())
                ->setDisabled($is_disabled)
                ->setSelectable('bulkTargetPHIDs[]', $phid, $is_selected, !$can_edit);

            if (!$can_edit) {
                $item->addIcon('fa-pencil red', \Yii::t("app", 'Not Editable'));
            }

            $list->addItem($item);
        }

        return $list;
    }

    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    private function newContextInputs()
    {
        $viewer = $this->getViewer();
        $controller = $this->getAction();
        $request = $controller->getRequest();

        $parameters = array();
        foreach ($this->context as $key => $value) {
            $parameters[$key] = $request->getStr($key);
        }

        $parameters = array(
                'bulkEngine' => 1,
            ) + $parameters;

        $result = array();
        foreach ($parameters as $key => $value) {
            $result[] = JavelinHtml::phutil_tag(
                'input',
                array(
                    'type' => 'hidden',
                    'name' => $key,
                    'value' => $value,
                ));
        }

        return $result;
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function newBulkActionForm()
    {
        $viewer = $this->getViewer();
        $input_id = JavelinHtml::generateUniqueNodeId();

        $edit_engine = $this->newEditEngine()
            ->setViewer($viewer);

        $edit_map = $edit_engine->newBulkEditMap();
        $groups = $edit_engine->newBulkEditGroupMap();

        $spec = array();
        $option_groups = igroup($edit_map, 'group');
        $default_value = null;
        foreach ($groups as $group_key => $group) {
            $options = ArrayHelper::getValue($option_groups, $group_key, array());
            if (!$options) {
                continue;
            }

            $option_map = array();
            foreach ($options as $option) {
                $option_map[] = array(
                    'key' => $option['xaction'],
                    'label' => $option['label'],
                );

                if ($default_value === null) {
                    $default_value = $option['xaction'];
                }
            }

            $spec[] = array(
                'label' => $group->getLabel(),
                'options' => $option_map,
            );
        }

//        require_celerity_resource('phui-bulk-editor-css');

        JavelinHtml::initBehavior(
            new JavelinBulkEditorBehaviorAsset(),
            array(
                'rootNodeID' => $this->getRootFormID(),
                'inputNodeID' => $input_id,
                'edits' => $edit_map,
                'optgroups' => array(
                    'value' => $default_value,
                    'groups' => $spec,
                ),
            ));

        $cancel_uri = $this->getCancelURI();

        return (new PHUIFormLayoutView())
            ->setViewer($viewer)
            ->appendChild(
                JavelinHtml::phutil_tag(
                    'input',
                    array(
                        'type' => 'hidden',
                        'name' => 'xactions',
                        'id' => $input_id,
                    )))
            ->appendChild(
                (new PHUIFormInsetView())
                    ->setTitle(\Yii::t("app", 'Bulk Edit Actions'))
                    ->setRightButton(
                        JavelinHtml::phutil_tag(
                            'a',
                            array(
                                'href' => '#',
                                'class' => 'btn btn-sm bg-' . PhabricatorEnv::getEnvConfig("ui.widget-color"),
                                'sigil' => 'add-action',
                                'mustcapture' => true,
                            ),
                            \Yii::t("app", 'Add Another Action')))
                    ->setContent(
                        JavelinHtml::phutil_tag(
                            'table',
                            array(
                                'sigil' => 'bulk-actions',
                                'class' => 'bulk-edit-table w-100',
                            ),
                            '')))
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->setValue(\Yii::t("app", 'Continue'))
                    ->addCancelButton($cancel_uri));
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    private function buildEditResponse()
    {
        $viewer = $this->getViewer();
        $controller = $this->getAction();
        $request = $controller->getRequest();

        if (!$this->objectList) {
            throw new Exception(\Yii::t("app", 'Query does not match any objects.'));
        }

        if (!$this->editableList) {
            throw new Exception(
                \Yii::t("app",
                    'Query does not match any objects you have permission to edit.'));
        }

        // Restrict the selection set to objects the user can actually edit.
        $objects = array_intersect_key($this->editableList, $this->targetList);

        if (!$objects) {
            throw new Exception(
                \Yii::t("app",
                    'You have not selected any objects to edit.'));
        }

        $raw_xactions = $request->getStr('xactions');
        if ($raw_xactions) {
            $raw_xactions = phutil_json_decode($raw_xactions);
        } else {
            $raw_xactions = array();
        }

        if (!$raw_xactions) {
            throw new Exception(
                \Yii::t("app",
                    'You have not chosen any edits to apply.'));
        }

        $edit_engine = $this->newEditEngine()
            ->setViewer($viewer);

        $xactions = $edit_engine->newRawBulkTransactions($raw_xactions);

        $cancel_uri = $this->getCancelURI();
        $done_uri = $this->getDoneURI();

        $job = PhabricatorWorkerBulkJob::initializeNewJob(
            $viewer,
            new PhabricatorEditEngineBulkJobType(),
            array(
                'objectPHIDs' => mpull($objects, 'getPHID'),
                'xactions' => $xactions,
                'cancelURI' => $cancel_uri,
                'doneURI' => $done_uri,
            ));

        $type_status = PhabricatorWorkerBulkJobTransaction::TYPE_STATUS;

        $xactions = array();
        $xactions[] = (new PhabricatorWorkerBulkJobTransaction())
            ->setTransactionType($type_status)
            ->setNewValue(PhabricatorWorkerBulkJob::STATUS_CONFIRM);

        (new PhabricatorWorkerBulkJobEditor())
            ->setActor($viewer)
            ->setContentSourceFromRequest($request)
            ->setContinueOnMissingFields(true)
            ->applyTransactions($job, $xactions);

        return (new AphrontRedirectResponse())
            ->setURI($job->getMonitorURI());
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getRootFormID()
    {
        if (!$this->rootFormID) {
            $this->rootFormID = JavelinHtml::generateUniqueNodeId();
        }

        return $this->rootFormID;
    }

}
