<?php

namespace orangins\modules\transactions\models;

use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\OranginsObject;
use orangins\modules\policy\constants\PhabricatorPolicyType;
use orangins\modules\policy\models\PhabricatorPolicy;
use orangins\modules\transactions\data\PhabricatorTransactionRemarkupChange;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\error\PhabricatorApplicationTransactionValidationError;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use PhutilCalendarAbsoluteDateTime;
use PhutilMethodNotImplementedException;
use PhutilInvalidStateException;
use yii\helpers\Html;

/**
 * Class PhabricatorModularTransactionType
 * @package orangins\modules\transactions\models
 * @author 陈妙威
 */
abstract class PhabricatorModularTransactionType extends OranginsObject
{

    /**
     * @var
     */
    private $storage;
    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $editor;

    /**
     * @return mixed
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public function getTransactionTypeConstant()
    {
        return $this->getPhobjectClassConstant('TRANSACTIONTYPE');
    }

    /**
     * @param $object
     * @author 陈妙威
     * @throws PhutilMethodNotImplementedException
     */
    public function generateOldValue($object)
    {
        throw new PhutilMethodNotImplementedException();
    }

    /**
     * @param $object
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function generateNewValue($object, $value)
    {
        return $value;
    }

    /**
     * @param $object
     * @param array $xactions
     * @return array
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        return array();
    }

    /**
     * @param $object
     * @param array $xactions
     * @author 陈妙威
     */
    public function willApplyTransactions($object, array $xactions)
    {
        return;
    }

    /**
     * @param $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        return;
    }

    /**
     * @param $object
     * @param $value
     * @author 陈妙威
     */
    public function applyExternalEffects($object, $value)
    {
        return;
    }

    /**
     * @param $object
     * @param $value
     * @author 陈妙威
     */
    public function didCommitTransaction($object, $value)
    {
        return;
    }

    /**
     * @param $object
     * @param $old
     * @param $new
     * @return bool
     * @author 陈妙威
     */
    public function getTransactionHasEffect($object, $old, $new)
    {
        return ($old !== $new);
    }

