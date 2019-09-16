<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\view\AphrontView;
use orangins\modules\widgets\javelin\JavelinHoverCardAsset;

/**
 * Class PHUIFeedStoryView
 * @package orangins\lib\view\phui
 * @author 陈妙威
 */
final class PHUIFeedStoryView extends AphrontView
{

    /**
     * @var
     */
    private $title;
    /**
     * @var
     */
    private $image;
    /**
     * @var
     */
    private $imageHref;
    /**
     * @var
     */
    private $appIcon;
    /**
     * @var
     */
    private $phid;
    /**
     * @var
     */
    private $epoch;
    /**
     * @var
     */
    private $viewed;
    /**
     * @var
     */
    private $href;
    /**
     * @var null
     */
    private $pontification = null;
    /**
     * @var array
     */
    private $tokenBar = array();
    /**
     * @var array
     */
    private $projects = array();
    /**
     * @var array
     */
    private $actions = array();
    /**
     * @var
     */
    private $chronologicalKey;
    /**
     * @var
     */
    private $tags;
    /**
     * @var
     */
    private $authorIcon;
    /**
     * @var bool
     */
    private $showTimestamp = true;

    /**
     * @param $tags
     * @return $this
     * @author 陈妙威
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param $chronological_key
     * @return $this
     * @author 陈妙威
     */
    public function setChronologicalKey($chronological_key)
    {
        $this->chronologicalKey = $chronological_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getChronologicalKey()
    {
        return $this->chronologicalKey;
    }

    /**
     * @param $title
     * @return $this
     * @author 陈妙威
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $epoch
     * @return $this
     * @author 陈妙威
     */
    public function setEpoch($epoch)
    {
        $this->epoch = $epoch;
        return $this;
    }

    /**
     * @param $image
     * @return $this
     * @author 陈妙威
     */
    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param $image_href
     * @return $this
     * @author 陈妙威
     */
    public function setImageHref($image_href)
    {
        $this->imageHref = $image_href;
        return $this;
    }

    /**
     * @param $icon
     * @return $this
     * @author 陈妙威
     */
    public function setAppIcon($icon)
    {
        $this->appIcon = $icon;
        return $this;
    }

    /**
     * @param $viewed
     * @return $this
     * @author 陈妙威
     */
    public function setViewed($viewed)
    {
        $this->viewed = $viewed;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewed()
    {
        return $this->viewed;
    }

    /**
     * @param $href
     * @return $this
     * @author 陈妙威
     */
    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }

    /**
     * @param $author_icon
     * @return $this
     * @author 陈妙威
     */
    public function setAuthorIcon($author_icon)
    {
        $this->authorIcon = $author_icon;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAuthorIcon()
    {
        return $this->authorIcon;
    }

    /**
     * @param array $tokens
     * @return $this
     * @author 陈妙威
     */
    public function setTokenBar(array $tokens)
    {
        $this->tokenBar = $tokens;
        return $this;
    }

    /**
     * @param $show_timestamp
     * @return $this
     * @author 陈妙威
     */
    public function setShowTimestamp($show_timestamp)
    {
        $this->showTimestamp = $show_timestamp;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getShowTimestamp()
    {
        return $this->showTimestamp;
    }

    /**
     * @param $project
     * @return $this
     * @author 陈妙威
     */
    public function addProject($project)
    {
        $this->projects[] = $project;
        return $this;
    }

    /**
     * @param PHUIIconView $action
     * @return $this
     * @author 陈妙威
     */
    public function addAction(PHUIIconView $action)
    {
        $this->actions[] = $action;
        return $this;
    }

    /**
     * @param $text
     * @param null $title
     * @return $this
     * @throws \Exception
     * @author 陈妙威
     */
    public function setPontification($text, $title = null)
    {
        if ($title) {
            $title = JavelinHtml::phutil_tag('h3', array(), $title);
        }
        $copy = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-feed-story-bigtext-post',
            ),
            array(
                $title,
                $text,
            ));
        $this->appendChild($copy);
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * @param $user
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    public function renderNotification($user)
    {
        $classes = array(
            'phabricator-notification',
        );

        if (!$this->viewed) {
            $classes[] = 'phabricator-notification-unread';
        }

        if ($this->getShowTimestamp()) {
            if ($this->epoch) {
                if ($user) {
                    $marker = (new PHUIIconView())
                        ->setIcon('fa-circle')
                        ->addClass('phabricator-notification-status');
                    $date = OranginsViewUtil::phabricator_datetime($this->epoch, $user);
                    $foot = JavelinHtml::phutil_tag(
                        'span',
                        array(
                            'class' => 'phabricator-notification-date',
                        ),
                        $date);
                    $foot = JavelinHtml::phutil_tag(
                        'div',
                        array(
                            'class' => 'phabricator-notification-foot',
                        ),
                        array(
                            $marker,
                            $date,
                        ));
                } else {
                    $foot = null;
                }
            } else {
                $foot = \Yii::t("app", 'No time specified.');
            }
        } else {
            $foot = null;
        }

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => implode(' ', $classes),
                'sigil' => 'notification',
                'meta' => array(
                    'href' => $this->getHref(),
                ),
            ),
            array($this->title, $foot));
    }

