<?php

namespace orangins\modules\conpherence\view;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\lib\view\phui\PHUIListView;
use orangins\modules\aphlict\assets\JavelinAphlictDropdownBehaviorAsset;
use orangins\modules\conpherence\assets\JavelinDurableColumnBehaviorAsset;
use orangins\modules\conpherence\models\ConpherenceThread;
use orangins\modules\conpherence\models\ConpherenceTransaction;
use orangins\modules\notification\view\PhabricatorNotificationStatusView;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\settings\setting\PhabricatorConpherenceColumnMinimizeSetting;
use orangins\modules\settings\setting\PhabricatorConpherenceColumnVisibleSetting;
use orangins\modules\widgets\javelin\JavelinDragAndDropTextareaAsset;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;
use yii\helpers\Url;

/**
 * Class ConpherenceDurableColumnView
 * @package orangins\modules\conpherence\view
 * @author 陈妙威
 */
final class ConpherenceDurableColumnView extends AphrontTagView
{

    /**
     * @var array
     */
    private $conpherences = array();
    /**
     * @var
     */
    private $draft;
    /**
     * @var
     */
    private $selectedConpherence;
    /**
     * @var
     */
    private $transactions;
    /**
     * @var
     */
    private $visible;
    /**
     * @var
     */
    private $minimize;
    /**
     * @var bool
     */
    private $initialLoad = false;
    /**
     * @var
     */
    private $policyObjects;
    /**
     * @var array
     */
    private $quicksandConfig = array();

    /**
     * @param array $conpherences
     * @return $this
     * @author 陈妙威
     */
    public function setConpherences(array $conpherences)
    {
        assert_instances_of($conpherences, ConpherenceThread::class);
        $this->conpherences = $conpherences;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getConpherences()
    {
        return $this->conpherences;
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
     * @param ConpherenceThread|null $conpherence
     * @return $this
     * @author 陈妙威
     */
    public function setSelectedConpherence(
        ConpherenceThread $conpherence = null)
    {
        $this->selectedConpherence = $conpherence;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSelectedConpherence()
    {
        return $this->selectedConpherence;
    }

    /**
     * @param array $transactions
     * @return $this
     * @author 陈妙威
     */
    public function setTransactions(array $transactions)
    {
        assert_instances_of($transactions, ConpherenceTransaction::class);
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
     * @param $visible
     * @return $this
     * @author 陈妙威
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * @param $minimize
     * @return $this
     * @author 陈妙威
     */
    public function setMinimize($minimize)
    {
        $this->minimize = $minimize;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMinimize()
    {
        return $this->minimize;
    }

    /**
     * @param $bool
     * @return $this
     * @author 陈妙威
     */
    public function setInitialLoad($bool)
    {
        $this->initialLoad = $bool;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getInitialLoad()
    {
        return $this->initialLoad;
    }

    /**
     * @param array $objects
     * @return $this
     * @author 陈妙威
     */
    public function setPolicyObjects(array $objects)
    {
        assert_instances_of($objects, 'PhabricatorPolicy');

        $this->policyObjects = $objects;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPolicyObjects()
    {
        return $this->policyObjects;
    }

    /**
     * @param array $config
     * @return $this
     * @author 陈妙威
     */
    public function setQuicksandConfig(array $config)
    {
        $this->quicksandConfig = $config;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getQuicksandConfig()
    {
        return $this->quicksandConfig;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        if ($this->getVisible()) {
            $style = null;
        } else {
            $style = 'display: none;';
        }
        $classes = array('conpherence-durable-column');
        if ($this->getInitialLoad()) {
            $classes[] = 'loading';
        }

        return array(
            'id' => 'conpherence-durable-column',
            'class' => implode(' ', $classes),
            'style' => $style,
            'sigil' => 'conpherence-durable-column',
        );
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        $column_key = PhabricatorConpherenceColumnVisibleSetting::SETTINGKEY;
        $minimize_key = PhabricatorConpherenceColumnMinimizeSetting::SETTINGKEY;

        JavelinHtml::initBehavior(
            new JavelinDurableColumnBehaviorAsset(),
            array(
                'visible' => $this->getVisible(),
                'minimize' => $this->getMinimize(),
                'visibleURI' => Url::to(['/settings/index/adjust', 'key' => $column_key]),
                'minimizeURI' => Url::to(['/settings/index/adjust', 'key' => $minimize_key]),
                'quicksandConfig' => $this->getQuicksandConfig(),
            ));

        $policy_objects = ConpherenceThread::loadViewPolicyObjects(
            $this->getUser(),
            $this->getConpherences());
        $this->setPolicyObjects($policy_objects);

        $classes = array();
        $classes[] = 'conpherence-durable-column-header';
        $classes[] = 'grouped';

        $header = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => implode(' ', $classes),
                'data-sigil' => 'conpherence-minimize-window',
            ),
            $this->buildHeader());

        $icon_bar = null;
        if ($this->conpherences) {
            $icon_bar = $this->buildIconBar();
        }
        $icon_bar = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'conpherence-durable-column-icon-bar',
            ),
            $icon_bar);

        $transactions = $this->buildTransactions();

        $content = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'conpherence-durable-column-main',
                'sigil' => 'conpherence-durable-column-main',
            ),
            JavelinHtml::phutil_tag(
                'div',
                array(
                    'id' => 'conpherence-durable-column-content',
                    'class' => 'conpherence-durable-column-frame',
                ),
                JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'class' => 'conpherence-durable-column-transactions',
                        'sigil' => 'conpherence-durable-column-transactions',
                    ),
                    $transactions)));

        $input = $this->buildTextInput();

        return array(
            $header,
            JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'conpherence-durable-column-body',
                    'sigil' => 'conpherence-durable-column-body',
                ),
                array(
                    $icon_bar,
                    $content,
                    $input,
                )),
        );
