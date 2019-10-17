<?php

namespace orangins\lib\view\phui;

use Exception;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;
use orangins\lib\view\control\AphrontCursorPagerView;
use orangins\modules\transactions\assets\JavelinShowOrderTransactionAsset;
use PhutilInvalidStateException;
use ReflectionException;
use Yii;

/**
 * Class PHUITimelineView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUITimelineView extends AphrontView
{

    /**
     * @var array
     */
    private $events = array();
    /**
     * @var
     */
    private $id;
    /**
     * @var bool
     */
    private $shouldTerminate = false;
    /**
     * @var bool
     */
    private $shouldAddSpacers = true;
    /**
     * @var
     */
    private $pager;
    /**
     * @var array
     */
    private $renderData = array();
    /**
     * @var
     */
    private $quoteTargetID;
    /**
     * @var
     */
    private $quoteRef;

    /**
     * @param $id
     * @return $this
     * @author 陈妙威
     */
    public function setID($id)
    {
        $this->id = $id;
        return $this;
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
     * @param $bool
     * @return $this
     * @author 陈妙威
     */
    public function setShouldAddSpacers($bool)
    {
        $this->shouldAddSpacers = $bool;
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
     * @return AphrontCursorPagerView
     * @author 陈妙威
     */
    public function getPager()
    {
        return $this->pager;
    }

    /**
     * @param PHUITimelineEventView $event
     * @return $this
     * @author 陈妙威
     */
    public function addEvent(PHUITimelineEventView $event)
    {
        $this->events[] = $event;
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     * @author 陈妙威
     */
    public function setRenderData(array $data)
    {
        $this->renderData = $data;
        return $this;
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
     * @return mixed|string
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    public function render()
    {
        if ($this->getPager()) {
            if ($this->id === null) {
                $this->id = JavelinHtml::generateUniqueNodeId();
            }
            JavelinHtml::initBehavior(
                new JavelinShowOrderTransactionAsset(),
                array(
                    'timelineID' => $this->id,
                    'renderData' => $this->renderData,
                ));
        }
        $events = $this->buildEvents();

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-timeline-view',
                'id' => $this->id,
            ),
            array(
                JavelinHtml::phutil_tag(
                    'h3',
                    array(
                        'class' => 'aural-only',
                    ),
                    Yii::t("app", 'Event Timeline')),
                $events,
            ));
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     * @throws PhutilInvalidStateException
     * @throws Exception
     * @author 陈妙威
     */
    public function buildEvents()
    {
        $spacer = self::renderSpacer();

        // Track why we're hiding older results.
        $hide_reason = null;

        $hide = array();
        $show = array();

        // Bucket timeline events into events we'll hide by default (because they
        // predate your most recent interaction with the object) and events we'll
        // show by default.
        foreach ($this->events as $event) {
            if ($event->getHideByDefault()) {
                $hide[] = $event;
            } else {
                $show[] = $event;
            }
        }

        // If you've never interacted with the object, all the events will be shown
        // by default. We may still need to paginate if there are a large number
        // of events.
        $more = (bool)$hide;

        if ($more) {
            $hide_reason = 'comment';
        }

        if ($this->getPager()) {
            if ($this->getPager()->getHasMoreResults()) {
                if (!$more) {
                    $hide_reason = 'limit';
                }
                $more = true;
            }
        }

        $events = array();
        if ($more && $this->getPager()) {
            switch ($hide_reason) {
                case 'comment':
                    $hide_help = Yii::t("app",
                        'Changes from before your most recent comment are hidden.');
                    break;
                case 'limit':
                default:
                    $hide_help = Yii::t("app",
                        'There are a very large number of changes, so older changes are ' .
                        'hidden.');
                    break;
            }

            $uri = $this->getPager()->getNextPageURI();
            $uri->setQueryParam('quoteTargetID', $this->getQuoteTargetID());
            $uri->setQueryParam('quoteRef', $this->getQuoteRef());
            $events[] = JavelinHtml::phutil_tag(
                'div',
                array(
                    'sigil' => 'show-older-block',
                    'class' => 'phui-timeline-older-transactions-are-hidden',
                ),
                array(
                    $hide_help,
                    ' ',
                    JavelinHtml::phutil_tag(
                        'a',
                        array(
                            'href' => (string)$uri,
                            'mustcapture' => true,
                            'sigil' => 'show-older-link',
                        ),
                        Yii::t("app", 'Show Older Changes')),
                ));

            if ($show) {
                $events[] = $spacer;
            }
        }

        if ($show) {
            $events[] = JavelinHtml::phutil_implode_html($spacer, $show);
        }

        if ($events) {
            if ($this->shouldAddSpacers) {
                $events = array($spacer, $events, $spacer);
            }
        } else {
            $events = array($spacer);
        }

        if ($this->shouldTerminate) {
            $events[] = self::renderEnder();
        }

        return $events;
    }

    /**
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public static function renderSpacer()
    {
        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-timeline-event-view ' .
                    'phui-timeline-spacer',
            ),
            '');
    }

    /**
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public static function renderEnder()
    {
        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-timeline-event-view ' .
                    'the-worlds-end',
            ),
            '');
    }
}
