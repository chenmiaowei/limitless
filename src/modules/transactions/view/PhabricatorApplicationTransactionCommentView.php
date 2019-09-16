<?php

namespace orangins\modules\transactions\view;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\PhabricatorRemarkupControl;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUITimelineView;
use orangins\modules\draft\models\PhabricatorVersionedDraft;
use orangins\modules\transactions\assets\JavelinCommentActionAsset;
use orangins\modules\transactions\assets\JavelinTransactionCommentFormBehaviorAsset;
use orangins\modules\transactions\commentaction\PhabricatorEditEngineCommentAction;
use PhutilInvalidStateException;
use PhutilURI;
use yii\helpers\ArrayHelper;

/**
 * @concrete-extensible
 */
class PhabricatorApplicationTransactionCommentView extends AphrontView
{

    /**
     * @var
     */
    private $submitButtonName;
    /**
     * @var
     */
    private $action;

    /**
     * @var
     */
    private $previewPanelID;
    /**
     * @var
     */
    private $previewTimelineID;
    /**
     * @var
     */
    private $previewToggleID;
    /**
     * @var
     */
    private $formID;
    /**
     * @var
     */
    private $statusID;
    /**
     * @var
     */
    private $commentID;
    /**
     * @var
     */
    private $draft;
    /**
     * @var
     */
    private $requestURI;
    /**
     * @var bool
     */
    private $showPreview = true;
    /**
     * @var
     */
    private $objectPHID;
    /**
     * @var
     */
    private $headerText;
    /**
     * @var
     */
    private $noPermission;
    /**
     * @var
     */
    private $fullWidth;
    /**
     * @var
     */
    private $infoView;
    /**
     * @var
     */
    private $editEngineLock;
    /**
     * @var
     */
    private $noBorder;

    /**
     * @var
     */
    private $currentVersion;
    /**
     * @var
     */
    private $versionedDraft;
    /**
     * @var
     */
    private $commentActions;
    /**
     * @var array
     */
    private $commentActionGroups = array();
    /**
     * @var
     */
    private $transactionTimeline;