//        return [];
    }

    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function buildIconBar()
    {
        $icons = array();
        $selected_conpherence = $this->getSelectedConpherence();
        $conpherences = $this->getConpherences();

        foreach ($conpherences as $conpherence) {
            $classes = array('conpherence-durable-column-thread-icon');
            if ($selected_conpherence->getID() == $conpherence->getID()) {
                $classes[] = 'selected';
            }
            $data = $conpherence->getDisplayData($this->getUser());
            $thread_title = JavelinHtml::phutil_tag(
                'span',
                array(),
                array(
                    $data['title'],
                ));
            $image = $data['image'];
            JavelinHtml::initBehavior(new JavelinTooltipAsset());
            $icons[] =
                JavelinHtml::phutil_tag(
                    'a',
                    array(
                        'href' => '/conpherence/columnview/',
                        'class' => implode(' ', $classes),
                        'sigil' => 'conpherence-durable-column-thread-icon has-tooltip',
                        'meta' => array(
                            'threadID' => $conpherence->getID(),
                            'threadTitle' => hsprintf('%s', $thread_title),
                            'tip' => $data['title'],
                            'align' => 'W',
                        ),
                    ),
                    JavelinHtml::phutil_tag(
                        'span',
                        array(
                            'style' => 'background-image: url(' . $image . ')',
                        ),
                        ''));
        }

        return $icons;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function buildHeader()
    {
        $conpherence = $this->getSelectedConpherence();

        $bubble_id = JavelinHtml::generateUniqueNodeId();
        $dropdown_id = JavelinHtml::generateUniqueNodeId();

        $settings_list = new PHUIListView();
        $header_actions = $this->getHeaderActionsConfig($conpherence);
        foreach ($header_actions as $action) {
            $settings_list->addMenuItem(
                (new PHUIListItemView())
                    ->setHref($action['href'])
                    ->setName($action['name'])
                    ->setIcon($action['icon'])
                    ->setDisabled($action['disabled'])
                    ->addSigil('conpherence-durable-column-header-action')
                    ->setMetadata(array(
                        'action' => $action['key'],
                    )));
        }

        $settings_menu = JavelinHtml::phutil_tag(
            'div',
            array(
                'id' => $dropdown_id,
                'class' => 'phabricator-main-menu-dropdown phui-list-sidenav ' .
                    'conpherence-settings-dropdown',
                'sigil' => 'phabricator-notification-menu',
                'style' => 'display: none',
            ),
            $settings_list);

        JavelinHtml::initBehavior(
            new JavelinAphlictDropdownBehaviorAsset(),
            array(
                'bubbleID' => $bubble_id,
                'dropdownID' => $dropdown_id,
                'local' => true,
                'containerDivID' => 'conpherence-durable-column',
            ));

        $bars = (new PHUIListItemView())
            ->setName(\Yii::t("app", 'Room Actions'))
            ->setIcon('fa-gear')
            ->addClass('core-menu-item')
            ->addClass('conpherence-settings-icon')
            ->addSigil('conpherence-settings-menu')
            ->setID($bubble_id)
            ->setHref('#')
            ->setAural(\Yii::t("app", 'Room Actions'))
            ->setOrder(400);

        $minimize = (new PHUIListItemView())
            ->setName(\Yii::t("app", 'Minimize Window'))
            ->setIcon('fa-toggle-down')
            ->addClass('core-menu-item')
            ->addClass('conpherence-minimize-icon')
            ->addSigil('conpherence-minimize-window')
            ->setHref('#')
            ->setAural(\Yii::t("app", 'Minimize Window'))
            ->setOrder(300);

        $settings_button = (new PHUIListView())
            ->addMenuItem($bars)
            ->addMenuItem($minimize)
            ->addClass('phabricator-application-menu');

        if ($conpherence) {
            $data = $conpherence->getDisplayData($this->getUser());
            $header = JavelinHtml::phutil_tag(
                'span',
                array(),
                $data['title']);
        } else {
            $header = JavelinHtml::phutil_tag(
                'span',
                array(),
                \Yii::t("app", 'Conpherence'));
        }

        $status = new PhabricatorNotificationStatusView();

        return
            JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'conpherence-durable-column-header-inner',
                ),
                array(
                    $status,
                    JavelinHtml::phutil_tag(
                        'div',
                        array(
                            'sigil' => 'conpherence-durable-column-header-text',
                            'class' => 'conpherence-durable-column-header-text',
                        ),
                        $header),
                    $settings_button,
                    $settings_menu,
                ));
    }

    /**
     * @param $conpherence
     * @return array
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function getHeaderActionsConfig($conpherence)
    {

        $actions = array();
        if ($conpherence) {
            $can_edit = PhabricatorPolicyFilter::hasCapability(
                $this->getUser(),
                $conpherence,
                PhabricatorPolicyCapability::CAN_EDIT);
            $actions[] = array(
                'name' => \Yii::t("app", 'Add Participants'),
                'disabled' => !$can_edit,
                'href' => '/conpherence/update/' . $conpherence->getID() . '/',
                'icon' => 'fa-plus',
                'key' => ConpherenceUpdateActions::ADD_PERSON,
            );
            $actions[] = array(
                'name' => \Yii::t("app", 'Edit Room'),
                'disabled' => !$can_edit,
                'href' => '/conpherence/edit/' . $conpherence->getID() . '/',
                'icon' => 'fa-pencil',
                'key' => 'go_edit',
            );
            $actions[] = array(
                'name' => \Yii::t("app", 'View in Conpherence'),
                'disabled' => false,
                'href' => '/' . $conpherence->getMonogram(),
                'icon' => 'fa-comments',
                'key' => 'go_conpherence',
            );
        }

        $actions[] = array(
            'name' => \Yii::t("app", 'Hide Window'),
            'disabled' => false,
            'href' => '#',
            'icon' => 'fa-times',
            'key' => 'hide_column',
        );

        return $actions;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function buildTransactions()
    {
        $conpherence = $this->getSelectedConpherence();
        if (!$conpherence) {
            if (!$this->getVisible() || $this->getInitialLoad()) {
                return \Yii::t("app", 'Loading...');
            }
            $view = array(
                JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'class' => 'column-no-rooms-text',
                    ),
                    \Yii::t("app", 'You have not joined any rooms yet.')),
                JavelinHtml::phutil_tag(
                    'a',
                    array(
                        'href' => '/conpherence/search/',
                        'class' => 'button button-grey',
                    ),
                    \Yii::t("app", 'Find Rooms')),
            );
            return JavelinHtml::phutil_tag_div('column-no-rooms', $view);
        }

        $data = ConpherenceTransactionRenderer::renderTransactions(
            $this->getUser(),
            $conpherence);
        $messages = ConpherenceTransactionRenderer::renderMessagePaneContent(
            $data['transactions'],
            $data['oldest_transaction_id'],
            $data['newest_transaction_id']);

        return $messages;
    }

    /**
     * @return null
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    private function buildTextInput()
    {
        $conpherence = $this->getSelectedConpherence();
        if (!$conpherence) {
            return null;
        }

        $draft = $this->getDraft();
        $draft_value = null;
        if ($draft) {
            $draft_value = $draft->getDraft();
        }

        $textarea_id = JavelinHtml::generateUniqueNodeId();
        $textarea = JavelinHtml::phutil_tag(
            'textarea',
            array(
                'id' => $textarea_id,
                'name' => 'text',
                'class' => 'conpherence-durable-column-textarea',
                'sigil' => 'conpherence-durable-column-textarea',
                'placeholder' => \Yii::t("app", 'Send a message...'),
            ),
            $draft_value);
        JavelinHtml::initBehavior(
            new JavelinDragAndDropTextareaAsset(),
            array(
                'target' => $textarea_id,
                'activatedClass' => 'aphront-textarea-drag-and-drop',
                'uri' => '/file/dropupload/',
            ));
        $id = $conpherence->getID();
        return JavelinHtml::phabricator_form(
            $this->getUser(),
            array(
                'method' => 'POST',
                'action' => '/conpherence/update/' . $id . '/',
                'sigil' => 'conpherence-message-form',
            ),
            array(
                $textarea,
                JavelinHtml::phutil_tag(
                    'input',
                    array(
                        'type' => 'hidden',
                        'name' => 'action',
                        'value' => ConpherenceUpdateActions::MESSAGE,
                    )),
            ));
    }

    /**
     * @return null
     * @author 陈妙威
     */
    private function buildStatusText()
    {
        return null;
    }

    /**
     * @return null
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    private function buildSendButton()
    {
        $conpherence = $this->getSelectedConpherence();
        if (!$conpherence) {
            return null;
        }

        return JavelinHtml::phutil_tag(
            'button',
            array(
                'class' => 'grey',
                'sigil' => 'conpherence-send-message',
            ),
            \Yii::t("app", 'Send'));
    }

}
