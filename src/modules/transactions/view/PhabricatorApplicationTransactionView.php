<?php

namespace orangins\modules\transactions\view;

use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\markup\PhabricatorMarkupEngine;
use orangins\lib\view\AphrontView;
use orangins\lib\view\control\AphrontCursorPagerView;
use orangins\lib\view\phui\PHUITimelineEventView;
use orangins\lib\view\phui\PHUITimelineView;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\transactions\assets\JavelinTransactionListAsset;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\models\PhabricatorApplicationTransactionComment;
use PhutilInvalidStateException;
use PhutilJSONParserException;
use PhutilSafeHTML;
use ReflectionException;
use Throwable;
use Yii;
use Exception;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * @concrete-extensible
 */
class PhabricatorApplicationTransactionView extends AphrontView
{

    /**
     * @var PhabricatorApplicationTransaction[]
     */
    private $transactions;
    /**
     * @var
     */
    private $engine;
    /**
     * @var bool
     */
    private $showEditActions = true;
    /**
     * @var
     */
    private $isPreview;
    /**
     * @var
     */
    private $objectPHID;
    /**
     * @var bool
     */
    private $shouldTerminate = false;
    /**
     * @var
     */
    private $quoteTargetID;
    /**
     * @var
     */
    private $quoteRef;
    /**
     * @var
     */
    private $pager;
    /**
     * @var
     */
    private $renderAsFeed;
    /**
     * @var array
     */
    private $renderData = array();
    /**
     * @var bool
     */
    private $hideCommentOptions = false;

    /**
     * @param $feed
     * @return $this
     * @author 陈妙威
     */
    public function setRenderAsFeed($feed)
    {
        $this->renderAsFeed = $feed;
        return $this;
    }