    /**
     * @param $object_phid
     * @return $this
     * @author 陈妙威
     */
    public function setObjectPHID($object_phid)
    {
        $this->objectPHID = $object_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getObjectPHID()
    {
        return $this->objectPHID;
    }

    /**
     * @param $show_preview
     * @return $this
     * @author 陈妙威
     */
    public function setShowPreview($show_preview)
    {
        $this->showPreview = $show_preview;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getShowPreview()
    {
        return $this->showPreview;
    }

    /**
     * @param PhutilURI $request_uri
     * @return $this
     * @author 陈妙威
     */
    public function setRequestURI(PhutilURI $request_uri)
    {
        $this->requestURI = $request_uri;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRequestURI()
    {
        return $this->requestURI;
    }

    /**
     * @param $current_version
     * @return $this
     * @author 陈妙威
     */
    public function setCurrentVersion($current_version)
    {
        $this->currentVersion = $current_version;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCurrentVersion()
    {
        return $this->currentVersion;
    }

    /**
     * @param PhabricatorVersionedDraft $versioned_draft
     * @return $this
     * @author 陈妙威
     */
    public function setVersionedDraft(
        PhabricatorVersionedDraft $versioned_draft)
    {
        $this->versionedDraft = $versioned_draft;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getVersionedDraft()
    {
        return $this->versionedDraft;
    }

    /**
     * @param PhabricatorDraft $draft
     * @return $this
     * @author 陈妙威
     */
    public function setDraft(PhabricatorDraft $draft)
    {
        $this->draft = $draft;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDraft()
    {
        return $this->draft;
    }

    /**
     * @param $submit_button_name
     * @return $this
     * @author 陈妙威
     */
    public function setSubmitButtonName($submit_button_name)
    {
        $this->submitButtonName = $submit_button_name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSubmitButtonName()
    {
        return $this->submitButtonName;
    }

    /**
     * @param $action
     * @return $this
     * @author 陈妙威
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param $text
     * @return $this
     * @author 陈妙威
     */
    public function setHeaderText($text)
    {
        $this->headerText = $text;
        return $this;
    }

    /**
     * @param $fw
     * @return $this
     * @author 陈妙威
     */
    public function setFullWidth($fw)
    {
        $this->fullWidth = $fw;
        return $this;
    }

    /**
     * @param PHUIInfoView $info_view
     * @return $this
     * @author 陈妙威
     */
    public function setInfoView(PHUIInfoView $info_view)
    {
        $this->infoView = $info_view;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getInfoView()
    {
        return $this->infoView;
    }

    /**
     * @param array $comment_actions
     * @return $this
     * @author 陈妙威
     */
    public function setCommentActions(array $comment_actions)
    {
        assert_instances_of($comment_actions, PhabricatorEditEngineCommentAction::class);
        $this->commentActions = $comment_actions;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCommentActions()
    {
        return $this->commentActions;
    }

    /**
     * @param array $groups
     * @return $this
     * @author 陈妙威
     */
    public function setCommentActionGroups(array $groups)
    {
        assert_instances_of($groups, 'PhabricatorEditEngineCommentActionGroup');
        $this->commentActionGroups = $groups;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getCommentActionGroups()
    {
        return $this->commentActionGroups;
    }

    /**
     * @param $no_permission
     * @return $this
     * @author 陈妙威
     */
    public function setNoPermission($no_permission)
    {
        $this->noPermission = $no_permission;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getNoPermission()
    {
        return $this->noPermission;
    }

    /**
     * @param PhabricatorEditEngineLock $lock
     * @return $this
     * @author 陈妙威
     */
    public function setEditEngineLock(PhabricatorEditEngineLock $lock)
    {
        $this->editEngineLock = $lock;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getEditEngineLock()
    {
        return $this->editEngineLock;
    }

    /**
     * @param PhabricatorApplicationTransactionView $timeline
     * @return $this
     * @author 陈妙威
     */
    public function setTransactionTimeline(
        PhabricatorApplicationTransactionView $timeline)
    {

        $timeline->setQuoteTargetID($this->getCommentID());
        if ($this->getNoPermission() || $this->getEditEngineLock()) {
            $timeline->setShouldTerminate(true);
        }

        $this->transactionTimeline = $timeline;
        return $this;
    }

    /**
     * @return array|mixed|null
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        if ($this->getNoPermission()) {
            return null;
        }

        $lock = $this->getEditEngineLock();
        if ($lock) {
            return (new PHUIInfoView())
                ->setSeverity(PHUIInfoView::SEVERITY_WARNING)
                ->setErrors(
                    array(
                        $lock->getLockedObjectDisplayText(),
                    ));
        }

        $user = $this->getUser();
        if (!$user->isLoggedIn()) {
            $uri = (new PhutilURI('/login/'))
                ->setQueryParam('next', (string)$this->getRequestURI());
            return (new PHUIObjectBoxView())
                ->setFlush(true)
                ->appendChild(
                    javelin_tag(
                        'a',
                        array(
                            'class' => 'login-to-comment button',
                            'href' => $uri,
                        ),
                        \Yii::t("app", 'Log In to Comment')));
        }

        $data = array();

        $comment = $this->renderCommentPanel();

        if ($this->getShowPreview()) {
            $preview = $this->renderPreviewPanel();
        } else {
            $preview = null;
        }

        if (!$this->getCommentActions()) {
            JavelinHtml::initBehavior(
                new JavelinTransactionCommentFormBehaviorAsset(),
                array(
                    'formID' => $this->getFormID(),
                    'timelineID' => $this->getPreviewTimelineID(),
                    'panelID' => $this->getPreviewPanelID(),
                    'showPreview' => $this->getShowPreview(),
                    'actionURI' => $this->getAction(),
                ));
        }

        $image_uri = $user->getProfileImageURI();
        $image = JavelinHtml::phutil_tag(
            'div',
            array(
                'style' => 'background-image: url(' . $image_uri . ')',
                'class' => 'phui-comment-image visual-only',
            ));
        $wedge = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-timeline-wedge',
            ),
            '');


        $comment_box = (new PHUIObjectBoxView())
            ->setFlush(true)
            ->addClass('phui-comment-form-view')
            ->addSigil('phui-comment-form')
            ->appendChild(
                JavelinHtml::phutil_tag(
                    'h3',
                    array(
                        'class' => 'aural-only',
                    ),
                    \Yii::t("app", 'Add Comment')))
            ->appendChild($image)
            ->appendChild($wedge)
            ->appendChild($comment);

        return array($comment_box, $preview);
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function renderCommentPanel()
    {
        $draft_comment = '';
        $draft_key = null;
        if ($this->getDraft()) {
            $draft_comment = $this->getDraft()->getDraft();
            $draft_key = $this->getDraft()->getDraftKey();
        }

        $versioned_draft = $this->getVersionedDraft();
        if ($versioned_draft) {
            $draft_comment = $versioned_draft->getProperty('comment', '');
        }

        if (!$this->getObjectPHID()) {
            throw new PhutilInvalidStateException('setObjectPHID', 'render');
        }

        $version_key = PhabricatorVersionedDraft::KEY_VERSION;
        $version_value = $this->getCurrentVersion();

        $form = (new AphrontFormView())
            ->setUser($this->getUser())
            ->addSigil('transaction-append')
            ->setWorkflow(true)
            ->setFullWidth($this->fullWidth)
            ->setMetadata(
                array(
                    'objectPHID' => $this->getObjectPHID(),
                ))
            ->setAction($this->getAction())
            ->setID($this->getFormID())
            ->addHiddenInput('__draft__', $draft_key)
            ->addHiddenInput($version_key, $version_value);

        $comment_actions = $this->getCommentActions();
        if ($comment_actions) {
            $action_map = array();
            $type_map = array();

            $comment_actions = mpull($comment_actions, null, 'getKey');

            $draft_actions = array();
            $draft_keys = array();
            if ($versioned_draft) {
                $draft_actions = $versioned_draft->getProperty('actions', array());

                if (!is_array($draft_actions)) {
                    $draft_actions = array();
                }

                foreach ($draft_actions as $action) {
                    $type = ArrayHelper::getValue($action, 'type');
                    $comment_action = ArrayHelper::getValue($comment_actions, $type);
                    if (!$comment_action) {
                        continue;
                    }

                    $value = ArrayHelper::getValue($action, 'value');
                    $comment_action->setValue($value);

                    $draft_keys[] = $type;
                }
            }

            foreach ($comment_actions as $key => $comment_action) {
                $key = $comment_action->getKey();
                $label = $comment_action->getLabel();

                $action_map[$key] = array(
                    'key' => $key,
                    'label' => $label,
                    'type' => $comment_action->getPHUIXControlType(),
                    'spec' => $comment_action->getPHUIXControlSpecification(),
                    'initialValue' => $comment_action->getInitialValue(),
                    'groupKey' => $comment_action->getGroupKey(),
                    'conflictKey' => $comment_action->getConflictKey(),
                    'auralLabel' => \Yii::t("app", 'Remove Action: {0}', [
                        $label
                    ]),
                    'buttonText' => $comment_action->getSubmitButtonText(),
                );

                $type_map[$key] = $comment_action;
            }

            $options = $this->newCommentActionOptions($action_map);

            $action_id = JavelinHtml::generateUniqueNodeId();
            $input_id = JavelinHtml::generateUniqueNodeId();
            $place_id = JavelinHtml::generateUniqueNodeId();


            $form->appendChild(
                JavelinHtml::phutil_tag(
                    'input',
                    array(
                        'type' => 'hidden',
                        'name' => 'editengine.actions',
                        'id' => $input_id,
                    )));

            $invisi_bar = JavelinHtml::phutil_tag(
                'div',
                array(
                    'id' => $place_id,
                    'class' => 'phui-comment-control-stack',
                ));

            $action_select = (new AphrontFormSelectControl())
                ->addClass('phui-comment-fullwidth-control')
                ->addClass('phui-comment-action-control')
                ->setID($action_id)
                ->setOptions($options);

            $action_bar = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-comment-action-bar grouped',
                ),
                array(
                    $action_select,
                ));

            $form->appendChild($action_bar);

            $info_view = $this->getInfoView();
            if ($info_view) {
                $form->appendChild($info_view);
            }

            $form->appendChild($invisi_bar);
            $form->addClass('phui-comment-has-actions');

            JavelinHtml::initBehavior(
                new JavelinCommentActionAsset(),
                array(
                    'actionID' => $action_id,
                    'inputID' => $input_id,
                    'formID' => $this->getFormID(),
                    'placeID' => $place_id,
                    'panelID' => $this->getPreviewPanelID(),
                    'timelineID' => $this->getPreviewTimelineID(),
                    'actions' => $action_map,
                    'showPreview' => $this->getShowPreview(),
                    'actionURI' => $this->getAction(),
                    'drafts' => $draft_keys,
                    'defaultButtonText' => $this->getSubmitButtonName(),
                ));
        }

        $submit_button = (new AphrontFormSubmitControl())
            ->addClass('phui-comment-fullwidth-control')
            ->addClass('phui-comment-submit-control')
            ->setValue($this->getSubmitButtonName());

        $form
            ->appendChild(
                (new PhabricatorRemarkupControl())
                    ->setID($this->getCommentID())
                    ->addClass('phui-comment-fullwidth-control')
                    ->addClass('phui-comment-textarea-control')
                    ->setCanPin(true)
                    ->setName('comment')
                    ->setUser($this->getUser())
                    ->setValue($draft_comment))
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->addClass('phui-comment-fullwidth-control')
                    ->addClass('phui-comment-submit-control')
                    ->addSigil('submit-transactions')
                    ->setValue($this->getSubmitButtonName()));

        return $form;
    }

    /**
     * @return \PhutilSafeHTML|string
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderPreviewPanel()
    {

        $preview = (new PHUITimelineView())
            ->setID($this->getPreviewTimelineID());

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'id' => $this->getPreviewPanelID(),
                'style' => 'display: none',
                'class' => 'phui-comment-preview-view',
            ),
            $preview);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getPreviewPanelID()
    {
        if (!$this->previewPanelID) {
            $this->previewPanelID = JavelinHtml::generateUniqueNodeId();
        }
        return $this->previewPanelID;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getPreviewTimelineID()
    {
        if (!$this->previewTimelineID) {
            $this->previewTimelineID = JavelinHtml::generateUniqueNodeId();
        }
        return $this->previewTimelineID;
    }

    /**
     * @param $id
     * @return $this
     * @author 陈妙威
     */
    public function setFormID($id)
    {
        $this->formID = $id;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getFormID()
    {
        if (!$this->formID) {
            $this->formID = JavelinHtml::generateUniqueNodeId();
        }
        return $this->formID;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getStatusID()
    {
        if (!$this->statusID) {
            $this->statusID = JavelinHtml::generateUniqueNodeId();
        }
        return $this->statusID;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    private function getCommentID()
    {
        if (!$this->commentID) {
            $this->commentID = JavelinHtml::generateUniqueNodeId();
        }
        return $this->commentID;
    }

    /**
     * @param array $action_map
     * @return array
     * @author 陈妙威
     */
    private function newCommentActionOptions(array $action_map)
    {
        $options = array();
        $options['+'] = \Yii::t("app", 'Add Action...');

        // Merge options into groups.
        $groups = array();
        foreach ($action_map as $key => $item) {
            $group_key = $item['groupKey'];
            if (!isset($groups[$group_key])) {
                $groups[$group_key] = array();
            }
            $groups[$group_key][$key] = $item;
        }

        $group_specs = $this->getCommentActionGroups();
        $group_labels = mpull($group_specs, 'getLabel', 'getKey');

        // Reorder groups to put them in the same order as the recognized
        // group definitions.
        $groups = array_select_keys($groups, array_keys($group_labels)) + $groups;

        // Move options with no group to the end.
        $default_group = ArrayHelper::getValue($groups, '');
        if ($default_group) {
            unset($groups['']);
            $groups[''] = $default_group;
        }

        foreach ($groups as $group_key => $group_items) {
            if (strlen($group_key)) {
                $group_label = ArrayHelper::getValue($group_labels, $group_key, $group_key);
                $options[$group_label] = ipull($group_items, 'label');
            } else {
                foreach ($group_items as $key => $item) {
                    $options[$key] = $item['label'];
                }
            }
        }

        return $options;
    }
}
