<?php

namespace orangins\lib\view\control;

use orangins\lib\OranginsObject;
use orangins\lib\helpers\JavelinHtml;
use orangins\modules\phid\PhabricatorObjectHandle;

/**
 * Class PhabricatorObjectSelectorDialog
 * @package orangins\lib\view\control
 * @author 陈妙威
 */
final class PhabricatorObjectSelectorDialog extends OranginsObject
{

    /**
     * @var
     */
    private $user;
    /**
     * @var array
     */
    private $filters = array();
    /**
     * @var array
     */
    private $handles = array();
    /**
     * @var
     */
    private $cancelURI;
    /**
     * @var
     */
    private $submitURI;
    /**
     * @var
     */
    private $searchURI;
    /**
     * @var
     */
    private $selectedFilter;
    /**
     * @var
     */
    private $excluded;
    /**
     * @var
     */
    private $initialPHIDs;
    /**
     * @var
     */
    private $maximumSelectionSize;

    /**
     * @var
     */
    private $title;
    /**
     * @var
     */
    private $header;
    /**
     * @var
     */
    private $buttonText;
    /**
     * @var
     */
    private $instructions;

    /**
     * @param $user
     * @return $this
     * @author 陈妙威
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param array $filters
     * @return $this
     * @author 陈妙威
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * @param $selected_filter
     * @return $this
     * @author 陈妙威
     */
    public function setSelectedFilter($selected_filter)
    {
        $this->selectedFilter = $selected_filter;
        return $this;
    }

    /**
     * @param $excluded_phid
     * @return $this
     * @author 陈妙威
     */
    public function setExcluded($excluded_phid)
    {
        $this->excluded = $excluded_phid;
        return $this;
    }

    /**
     * @param array $handles
     * @return $this
     * @author 陈妙威
     */
    public function setHandles(array $handles)
    {
        assert_instances_of($handles, PhabricatorObjectHandle::class);
        $this->handles = $handles;
        return $this;
    }

    /**
     * @param $cancel_uri
     * @return $this
     * @author 陈妙威
     */
    public function setCancelURI($cancel_uri)
    {
        $this->cancelURI = $cancel_uri;
        return $this;
    }

    /**
     * @param $submit_uri
     * @return $this
     * @author 陈妙威
     */
    public function setSubmitURI($submit_uri)
    {
        $this->submitURI = $submit_uri;
        return $this;
    }

    /**
     * @param $search_uri
     * @return $this
     * @author 陈妙威
     */
    public function setSearchURI($search_uri)
    {
        $this->searchURI = $search_uri;
        return $this;
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
     * @param $header
     * @return $this
     * @author 陈妙威
     */
    public function setHeader($header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @param $button_text
     * @return $this
     * @author 陈妙威
     */
    public function setButtonText($button_text)
    {
        $this->buttonText = $button_text;
        return $this;
    }

    /**
     * @param $instructions
     * @return $this
     * @author 陈妙威
     */
    public function setInstructions($instructions)
    {
        $this->instructions = $instructions;
        return $this;
    }

    /**
     * @param array $initial_phids
     * @return $this
     * @author 陈妙威
     */
    public function setInitialPHIDs(array $initial_phids)
    {
        $this->initialPHIDs = $initial_phids;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getInitialPHIDs()
    {
        return $this->initialPHIDs;
    }

    /**
     * @param $maximum_selection_size
     * @return $this
     * @author 陈妙威
     */
    public function setMaximumSelectionSize($maximum_selection_size)
    {
        $this->maximumSelectionSize = $maximum_selection_size;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMaximumSelectionSize()
    {
        return $this->maximumSelectionSize;
    }

    /**
     * @return AphrontDialogView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildDialog()
    {
        $user = $this->user;

        $filter_id = JavelinHtml::generateUniqueNodeId();
        $query_id = JavelinHtml::generateUniqueNodeId();
        $results_id = JavelinHtml::generateUniqueNodeId();
        $current_id = JavelinHtml::generateUniqueNodeId();
        $search_id = JavelinHtml::generateUniqueNodeId();
        $form_id = JavelinHtml::generateUniqueNodeId();

//        require_celerity_resource('phabricator-object-selector-css');

        $options = array();
        foreach ($this->filters as $key => $label) {
            $options[] = JavelinHtml::phutil_tag(
                'option',
                array(
                    'value' => $key,
                    'selected' => ($key == $this->selectedFilter)
                        ? 'selected'
                        : null,
                ),
                $label);
        }

        $instructions = null;
        if ($this->instructions) {
            $instructions = JavelinHtml::phutil_tag(
                'p',
                array('class' => 'phabricator-object-selector-instructions'),
                $this->instructions);
        }

        $search_box = phabricator_form(
            $user,
            array(
                'method' => 'POST',
                'action' => $this->submitURI,
                'id' => $search_id,
            ),
            JavelinHtml::phutil_tag(
                'table',
                array('class' => 'phabricator-object-selector-search'),
                JavelinHtml::phutil_tag('tr', array(), array(
                    JavelinHtml::phutil_tag(
                        'td',
                        array('class' => 'phabricator-object-selector-search-filter'),
                        JavelinHtml::phutil_tag('select', array('id' => $filter_id), $options)),
                    JavelinHtml::phutil_tag(
                        'td',
                        array('class' => 'phabricator-object-selector-search-text'),
                        JavelinHtml::phutil_tag('input', array('id' => $query_id, 'type' => 'text'))),
                ))));

        $result_box = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phabricator-object-selector-results',
                'id' => $results_id,
            ),
            '');

        $attached_box = JavelinHtml::phutil_tag_div(
            'phabricator-object-selector-current',
            JavelinHtml::phutil_tag_div(
                'phabricator-object-selector-currently-attached',
                array(
                    JavelinHtml::phutil_tag_div('phabricator-object-selector-header', $this->header),
                    JavelinHtml::phutil_tag('div', array('id' => $current_id)),
                    $instructions,
                )));

        $dialog = new AphrontDialogView();
        $dialog
            ->setUser($this->user)
            ->setTitle($this->title)
            ->setClass('phabricator-object-selector-dialog')
            ->appendChild($search_box)
            ->appendChild($result_box)
            ->appendChild($attached_box)
            ->setRenderDialogAsDiv()
            ->setFormID($form_id)
            ->addSubmitButton($this->buttonText);

        if ($this->cancelURI) {
            $dialog->addCancelButton($this->cancelURI);
        }

        $handle_views = array();
        foreach ($this->handles as $handle) {
            $phid = $handle->getPHID();
            $view = new PhabricatorHandleObjectSelectorDataView($handle);
            $handle_views[$phid] = $view->renderData();
        }

        $dialog->addHiddenInput('phids', implode(';', array_keys($this->handles)));

        $initial_phids = $this->getInitialPHIDs();
        if ($initial_phids) {
            $initial_phids = implode(', ', $initial_phids);
            $dialog->addHiddenInput('initialPHIDs', $initial_phids);
        }

        $maximum = $this->getMaximumSelectionSize();

        Javelin::initBehavior(
            'phabricator-object-selector',
            array(
                'filter' => $filter_id,
                'query' => $query_id,
                'search' => $search_id,
                'results' => $results_id,
                'current' => $current_id,
                'form' => $form_id,
                'exclude' => $this->excluded,
                'uri' => $this->searchURI,
                'handles' => $handle_views,
                'maximum' => $maximum,
            ));

        $dialog->setResizeX(true);
        $dialog->setResizeY($results_id);

        return $dialog;
    }

}
