<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\infrastructure\contentsource\PhabricatorContentSourceView;
use orangins\modules\widgets\javelin\JavelinPHUIDropdownBehaviorAsset;
use PhutilSafeHTML;
use orangins\lib\view\AphrontView;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\layout\PhabricatorAnchorView;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;
use orangins\modules\widgets\javelin\JavelinWatchAnchorAsset;

/**
 * Class PHUITimelineEventView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUITimelineEventView extends AphrontView
{

    /**
     *
     */
    const DELIMITER = " \xC2\xB7 ";

    /**
     * @var
     */
    private $userHandle;
    /**
     * @var
     */
    private $title;
    /**
     * @var
     */
    private $icon;
    /**
     * @var
     */
    private $color;
    /**
     * @var array
     */
    private $classes = array();
    /**
     * @var
     */
    private $contentSource;
    /**
     * @var
     */
    private $dateCreated;
    /**
     * @var
     */
    private $anchor;
    /**
     * @var
     */
    private $isEditable;
    /**
     * @var
     */
    private $isEdited;
    /**
     * @var
     */
    private $isRemovable;
    /**
     * @var
     */
    private $transactionPHID;
    /**
     * @var
     */
    private $isPreview;
    /**
     * @var array
     */
    private $eventGroup = array();
    /**
     * @var
     */
    private $hideByDefault;
    /**
     * @var
     */
    private $token;
    /**
     * @var
     */
    private $tokenRemoved;
    /**
     * @var
     */
    private $quoteTargetID;
    /**
     * @var
     */
    private $isNormalComment;
    /**
     * @var
     */
    private $quoteRef;
    /**
     * @var
     */
    private $reallyMajorEvent;
    /**
     * @var bool
     */
    private $hideCommentOptions = false;
    /**
     * @var
     */
    private $authorPHID;
    /**
     * @var array
     */
    private $badges = array();
    /**
     * @var array
     */
    private $pinboardItems = array();
    /**
     * @var
     */
    private $isSilent;
    /**
     * @var
     */
    private $isMFA;

    /**
     * @param $author_phid
     * @return $this
     * @author 陈妙威
     */
    public function setAuthorPHID($author_phid)
    {
        $this->authorPHID = $author_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAuthorPHID()
    {
        return $this->authorPHID;
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
     * @param $is_normal_comment
     * @return $this
     * @author 陈妙威
     */
    public function setIsNormalComment($is_normal_comment)
    {
        $this->isNormalComment = $is_normal_comment;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsNormalComment()
    {
        return $this->isNormalComment;
    }

    /**
     * @param $hide_by_default
     * @return $this
     * @author 陈妙威
     */
    public function setHideByDefault($hide_by_default)
    {
        $this->hideByDefault = $hide_by_default;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHideByDefault()
    {
        return $this->hideByDefault;
    }

    /**
     * @param $transaction_phid
     * @return $this
     * @author 陈妙威
     */
    public function setTransactionPHID($transaction_phid)
    {
        $this->transactionPHID = $transaction_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTransactionPHID()
    {
        return $this->transactionPHID;
    }

    /**
     * @param $is_edited
     * @return $this
     * @author 陈妙威
     */
    public function setIsEdited($is_edited)
    {
        $this->isEdited = $is_edited;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsEdited()
    {
        return $this->isEdited;
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
     * @param $is_editable
     * @return $this
     * @author 陈妙威
     */
    public function setIsEditable($is_editable)
    {
        $this->isEditable = $is_editable;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsEditable()
    {
        return $this->isEditable;
    }

    /**
     * @param $is_removable
     * @return $this
     * @author 陈妙威
     */
    public function setIsRemovable($is_removable)
    {
        $this->isRemovable = $is_removable;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsRemovable()
    {
        return $this->isRemovable;
    }

    /**
     * @param $date_created
     * @return $this
     * @author 陈妙威
     */
    public function setDateCreated($date_created)
    {
        $this->dateCreated = $date_created;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCreatedAt()
    {
        return $this->dateCreated;
    }

    /**
     * @param PhabricatorContentSource $content_source
     * @return $this
     * @author 陈妙威
     */
    public function setContentSource(PhabricatorContentSource $content_source)
    {
        $this->contentSource = $content_source;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getContentSource()
    {
        return $this->contentSource;
    }

    /**
     * @param PhabricatorObjectHandle $handle
     * @return $this
     * @author 陈妙威
     */
    public function setUserHandle(PhabricatorObjectHandle $handle)
    {
        $this->userHandle = $handle;
        return $this;
    }

    /**
     * @param $anchor
     * @return $this
     * @author 陈妙威
     */
    public function setAnchor($anchor)
    {
        $this->anchor = $anchor;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAnchor()
    {
        return $this->anchor;
    }

    /**
     * @param $title
     * @return $this
     * @author 陈妙威
     */
    public function setTitle($title)
    {
        $this->title = new PhutilSafeHTML($title);
        return $this;
    }

    /**
     * @param $class
     * @return $this
     * @author 陈妙威
     */
    public function addClass($class)
    {
        $this->classes[] = $class;
        return $this;
    }

    /**
     * @param PHUIBadgeMiniView $badge
     * @return $this
     * @author 陈妙威
     */
    public function addBadge(PHUIBadgeMiniView $badge)
    {
        $this->badges[] = $badge;
        return $this;
    }

    /**
     * @param $icon
     * @return $this
     * @author 陈妙威
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @param $color
     * @return $this
     * @author 陈妙威
     */
    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @param $is_silent
     * @return $this
     * @author 陈妙威
     */
    public function setIsSilent($is_silent)
    {
        $this->isSilent = $is_silent;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsSilent()
    {
        return $this->isSilent;
    }

    /**
     * @param $is_mfa
     * @return $this
     * @author 陈妙威
     */
    public function setIsMFA($is_mfa)
    {
        $this->isMFA = $is_mfa;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsMFA()
    {
        return $this->isMFA;
    }

    /**
     * @param $me
     * @return $this
     * @author 陈妙威
     */
    public function setReallyMajorEvent($me)
    {
        $this->reallyMajorEvent = $me;
        return $this;
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
     * @param PHUIPinboardItemView $item
     * @return $this
     * @author 陈妙威
     */
    public function addPinboardItem(PHUIPinboardItemView $item)
    {
        $this->pinboardItems[] = $item;
        return $this;
    }

    /**
     * @param $token
     * @param bool $removed
     * @return $this
     * @author 陈妙威
     */
    public function setToken($token, $removed = false)
    {
        $this->token = $token;
        $this->tokenRemoved = $removed;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getEventGroup()
    {
        return array_merge(array($this), $this->eventGroup);
    }

    /**
     * @param PHUITimelineEventView $event
     * @return $this
     * @author 陈妙威
     */
    public function addEventToGroup(PHUITimelineEventView $event)
    {
        $this->eventGroup[] = $event;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function shouldRenderEventTitle()
    {
        if ($this->title === null) {
            return false;
        }

        return true;
    }

    /**
     * @param $force_icon
     * @param $has_menu
     * @param $extra
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderEventTitle($force_icon, $has_menu, $extra)
    {
        $title = $this->title;

        $title_classes = array();
        $title_classes[] = 'phui-timeline-title';

        $icon = null;
        if ($this->icon || $force_icon) {
            $title_classes[] = 'phui-timeline-title-with-icon';
        }

        if ($has_menu) {
            $title_classes[] = 'phui-timeline-title-with-menu';
        }

        if ($this->icon) {
            $fill_classes = array();
            $fill_classes[] = 'phui-timeline-icon-fill';
            if ($this->color) {
                $fill_classes[] = 'fill-has-color';
                $fill_classes[] = 'phui-timeline-icon-fill-' . $this->color;
            }

            $icon = (new PHUIIconView())
                ->setIcon($this->icon)
                ->addClass('phui-timeline-icon');

            $icon = JavelinHtml::phutil_tag(
                'span',
                array(
                    'class' => implode(' ', $fill_classes),
                ),
                $icon);
        }

        $token = null;
        if ($this->token) {
            $token = (new PHUIIconView())
                ->addClass('phui-timeline-token')
                ->setSpriteSheet(PHUIIconView::SPRITE_TOKENS)
                ->setSpriteIcon($this->token);
            if ($this->tokenRemoved) {
                $token->addClass('strikethrough');
            }
        }

        $title = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => implode(' ', $title_classes),
            ),
            array($icon, $token, $title, $extra));

        return $title;
    }

    /**
     * @return array|mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception*@throws \Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {

        $events = $this->getEventGroup();

        // Move events with icons first.
        $icon_keys = array();
        foreach ($this->getEventGroup() as $key => $event) {
            if ($event->icon) {
                $icon_keys[] = $key;
            }
        }
        $events = OranginsUtil::array_select_keys($events, $icon_keys) + $events;
        $force_icon = (bool)$icon_keys;

        $menu = null;
        $items = array();
        if (!$this->getIsPreview() && !$this->getHideCommentOptions()) {
            foreach ($this->getEventGroup() as $event) {
                $items[] = $event->getMenuItems($this->anchor);
            }
            $items = OranginsUtil::array_mergev($items);
        }

        if ($items) {
            $icon = (new PHUIIconView())
                ->setIcon('fa-caret-down');
            $aural = JavelinHtml::phutil_tag(
                'span',
                array(
                    'aural' => true,
                ),
                \Yii::t("app", 'Comment Actions'));

            if ($items) {
                $sigil = 'phui-dropdown-menu';
                JavelinHtml::initBehavior(new JavelinPHUIDropdownBehaviorAsset());
            } else {
                $sigil = null;
            }

            $action_list = (new PhabricatorActionListView())
                ->setUser($this->getUser());
            foreach ($items as $item) {
                $action_list->addAction($item);
            }

            $menu = JavelinHtml::phutil_tag(
                $items ? 'a' : 'span',
                array(
                    'href' => '#',
                    'class' => 'phui-timeline-menu',
                    'sigil' => $sigil,
                    'aria-haspopup' => 'true',
                    'aria-expanded' => 'false',
                    'meta' => $action_list->getDropdownMenuMetadata(),
                ),
                array(
                    $aural,
                    $icon,
                ));

            $has_menu = true;
        } else {
            $has_menu = false;
        }

        // Render "extra" information (timestamp, etc).
        $extra = $this->renderExtra($events);

        $show_badges = false;

        $group_titles = array();
        $group_items = array();
        $group_children = array();
        foreach ($events as $event) {
            if ($event->shouldRenderEventTitle()) {

                // Render the group anchor here, outside the title box. If we render
                // it inside the title box it ends up completely hidden and Chrome 55
                // refuses to jump to it. See T11997 for discussion.

                if ($extra && $this->anchor) {
                    $group_titles[] = (new PhabricatorAnchorView())
                        ->setAnchorName($this->anchor)
                        ->render();
                }

                $group_titles[] = $event->renderEventTitle(
                    $force_icon,
                    $has_menu,
                    $extra);

                // Don't render this information more than once.
                $extra = null;
            }

            if ($event->hasChildren()) {
                $group_children[] = $event->renderChildren();
                $show_badges = true;
            }
        }

        $image_uri = $this->userHandle->getImageURI();

        $wedge = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-timeline-wedge',
                'style' => (OranginsUtil::nonempty($image_uri)) ? '' : 'display: none;',
            ),
            '');

        $image = null;
        $badges = null;
        if ($image_uri) {
            $image = JavelinHtml::phutil_tag(
                ($this->userHandle->getURI()) ? 'a' : 'div',
                array(
                    'style' => 'background-image: url(' . $image_uri . ')',
                    'class' => 'phui-timeline-image visual-only',
                    'href' => $this->userHandle->getURI(),
                ),
                '');
            if ($this->badges && $show_badges) {
                $flex = new PHUIBadgeBoxView();
                $flex->addItems($this->badges);
                $flex->setCollapsed(true);
                $badges = JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'class' => 'phui-timeline-badges',
                    ),
                    $flex);
            }
        }

        $content_classes = array();
        $content_classes[] = 'phui-timeline-content';

        $classes = array();
        $classes[] = 'phui-timeline-event-view';
        if ($group_children) {
            $classes[] = 'phui-timeline-major-event';
            $content = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-timeline-inner-content',
                ),
                array(
                    $group_titles,
                    $menu,
                    JavelinHtml::phutil_tag(
                        'div',
                        array(
                            'class' => 'phui-timeline-core-content',
                        ),
                        $group_children),
                ));
        } else {
            $classes[] = 'phui-timeline-minor-event';
            $content = $group_titles;
        }

        $content = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-timeline-group',
            ),
            $content);

        // Image Events
        $pinboard = null;
        if ($this->pinboardItems) {
            $pinboard = new PHUIPinboardView();
            foreach ($this->pinboardItems as $item) {
                $pinboard->addItem($item);
            }
        }

        $content = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => implode(' ', $content_classes),
            ),
            array($image, $badges, $wedge, $content, $pinboard));

        $outer_classes = $this->classes;
        $outer_classes[] = 'phui-timeline-shell';
        $color = null;
        foreach ($this->getEventGroup() as $event) {
            if ($event->color) {
                $color = $event->color;
                break;
            }
        }

        if ($color) {
            $outer_classes[] = 'phui-timeline-' . $color;
        }

        $sigil = null;
        $meta = null;
        if ($this->getTransactionPHID()) {
            $sigil = 'transaction';
            $meta = array(
                'phid' => $this->getTransactionPHID(),
                'anchor' => $this->anchor,
            );
        }

        $major_event = null;
        if ($this->reallyMajorEvent) {
            $major_event = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-timeline-event-view ' .
                        'phui-timeline-spacer ' .
                        'phui-timeline-spacer-bold',
                    '',
                ));
        }

        return array(
            JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => implode(' ', $outer_classes),
                    'id' => $this->anchor ? 'anchor-' . $this->anchor : null,
                    'sigil' => $sigil,
                    'meta' => $meta,
                ),
                JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'class' => implode(' ', $classes),
                    ),
                    $content)),
            $major_event,
        );
    }

    /**
     * @param PHUITimelineEventView[] $events
     * @return array|string
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception*@throws \Exception
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderExtra(array $events)
    {
        $extra = array();

        if ($this->getIsPreview()) {
            $extra[] = \Yii::t("app", 'PREVIEW');
        } else {
            foreach ($events as $event) {
                if ($event->getIsEdited()) {
                    $extra[] = \Yii::t("app", 'Edited');
                    break;
                }
            }

            $source = $this->getContentSource();
            $content_source = null;
            if ($source) {
                $content_source = (new PhabricatorContentSourceView())
                    ->setContentSource($source)
                    ->setUser($this->getUser());
                $content_source = \Yii::t("app", 'Via {0}', [
                    $content_source->getSourceName()
                ]);
            }

            $date_created = null;
            foreach ($events as $event) {
                if ($event->getCreatedAt()) {
                    if ($date_created === null) {
                        $date_created = $event->getCreatedAt();
                    } else {
                        $date_created = min($event->getCreatedAt(), $date_created);
                    }
                }
            }

            if ($date_created) {
                $date = OranginsViewUtil::phabricator_datetime(
                    $date_created,
                    $this->getUser());
                if ($this->anchor) {
                    JavelinHtml::initBehavior(new JavelinWatchAnchorAsset());
                    JavelinHtml::initBehavior(new JavelinTooltipAsset());

                    $date = array(
                        JavelinHtml::phutil_tag(
                            'a',
                            array(
                                'href' => '#' . $this->anchor,
                                'sigil' => 'has-tooltip',
                                'meta' => array(
                                    'tip' => $content_source,
                                ),
                            ),
                            $date),
                    );
                }
                $extra[] = $date;
            }

            // If this edit was applied silently, give user a hint that they should
            // not expect to have received any mail or notifications.
            if ($this->getIsSilent()) {
                $extra[] = (new PHUIIconView())
                    ->setIcon('fa-bell-slash', 'red')
                    ->setTooltip(\Yii::t("app", 'Silent Edit'));
            }

            // If this edit was applied while the actor was in high-security mode,
            // provide a hint that it was extra authentic.
            if ($this->getIsMFA()) {
                $extra[] = (new PHUIIconView())
                    ->setIcon('fa-vcard', 'green')
                    ->setTooltip(\Yii::t("app", 'MFA Authenticated'));
            }
        }

        $extra = JavelinHtml::phutil_tag(
            'span',
            array(
                'class' => 'phui-timeline-extra',
            ),
            JavelinHtml::phutil_implode_html(
                JavelinHtml::phutil_tag(
                    'span',
                    array(
                        'aural' => false,
                    ),
                    self::DELIMITER),
                $extra));

        return $extra;
    }

    /**
     * @param $anchor
     * @return array
     * @author 陈妙威
     */
    private function getMenuItems($anchor)
    {
        $xaction_phid = $this->getTransactionPHID();

        $items = array();

        if ($this->getIsEditable()) {
            $items[] = (new PhabricatorActionView())
                ->setIcon('fa-pencil')
                ->setHref('/transactions/edit/' . $xaction_phid . '/')
                ->setName(\Yii::t("app", 'Edit Comment'))
                ->addSigil('transaction-edit')
                ->setMetadata(
                    array(
                        'anchor' => $anchor,
                    ));
        }

        if ($this->getQuoteTargetID()) {
            $ref = null;
            if ($this->getQuoteRef()) {
                $ref = $this->getQuoteRef();
                if ($anchor) {
                    $ref = $ref . '#' . $anchor;
                }
            }

            $items[] = (new PhabricatorActionView())
                ->setIcon('fa-quote-left')
                ->setName(\Yii::t("app", 'Quote Comment'))
                ->setHref('#')
                ->addSigil('transaction-quote')
                ->setMetadata(
                    array(
                        'targetID' => $this->getQuoteTargetID(),
                        'uri' => '/transactions/quote/' . $xaction_phid . '/',
                        'ref' => $ref,
                    ));
        }

        if ($this->getIsNormalComment()) {
            $items[] = (new PhabricatorActionView())
                ->setIcon('fa-code')
                ->setHref('/transactions/raw/' . $xaction_phid . '/')
                ->setName(\Yii::t("app", 'View Remarkup'))
                ->addSigil('transaction-raw')
                ->setMetadata(
                    array(
                        'anchor' => $anchor,
                    ));

            $content_source = $this->getContentSource();
            $source_email = PhabricatorEmailContentSource::SOURCECONST;
            if ($content_source->getSource() == $source_email) {
                $source_id = $content_source->getContentSourceParameter('id');
                if ($source_id) {
                    $items[] = (new PhabricatorActionView())
                        ->setIcon('fa-envelope-o')
                        ->setHref('/transactions/raw/' . $xaction_phid . '/?email')
                        ->setName(\Yii::t("app", 'View Email Body'))
                        ->addSigil('transaction-raw')
                        ->setMetadata(
                            array(
                                'anchor' => $anchor,
                            ));
                }
            }
        }

        if ($this->getIsEdited()) {
            $items[] = (new PhabricatorActionView())
                ->setIcon('fa-list')
                ->setHref('/transactions/history/' . $xaction_phid . '/')
                ->setName(\Yii::t("app", 'View Edit History'))
                ->setWorkflow(true);
        }

        if ($this->getIsRemovable()) {
            $items[] = (new PhabricatorActionView())
                ->setType(PhabricatorActionView::TYPE_DIVIDER);

            $items[] = (new PhabricatorActionView())
                ->setIcon('fa-trash-o')
                ->setHref('/transactions/remove/' . $xaction_phid . '/')
                ->setName(\Yii::t("app", 'Remove Comment'))
                ->setColor(PhabricatorActionView::RED)
                ->addSigil('transaction-remove')
                ->setMetadata(
                    array(
                        'anchor' => $anchor,
                    ));

        }

        return $items;
    }

}
