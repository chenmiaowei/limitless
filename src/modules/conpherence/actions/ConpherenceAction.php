<?php

namespace orangins\modules\conpherence\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIIconCircleView;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\lib\view\phui\PHUIListView;
use orangins\lib\view\phui\PHUITagView;
use orangins\modules\conpherence\models\ConpherenceThread;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\settings\setting\PhabricatorConpherenceWidgetVisibleSetting;
use yii\helpers\Url;

/**
 * Class ConpherenceAction
 * @package orangins\modules\conpherence\actions
 * @author 陈妙威
 */
abstract class ConpherenceAction extends PhabricatorAction
{

    /**
     * @var
     */
    private $conpherence;

    /**
     * @param ConpherenceThread $conpherence
     * @return $this
     * @author 陈妙威
     */
    public function setConpherence(ConpherenceThread $conpherence)
    {
        $this->conpherence = $conpherence;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getConpherence()
    {
        return $this->conpherence;
    }

    /**
     * @return null|PHUIListView
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        $nav = new PHUIListView();
        $conpherence = $this->conpherence;

        // Local Links
        if ($conpherence) {
            $nav->addMenuItem(
                (new PHUIListItemView())
                    ->setName(\Yii::t("app", 'Joined Rooms'))
                    ->setType(PHUIListItemView::TYPE_LINK)
                    ->setHref($this->getApplicationURI()));

            $nav->addMenuItem(
                (new PHUIListItemView())
                    ->setName(\Yii::t("app", 'Edit Room'))
                    ->setType(PHUIListItemView::TYPE_LINK)
                    ->setHref(
                        $this->getApplicationURI('update/' . $conpherence->getID()) . '/')
                    ->setWorkflow(true));

            $nav->addMenuItem(
                (new PHUIListItemView())
                    ->setName(\Yii::t("app", 'Add Participants'))
                    ->setType(PHUIListItemView::TYPE_LINK)
                    ->setHref('#')
                    ->addSigil('conpherence-widget-adder')
                    ->setMetadata(array('widget' => 'widgets-people')));
        }

        // Global Links
        $nav->newLabel(\Yii::t("app", 'Conpherence'));
        $nav->newLink(
            \Yii::t("app", 'New Room'),
            $this->getApplicationURI('new/'));
        $nav->newLink(
            \Yii::t("app", 'Search Rooms'),
            $this->getApplicationURI('search/'));

        return $nav;
    }

    /**
     * @param ConpherenceThread $conpherence
     * @return null
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function buildHeaderPaneContent(
        ConpherenceThread $conpherence)
    {
        $viewer = $this->getViewer();
        $header = null;
        $id = $conpherence->getID();

        if ($id) {
            $data = $conpherence->getDisplayData($this->getViewer());

            $header = (new PHUIHeaderView())
                ->setViewer($viewer)
                ->setHeader($data['title'])
                ->setPolicyObject($conpherence)
                ->setImage($data['image']);

            if (strlen($data['topic'])) {
                $topic = (new PHUITagView())
                    ->setName($data['topic'])
                    ->setColor(PHUITagView::COLOR_VIOLET)
                    ->setType(PHUITagView::TYPE_SHADE)
                    ->addClass('conpherence-header-topic');
                $header->addTag($topic);
            }

            $can_edit = PhabricatorPolicyFilter::hasCapability(
                $viewer,
                $conpherence,
                PhabricatorPolicyCapability::CAN_EDIT);

            if ($can_edit) {
                $header->setImageURL(
                    $this->getApplicationURI("picture/{$id}/"));
            }

            $participating = $conpherence->getParticipantIfExists($viewer->getPHID());

            $header->addActionItem(
                (new PHUIIconCircleView())
                    ->setHref(
                        $this->getApplicationURI('edit/' . $conpherence->getID()) . '/')
                    ->setIcon('fa-pencil')
                    ->addClass('hide-on-device')
                    ->setColor('violet')
                    ->setWorkflow(true));

            $header->addActionItem(
                (new PHUIIconCircleView())
                    ->setHref($this->getApplicationURI("preferences/{$id}/"))
                    ->setIcon('fa-gear')
                    ->addClass('hide-on-device')
                    ->setColor('pink')
                    ->setWorkflow(true));

            $widget_key = PhabricatorConpherenceWidgetVisibleSetting::SETTINGKEY;
            $widget_view = (bool)$viewer->getUserSetting($widget_key, false);

            Javelin::initBehavior(
                'toggle-widget',
                array(
                    'show' => (int)$widget_view,
                    'settingsURI' => Url::to(['/settings/index/adjust', 'key' => $widget_key]),
                ));

            $header->addActionItem(
                (new PHUIIconCircleView())
                    ->addSigil('conpherence-widget-toggle')
                    ->setIcon('fa-group')
                    ->setHref('#')
                    ->addClass('conpherence-participant-toggle'));

            Javelin::initBehavior('conpherence-search');

            $header->addActionItem(
                (new PHUIIconCircleView())
                    ->addSigil('conpherence-search-toggle')
                    ->setIcon('fa-search')
                    ->setHref('#')
                    ->setColor('green')
                    ->addClass('conpherence-search-toggle'));

            if (!$participating) {
                $action = ConpherenceUpdateActions::JOIN_ROOM;
                $uri = $this->getApplicationURI("update/{$id}/");
                $button = phutil_tag(
                    'button',
                    array(
                        'type' => 'SUBMIT',
                        'class' => 'button button-green mlr',
                    ),
                    \Yii::t("app", 'Join Room'));

                $hidden = phutil_tag(
                    'input',
                    array(
                        'type' => 'hidden',
                        'name' => 'action',
                        'value' => ConpherenceUpdateActions::JOIN_ROOM,
                    ));

                $form = phabricator_form(
                    $viewer,
                    array(
                        'method' => 'POST',
                        'action' => (string)$uri,
                    ),
                    array(
                        $hidden,
                        $button,
                    ));
                $header->addActionItem($form);
            }
        }

        return $header;
    }

    /**
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSearchForm()
    {
        $viewer = $this->getViewer();
        $conpherence = $this->conpherence;
        $name = $conpherence->getTitle();

        $bar = javelin_tag(
            'input',
            array(
                'type' => 'text',
                'id' => 'conpherence-search-input',
                'name' => 'fulltext',
                'class' => 'conpherence-search-input',
                'sigil' => 'conpherence-search-input',
                'placeholder' => \Yii::t("app", 'Search {0}...', [
                    $name
                ]),
            ));

        $id = $conpherence->getID();
        $form = phabricator_form(
            $viewer,
            array(
                'method' => 'POST',
                'action' => '/conpherence/threadsearch/' . $id . '/',
                'sigil' => 'conpherence-search-form',
                'class' => 'conpherence-search-form',
                'id' => 'conpherence-search-form',
            ),
            array(
                $bar,
            ));

        $form_view = phutil_tag(
            'div',
            array(
                'class' => 'conpherence-search-form-view',
            ),
            $form);

        $results = phutil_tag(
            'div',
            array(
                'id' => 'conpherence-search-results',
                'class' => 'conpherence-search-results',
            ));

        $view = phutil_tag(
            'div',
            array(
                'class' => 'conpherence-search-window',
            ),
            array(
                $form_view,
                $results,
            ));

        return $view;
    }

}