    /**
     * @param $quote_ref
     * @return $this
     * @author 陈妙威
     */
    public function setQuoteRef($quote_ref)
    {
        $this->quoteRef = $quote_ref;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getQuoteRef()
    {
        return $this->quoteRef;
    }

    /**
     * @param $quote_target_id
     * @return $this
     * @author 陈妙威
     */
    public function setQuoteTargetID($quote_target_id)
    {
        $this->quoteTargetID = $quote_target_id;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getQuoteTargetID()
    {
        return $this->quoteTargetID;
    }

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
     * @param $is_preview
     * @return $this
     * @author 陈妙威
     */
    public function setIsPreview($is_preview)
    {
        $this->isPreview = $is_preview;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsPreview()
    {
        return $this->isPreview;
    }

    /**
     * @param $show_edit_actions
     * @return $this
     * @author 陈妙威
     */
    public function setShowEditActions($show_edit_actions)
    {
        $this->showEditActions = $show_edit_actions;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getShowEditActions()
    {
        return $this->showEditActions;
    }

    /**
     * @param PhabricatorMarkupEngine $engine
     * @return $this
     * @author 陈妙威
     */
    public function setMarkupEngine(PhabricatorMarkupEngine $engine)
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * @param array $transactions
     * @return $this
     * @author 陈妙威
     */
    public function setTransactions(array $transactions)
    {
        assert_instances_of($transactions, PhabricatorApplicationTransaction::class);
        $this->transactions = $transactions;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @param $term
     * @return $this
     * @author 陈妙威
     */
    public function setShouldTerminate($term)
    {
        $this->shouldTerminate = $term;
        return $this;
    }

    /**
     * @param AphrontCursorPagerView $pager
     * @return $this
     * @author 陈妙威
     */
    public function setPager(AphrontCursorPagerView $pager)
    {
        $this->pager = $pager;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPager()
    {
        return $this->pager;
    }

    /**
     * This is additional data that may be necessary to render the next set
     * of transactions. Objects that implement
     * PhabricatorApplicationTransactionInterface use this data in
     * willRenderTimeline.
     * @param array $data
     * @return PhabricatorApplicationTransactionView
     */
    public function setRenderData(array $data)
    {
        $this->renderData = $data;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getRenderData()
    {
        return $this->renderData;
    }

    /**
     * @param $hide_comment_options
     * @return $this
     * @author 陈妙威
     */
    public function setHideCommentOptions($hide_comment_options)
    {
        $this->hideCommentOptions = $hide_comment_options;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getHideCommentOptions()
    {
        return $this->hideCommentOptions;
    }

    /**
     * @param bool $with_hiding
     * @return array
     * @throws InvalidConfigException
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws ReflectionException
     * @throws Throwable
     * @author 陈妙威
     */
    public function buildEvents($with_hiding = false)
    {
        $user = $this->getUser();

        $xactions = $this->transactions;

        $xactions = $this->filterHiddenTransactions($xactions);
        $xactions = $this->groupRelatedTransactions($xactions);
        $groups = $this->groupDisplayTransactions($xactions);

        // If the viewer has interacted with this object, we hide things from
        // before their most recent interaction by default. This tends to make
        // very long threads much more manageable, because you don't have to
        // scroll through a lot of history and can focus on just new stuff.

        $show_group = null;

        if ($with_hiding) {
            // Find the most recent comment by the viewer.
            $group_keys = array_keys($groups);
            $group_keys = array_reverse($group_keys);

            // If we would only hide a small number of transactions, don't hide
            // anything. Just don't examine the last few keys. Also, we always
            // want to show the most recent pieces of activity, so don't examine
            // the first few keys either.
            $group_keys = array_slice($group_keys, 2, -2);

            $type_comment = PhabricatorTransactions::TYPE_COMMENT;
            foreach ($group_keys as $group_key) {
                $group = $groups[$group_key];
                foreach ($group as $xaction) {
                    if ($xaction->getAuthorPHID() == $user->getPHID() &&
                        $xaction->getTransactionType() == $type_comment) {
                        // This is the most recent group where the user commented.
                        $show_group = $group_key;
                        break 2;
                    }
                }
            }
        }

        $events = array();
        $hide_by_default = ($show_group !== null);
        $set_next_page_id = false;

        foreach ($groups as $group_key => $group) {
            if ($hide_by_default && ($show_group === $group_key)) {
                $hide_by_default = false;
                $set_next_page_id = true;
            }

            /** @var PHUITimelineEventView $group_event */
            $group_event = null;
            foreach ($group as $xaction) {
                $event = $this->renderEvent($xaction, $group);
                $event->setHideByDefault($hide_by_default);
                if (!$group_event) {
                    $group_event = $event;
                } else {
                    $group_event->addEventToGroup($event);
                }
                if ($set_next_page_id) {
                    $set_next_page_id = false;
                    $pager = $this->getPager();
                    if ($pager) {
                        $pager->setNextPageID($xaction->getID());
                    }
                }
            }
            $events[] = $group_event;

        }

        return $events;
    }

    /**
     * @return mixed
     * @throws InvalidConfigException
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws ReflectionException
     * @throws Throwable
     * @author 陈妙威
     */
    public function render()
    {
        if (!$this->getObjectPHID()) {
            throw new PhutilInvalidStateException('setObjectPHID');
        }

        $view = $this->buildPHUITimelineView();

        if ($this->getShowEditActions()) {
            JavelinHtml::initBehavior(new JavelinTransactionListAsset());
        }

        return $view->render();
    }

    /**
     * @param bool $with_hiding
     * @return mixed
     * @throws InvalidConfigException
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws ReflectionException
     * @throws Throwable
     * @author 陈妙威
     */
    public function buildPHUITimelineView($with_hiding = true)
    {
        if (!$this->getObjectPHID()) {
            throw new PhutilInvalidStateException('setObjectPHID');
        }

        $view = (new PHUITimelineView())
            ->setUser($this->getUser())
            ->setShouldTerminate($this->shouldTerminate)
            ->setQuoteTargetID($this->getQuoteTargetID())
            ->setQuoteRef($this->getQuoteRef());

        $events = $this->buildEvents($with_hiding);
        foreach ($events as $event) {
            $view->addEvent($event);
        }

        if ($this->getPager()) {
            $view->setPager($this->getPager());
        }

        if ($this->getRenderData()) {
            $view->setRenderData($this->getRenderData());
        }

        return $view;
    }

    /**
     * @return bool
     * @throws InvalidConfigException
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws ReflectionException
     * @throws Throwable
     * @author 陈妙威
     */
    public function isTimelineEmpty()
    {
        return !count($this->buildEvents(true));
    }

    /**
     * @return PhabricatorMarkupEngine
     * @throws PhutilInvalidStateException
     * @throws Throwable
     * @author 陈妙威
     */
    protected function getOrBuildEngine()
    {
        if (!$this->engine) {
            $field = PhabricatorApplicationTransactionComment::MARKUP_FIELD_COMMENT;

            $engine = (new PhabricatorMarkupEngine())
                ->setViewer($this->getUser());
            foreach ($this->transactions as $xaction) {
                if (!$xaction->hasComment()) {
                    continue;
                }
                $engine->addObject($xaction->getComment(), $field);
            }
            $engine->process();

            $this->engine = $engine;
        }

        return $this->engine;
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function buildChangeDetailsLink(
        PhabricatorApplicationTransaction $xaction)
    {

        return JavelinHtml::phutil_tag(
            'a',
            array(
                'href' => $xaction->getChangeDetailsURI(),
                'sigil' => 'workflow',
            ),
            Yii::t("app",'(Show Details)'));
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return null|PhutilSafeHTML
     * @throws Exception
     * @author 陈妙威
     */
    private function buildExtraInformationLink(
        PhabricatorApplicationTransaction $xaction)
    {

        $link = $xaction->renderExtraInformationLink();
        if (!$link) {
            return null;
        }

        return phutil_tag(
            'span',
            array(
                'class' => 'phui-timeline-extra-information',
            ),
            array(" \xC2\xB7  ", $link));
    }

    /**
     * @param PhabricatorApplicationTransaction $u
     * @param PhabricatorApplicationTransaction $v
     * @return bool
     * @author 陈妙威
     */
    protected function shouldGroupTransactions(
        PhabricatorApplicationTransaction $u,
        PhabricatorApplicationTransaction $v)
    {
        return false;
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @return null
     * @throws PhutilInvalidStateException
     * @throws Throwable
     * @author 陈妙威
     */
    protected function renderTransactionContent(
        PhabricatorApplicationTransaction $xaction)
    {

        $field = PhabricatorApplicationTransactionComment::MARKUP_FIELD_COMMENT;
        $engine = $this->getOrBuildEngine();
        $comment = $xaction->getComment();

        if ($comment) {
            if ($comment->getIsRemoved()) {
                return JavelinHtml::phutil_tag(
                    'span',
                    array(
                        'class' => 'comment-deleted',
                        'sigil' => 'transaction-comment',
                        'meta' => array('phid' => $comment->getTransactionPHID()),
                    ),
                    Yii::t("app",
                        'This comment was removed by %s.',
                        $xaction->getHandle($comment->getAuthorPHID())->renderLink()));
            } else if ($comment->getIsDeleted()) {
                return JavelinHtml::phutil_tag(
                    'span',
                    array(
                        'class' => 'comment-deleted',
                        'sigil' => 'transaction-comment',
                        'meta' => array('phid' => $comment->getTransactionPHID()),
                    ),
                    Yii::t("app",'This comment has been deleted.'));
            } else if ($xaction->hasComment()) {

                $content = $engine->getOutput($comment, $field);
                return JavelinHtml::phutil_tag(
                    'span',
                    array(
                        'class' => 'transaction-comment',
                        'sigil' => 'transaction-comment',
                        'meta' => array('phid' => $comment->getTransactionPHID()),
                    ),
                    $content);
            } else {
                // This is an empty, non-deleted comment. Usually this happens when
                // rendering previews.
                return null;
            }
        }

        return null;
    }

    /**
     * @param array $xactions
     * @return array
     * @author 陈妙威
     */
    private function filterHiddenTransactions(array $xactions)
    {
        foreach ($xactions as $key => $xaction) {
            if ($xaction->shouldHide()) {
                unset($xactions[$key]);
            }
        }
        return $xactions;
    }

    /**
     * @param array $xactions
     * @return array
     * @author 陈妙威
     */
    private function groupRelatedTransactions(array $xactions)
    {
        $last = null;
        $last_key = null;
        $groups = array();
        foreach ($xactions as $key => $xaction) {
            if ($last && $this->shouldGroupTransactions($last, $xaction)) {
                $groups[$last_key][] = $xaction;
                unset($xactions[$key]);
            } else {
                $last = $xaction;
                $last_key = $key;
            }
        }

        foreach ($xactions as $key => $xaction) {
            $xaction->attachTransactionGroup(ArrayHelper::getValue($groups, $key, array()));
        }

        return $xactions;
    }

    /**
     * @param array $xactions
     * @return array
     * @author 陈妙威
     */
    private function groupDisplayTransactions(array $xactions)
    {
        $groups = array();
        $group = array();
        foreach ($xactions as $xaction) {
            if ($xaction->shouldDisplayGroupWith($group)) {
                $group[] = $xaction;
            } else {
                if ($group) {
                    $groups[] = $group;
                }
                $group = array($xaction);
            }
        }

        if ($group) {
            $groups[] = $group;
        }

        foreach ($groups as $key => $group) {
            $results = array();

            // Sort transactions within the group by action strength, then by
            // chronological order. This makes sure that multiple actions of the
            // same type (like a close, then a reopen) render in the order they
            // were performed.
            /** @var array $strength_groups */
            $strength_groups = mgroup($group, 'getActionStrength');
            krsort($strength_groups);
            foreach ($strength_groups as $strength_group) {
                foreach (msort($strength_group, 'getID') as $xaction) {
                    $results[] = $xaction;
                }
            }

            $groups[$key] = $results;
        }

        return $groups;
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @param array $group
     * @return mixed
     * @throws InvalidConfigException *@throws \Exception
     * @throws PhabricatorDataNotAttachedException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws ReflectionException
     * @throws Throwable
     * @author 陈妙威
     */
    private function renderEvent(
        PhabricatorApplicationTransaction $xaction,
        array $group)
    {
        $viewer = $this->getUser();

        $event = (new PHUITimelineEventView())
            ->setUser($viewer)
            ->setAuthorPHID($xaction->getAuthorPHID())
            ->setTransactionPHID($xaction->getPHID())
            ->setUserHandle($xaction->getHandle($xaction->getAuthorPHID()))
            ->setIcon($xaction->getIcon())
            ->setColor($xaction->getColor())
            ->setHideCommentOptions($this->getHideCommentOptions())
            ->setIsSilent($xaction->getIsSilentTransaction())
            ->setIsMFA($xaction->getIsMFATransaction());

        list($token, $token_removed) = $xaction->getToken();
        if ($token) {
            $event->setToken($token, $token_removed);
        }

        if (!$this->shouldSuppressTitle($xaction, $group)) {
            if ($this->renderAsFeed) {
                $title = $xaction->getTitleForFeed();
            } else {
                $title = $xaction->getTitle();
            }
            if ($xaction->hasChangeDetails()) {
                if (!$this->isPreview) {
                    $details = $this->buildChangeDetailsLink($xaction);
                    $title = array(
                        $title,
                        ' ',
                        $details,
                    );
                }
            }

            if (!$this->isPreview) {
                $more = $this->buildExtraInformationLink($xaction);
                if ($more) {
                    $title = array($title, ' ', $more);
                }
            }

            $event->setTitle($title);
        }

        if ($this->isPreview) {
            $event->setIsPreview(true);
        } else {
            $event
                ->setDateCreated($xaction->created_at)
                ->setContentSource($xaction->getContentSource())
                ->setAnchor($xaction->getID());
        }

        $transaction_type = $xaction->getTransactionType();
        $comment_type = PhabricatorTransactions::TYPE_COMMENT;
        $is_normal_comment = ($transaction_type == $comment_type);

        if ($this->getShowEditActions() &&
            !$this->isPreview &&
            $is_normal_comment) {

            $has_deleted_comment =
                $xaction->getComment() &&
                $xaction->getComment()->getIsDeleted();

            $has_removed_comment =
                $xaction->getComment() &&
                $xaction->getComment()->getIsRemoved();

            if ($xaction->getCommentVersion() > 1 && !$has_removed_comment) {
                $event->setIsEdited(true);
            }

            if (!$has_removed_comment) {
                $event->setIsNormalComment(true);
            }

            // If we have a place for quoted text to go and this is a quotable
            // comment, pass the quote target ID to the event view.
            if ($this->getQuoteTargetID()) {
                if ($xaction->hasComment()) {
                    if (!$has_removed_comment && !$has_deleted_comment) {
                        $event->setQuoteTargetID($this->getQuoteTargetID());
                        $event->setQuoteRef($this->getQuoteRef());
                    }
                }
            }

            $can_edit = PhabricatorPolicyCapability::CAN_EDIT;

            if ($xaction->hasComment() || $has_deleted_comment) {
                $has_edit_capability = PhabricatorPolicyFilter::hasCapability(
                    $viewer,
                    $xaction,
                    $can_edit);
                if ($has_edit_capability && !$has_removed_comment) {
                    $event->setIsEditable(true);
                }
                if ($has_edit_capability || $viewer->getIsAdmin()) {
                    if (!$has_removed_comment) {
                        $event->setIsRemovable(true);
                    }
                }
            }
        }

        $comment = $this->renderTransactionContent($xaction);
        if ($comment) {
            $event->appendChild($comment);
        }

        return $event;
    }

    /**
     * @param PhabricatorApplicationTransaction $xaction
     * @param array $group
     * @return bool
     * @author 陈妙威
     */
    private function shouldSuppressTitle(
        PhabricatorApplicationTransaction $xaction,
        array $group)
    {

        // This is a little hard-coded, but we don't have any other reasonable
        // cases for now. Suppress "commented on" if there are other actions in
        // the display group.

        if (count($group) > 1) {
            $type_comment = PhabricatorTransactions::TYPE_COMMENT;
            if ($xaction->getTransactionType() == $type_comment) {
                return true;
            }
        }

        return false;
    }

}