    /**
     * @param $object
     * @param $value
     * @return array
     * @author 陈妙威
     */
    public function extractFilePHIDs($object, $value)
    {
        return array();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldHide()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldHideForFeed()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldHideForMail()
    {
        return false;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getIcon()
    {
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getTitle()
    {
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getActionName()
    {
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getActionStrength()
    {
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getColor()
    {
        return null;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function hasChangeDetailView()
    {
        return false;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function newChangeDetailView()
    {
        return null;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMailDiffSectionHeader()
    {
        return \Yii::t("app", 'EDIT DETAILS');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function newRemarkupChanges()
    {
        return array();
    }

    /**
     * @param $object
     * @param PhabricatorApplicationTransaction $u
     * @param PhabricatorApplicationTransaction $v
     * @return null
     * @author 陈妙威
     */
    public function mergeTransactions(
        $object,
        PhabricatorApplicationTransaction $u,
        PhabricatorApplicationTransaction $v)
    {
        return null;
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return $this
     * @author 陈妙威
     */
    final public function setStorage(
        PhabricatorApplicationTransaction $xaction)
    {
        $this->storage = $xaction;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getStorage()
    {
        return $this->storage;
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
    final protected function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    final public function getActor()
    {
        return $this->getEditor()->getActor();
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    final public function getActingAsPHID()
    {
        return $this->getEditor()->getActingAsPHID();
    }

    /**
     * @param PhabricatorApplicationTransactionEditor $editor
     * @return $this
     * @author 陈妙威
     */
    final public function setEditor(
        PhabricatorApplicationTransactionEditor $editor)
    {
        $this->editor = $editor;
        return $this;
    }

    /**
     * @return PhabricatorApplicationTransactionEditor
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    final protected function getEditor()
    {
        if (!$this->editor) {
            throw new PhutilInvalidStateException('setEditor');
        }
        return $this->editor;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    final protected function hasEditor()
    {
        return (bool)$this->editor;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function getAuthorPHID()
    {
        return $this->getStorage()->getAuthorPHID();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function getObjectPHID()
    {
        return $this->getStorage()->getObjectPHID();
    }

    /**
     * @return PhabricatorApplicationTransactionInterface
     * @author 陈妙威
     */
    final protected function getObject()
    {
        return $this->getStorage()->getObject();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function getOldValue()
    {
        return $this->getStorage()->getOldValue();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function getNewValue()
    {
        return $this->getStorage()->getNewValue();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function renderAuthor()
    {
        $author_phid = $this->getAuthorPHID();
        return $this->getStorage()->renderHandleLink($author_phid);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function renderObject()
    {
        $object_phid = $this->getObjectPHID();
        return $this->getStorage()->renderHandleLink($object_phid);
    }

    /**
     * @param $phid
     * @return mixed
     * @author 陈妙威
     */
    final protected function renderHandle($phid)
    {
        $viewer = $this->getViewer();
        $display = $viewer->renderHandle($phid);

        if ($this->isTextMode()) {
            $display->setAsText(true);
        }

        return $display;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function renderOldHandle()
    {
        return $this->renderHandle($this->getOldValue());
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function renderNewHandle()
    {
        return $this->renderHandle($this->getNewValue());
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    final protected function renderOldPolicy()
    {
        return $this->renderPolicy($this->getOldValue(), 'old');
    }

    /**
     * @return string
     * @throws \ReflectionException
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    final protected function renderNewPolicy()
    {
        return $this->renderPolicy($this->getNewValue(), 'new');
    }

    /**
     * @param $phid
     * @param $mode
     * @return string
     * @throws \ReflectionException
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    final protected function renderPolicy($phid, $mode)
    {
        $viewer = $this->getViewer();
        $handles = $viewer->loadHandles(array($phid));

        $policy = PhabricatorPolicy::newFromPolicyAndHandle(
            $phid,
            $handles[$phid]);

        if ($this->isTextMode()) {
            return $this->renderValue($policy->getFullName());
        }

        $storage = $this->getStorage();
        if ($policy->getType() == PhabricatorPolicyType::TYPE_CUSTOM) {
            $policy->setHref('/transactions/' . $mode . '/' . $storage->getPHID() . '/');
            $policy->setWorkflow(true);
        }

        return $this->renderValue($policy->renderDescription());
    }

    /**
     * @param array $phids
     * @return mixed
     * @author 陈妙威
     */
    final protected function renderHandleList(array $phids)
    {
        $viewer = $this->getViewer();
        $display = $viewer->renderHandleList($phids)
            ->setAsInline(true);

        if ($this->isTextMode()) {
            $display->setAsText(true);
        }

        return $display;
    }

    /**
     * @param $value
     * @return string
     * @author 陈妙威
     */
    final protected function renderValue($value)
    {
        if ($this->isTextMode()) {
            return sprintf('"%s"', $value);
        }

        return Html::tag(
            'span',
            $value,
            array(
                'class' => 'phui-timeline-value',
            ));
    }

    /**
     * @param array $values
     * @return string
     * @author 陈妙威
     */
    final protected function renderValueList(array $values)
    {
        $result = array();
        foreach ($values as $value) {
            $result[] = $this->renderValue($value);
        }

        if ($this->isTextMode()) {
            return implode(', ', $result);
        }

        return phutil_implode_html(', ', $result);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    final protected function renderOldValue()
    {
        return $this->renderValue($this->getOldValue());
    }

    /**
     * @return string
     * @author 陈妙威
     */
    final protected function renderNewValue()
    {
        return $this->renderValue($this->getNewValue());
    }

    /**
     * @param $epoch
     * @return string
     * @throws \ReflectionException

     * @throws \Exception
     * @throws \Exception
     * @author 陈妙威
     */
    final protected function renderDate($epoch)
    {
        $viewer = $this->getViewer();

        // We accept either epoch timestamps or dictionaries describing a
        // PhutilCalendarDateTime.
        if (is_array($epoch)) {
            /** @var PhutilCalendarAbsoluteDateTime $absoluteDateTime */
            $absoluteDateTime = PhutilCalendarAbsoluteDateTime::newFromDictionary($epoch);
            $datetime = $absoluteDateTime
                ->setViewerTimezone($viewer->getTimezoneIdentifier());

            $all_day = $datetime->getIsAllDay();

            $epoch = $datetime->getEpoch();
        } else {
            $all_day = false;
        }

        if ($all_day) {
            $display = OranginsViewUtil::phabricator_date($epoch, $viewer);
        } else if ($this->isRenderingTargetExternal()) {
            // When rendering to text, we explicitly render the offset from UTC to
            // provide context to the date: the mail may be generating with the
            // server's settings, or the user may later refer back to it after
            // changing timezones.


            $display = OranginsViewUtil::phabricator_datetimezone($epoch, $viewer);
        } else {
            $display = OranginsViewUtil::phabricator_datetime($epoch, $viewer);
        }

        return $this->renderValue($display);
    }

    /**
     * @return string
     * @throws \ReflectionException

     * @throws \Exception
     * @author 陈妙威
     */
    final protected function renderOldDate()
    {
        return $this->renderDate($this->getOldValue());
    }

    /**
     * @return string
     * @throws \ReflectionException

     * @throws \Exception
     * @author 陈妙威
     */
    final protected function renderNewDate()
    {
        return $this->renderDate($this->getNewValue());
    }

    /**
     * @param $title
     * @param $message
     * @param null $xaction
     * @return PhabricatorApplicationTransactionValidationError
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    final protected function newError($title, $message, $xaction = null)
    {
        return new PhabricatorApplicationTransactionValidationError(
            $this->getTransactionTypeConstant(),
            $title,
            $message,
            $xaction);
    }

    /**
     * @param $message
     * @param null $xaction
     * @return mixed
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    final protected function newRequiredError($message, $xaction = null)
    {
        return $this->newError(\Yii::t("app", 'Required'), $message, $xaction)
            ->setIsMissingFieldError(true);
    }

    /**
     * @param $message
     * @param null $xaction
     * @return PhabricatorApplicationTransactionValidationError
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    final protected function newInvalidError($message, $xaction = null)
    {
        return $this->newError(\Yii::t("app", 'Invalid'), $message, $xaction);
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     */
    final protected function isNewObject()
    {
        return $this->getEditor()->getIsNewObject();
    }

    /**
     * @param $value
     * @param PhabricatorModularTransaction[] $xactions
     * @return bool
     * @author 陈妙威
     * @throws \PhutilJSONParserException
     */
    final protected function isEmptyTextTransaction($value, array $xactions)
    {
        foreach ($xactions as $xaction) {
            $value = $xaction->getNewValue();
        }
        return !strlen($value);
    }

    /**
     * When rendering to external targets (Email/Asana/etc), we need to include
     * more information that users can't obtain later.
     */
    final protected function isRenderingTargetExternal()
    {
        // Right now, this is our best proxy for this:
        return $this->isTextMode();
        // "TARGET_TEXT" means "EMail" and "TARGET_HTML" means "Web".
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    final protected function isTextMode()
    {
        $target = $this->getStorage()->getRenderingTarget();
        return ($target == PhabricatorApplicationTransaction::TARGET_TEXT);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function newRemarkupChange()
    {
        return (new PhabricatorTransactionRemarkupChange())
            ->setTransaction($this->getStorage());
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function isCreateTransaction()
    {
        return $this->getStorage()->getIsCreateTransaction();
    }

    /**
     * @param array $old
     * @param array $new
     * @return mixed
     * @author 陈妙威
     * @throws PhutilInvalidStateException
     * @throws \Exception
     */
    final protected function getPHIDList(array $old, array $new)
    {
        $editor = $this->getEditor();
        return $editor->getPHIDList($old, $new);
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     * @author 陈妙威
     */
    public function getMetadataValue($key, $default = null)
    {
        return $this->getStorage()->getMetadataValue($key, $default);
    }

    /**
     * @param array $xactions
     * @return null
     * @author 陈妙威
     */
    public function loadTransactionTypeConduitData(array $xactions)
    {
        return null;
    }

    /**
     * @param $xaction
     * @return null
     * @author 陈妙威
     */
    public function getTransactionTypeForConduit($xaction)
    {
        return null;
    }

    /**
     * @param $xaction
     * @param $data
     * @return array
     * @author 陈妙威
     */
    public function getFieldValuesForConduit($xaction, $data)
    {
        return array();
    }

    /**
     * @param $capability
     * @throws PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function requireApplicationCapability($capability)
    {
        $application_class = $this->getEditor()->getEditorApplicationClass();
        $application = OranginsUtil::newv($application_class, array());

        PhabricatorPolicyFilter::requireCapability(
            $this->getActor(),
            $application,
            $capability);
    }

    /**
     * Get a list of capabilities the actor must have on the object to apply
     * a transaction to it.
     *
     * Usually, you should use this to reduce capability requirements when a
     * transaction (like leaving a Conpherence thread) can be applied without
     * having edit permission on the object. You can override this method to
     * remove the CAN_EDIT requirement, or to replace it with a different
     * requirement.
     *
     * If you are increasing capability requirements and need to add an
     * additional capability or policy requirement above and beyond CAN_EDIT, it
     * is usually better implemented as a validation check.
     *
     * @param object Object being edited.
     * @param PhabricatorApplicationTransaction Transaction being applied.
     * @return string
     *    capability constants) which the actor must have on the object. You can
     *    return `null` as a shorthand for "no capabilities are required".
     */
    public function getRequiredCapabilities(
        $object,
        PhabricatorApplicationTransaction $xaction)
    {
        return PhabricatorPolicyCapability::CAN_EDIT;
    }

}