    /**
     * @return mixed|PHUIBoxView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {

//        require_celerity_resource('phui-feed-story-css');
        JavelinHtml::initBehavior(new JavelinHoverCardAsset());

        $body = null;
        $foot = null;

        $actor = new PHUIIconView();
        $actor->addClass('phui-feed-story-actor');

        $author_icon = $this->getAuthorIcon();

        if ($this->image) {
            $actor->addClass('phui-feed-story-actor-image');
            $actor->setImage($this->image);
        } else if ($author_icon) {
            $actor->addClass('phui-feed-story-actor-icon');
            $actor->setIcon($author_icon);
        }

        if ($this->imageHref) {
            $actor->setHref($this->imageHref);
        }

        if ($this->epoch) {
            // TODO: This is really bad; when rendering through Conduit and via
            // renderText() we don't have a user.
            if ($this->hasViewer()) {
                $foot = OranginsViewUtil::phabricator_datetime($this->epoch, $this->getViewer());
            } else {
                $foot = null;
            }
        } else {
            $foot = \Yii::t("app", 'No time specified.');
        }

        if ($this->chronologicalKey) {
            $foot = JavelinHtml::phutil_tag(
                'a',
                array(
                    'href' => '/feed/' . $this->chronologicalKey . '/',
                ),
                $foot);
        }

        $icon = null;
        if ($this->appIcon) {
            $icon = (new PHUIIconView())
                ->addClass('mr-2')
                ->setIcon($this->appIcon);
        }

        $action_list = array();
        $icons = null;
        foreach ($this->actions as $action) {
            $action_list[] = JavelinHtml::phutil_tag(
                'li',
                array(
                    'class' => 'phui-feed-story-action-item',
                ),
                $action);
        }
        if (!empty($action_list)) {
            $icons = JavelinHtml::phutil_tag(
                'ul',
                array(
                    'class' => 'phui-feed-story-action-list',
                ),
                $action_list);
        }

        $head = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-feed-story-head',
            ),
            array(
                $actor,
                nonempty($this->title, \Yii::t("app", 'Untitled Story')),
                $icons,
            ));

        if (!empty($this->tokenBar)) {
            $tokenview = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-feed-token-bar',
                ),
                $this->tokenBar);
            $this->appendChild($tokenview);
        }

        $body_content = $this->renderChildren();
        if ($body_content) {
            $body = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phui-feed-story-body phabricator-remarkup',
                ),
                $body_content);
        }

        $tags = null;
        if ($this->tags) {
            $tags = array(
                " \xC2\xB7 ",
                $this->tags,
            );
        }

        $foot = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-feed-story-foot',
            ),
            array(
                $icon,
                $foot,
                $tags,
            ));

        $classes = array('phui-feed-story');

        return (new PHUIBoxView())
            ->addClass(implode(' ', $classes))
            ->setBorder(true)
            ->addMargin(PHUI::MARGIN_MEDIUM_BOTTOM)
            ->appendChild(array($head, $body, $foot));
    }

}
