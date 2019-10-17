<?php

namespace orangins\lib\view\layout;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;

/**
 * Class AphrontListFilterView
 * @package orangins\lib\view\layout
 * @author 陈妙威
 */
final class AphrontListFilterView extends AphrontView
{

    /**
     * @var
     */
    private $showAction;
    /**
     * @var
     */
    private $hideAction;
    /**
     * @var
     */
    private $showHideDescription;
    /**
     * @var
     */
    private $showHideHref;

    /**
     * @param $show
     * @param $hide
     * @param $description
     * @param $href
     * @return $this
     * @author 陈妙威
     */
    public function setCollapsed($show, $hide, $description, $href)
    {
        $this->showAction = $show;
        $this->hideAction = $hide;
        $this->showHideDescription = $description;
        $this->showHideHref = $href;
        return $this;
    }

    /**
     * @return mixed|string|null
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        $content = $this->renderChildren();
        if (!$content) {
            return null;
        }

//    require_celerity_resource('aphront-list-filter-view-css');

        $content = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'aphront-list-filter-view-content',
            ),
            $content);

        $classes = array();
        $classes[] = 'aphront-list-filter-view';
        if ($this->showAction !== null) {
            $classes[] = 'aphront-list-filter-view-collapsible';

            Javelin::initBehavior('phabricator-reveal-content');

            $hide_action_id = JavelinHtml::generateUniqueNodeId();
            $show_action_id = JavelinHtml::generateUniqueNodeId();
            $content_id = JavelinHtml::generateUniqueNodeId();

            $hide_action = JavelinHtml::phutil_tag(
                'a',
                array(
                    'class' => 'button button-grey',
                    'sigil' => 'reveal-content',
                    'id' => $hide_action_id,
                    'href' => $this->showHideHref,
                    'meta' => array(
                        'hideIDs' => array($hide_action_id),
                        'showIDs' => array($content_id, $show_action_id),
                    ),
                ),
                $this->showAction);

            $content_description = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'aphront-list-filter-description',
                ),
                $this->showHideDescription);

            $show_action = JavelinHtml::phutil_tag(
                'a',
                array(
                    'class' => 'button button-grey',
                    'sigil' => 'reveal-content',
                    'style' => 'display: none;',
                    'href' => '#',
                    'id' => $show_action_id,
                    'meta' => array(
                        'hideIDs' => array($content_id, $show_action_id),
                        'showIDs' => array($hide_action_id),
                    ),
                ),
                $this->hideAction);

            $reveal_block = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'aphront-list-filter-reveal',
                ),
                array(
                    $content_description,
                    $hide_action,
                    $show_action,
                ));

            $content = array(
                $reveal_block,
                JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'id' => $content_id,
                        'style' => 'display: none;',
                    ),
                    $content),
            );
        }

        $content = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => implode(' ', $classes),
            ),
            $content);

        return JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'aphront-list-filter-wrap',
            ),
            $content);
    }

}
