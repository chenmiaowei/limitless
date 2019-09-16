<?php

namespace orangins\modules\dashboard\paneltype;

use orangins\lib\infrastructure\customfield\field\PhabricatorCustomFieldList;
use orangins\lib\OranginsObject;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\modules\dashboard\engine\PhabricatorDashboardPanelRenderingEngine;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\people\models\PhabricatorUser;
use PhutilClassMapQuery;

/**
 * Class PhabricatorDashboardPanelType
 * @package orangins\modules\dashboard\paneltype
 * @author 陈妙威
 */
abstract class PhabricatorDashboardPanelType extends OranginsObject
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getPanelTypeKey();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getPanelTypeName();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getPanelTypeDescription();


    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getIcon();

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorDashboardPanel $panel
     * @param PhabricatorDashboardPanelRenderingEngine $engine
     * @return mixed
     * @author 陈妙威
     */
    abstract public function renderPanelContent(
        PhabricatorUser $viewer,
        PhabricatorDashboardPanel $panel,
        PhabricatorDashboardPanelRenderingEngine $engine);

    /**
     * @param PhabricatorDashboardPanel $panel
     * @param PhabricatorCustomFieldList $field_list
     * @param AphrontRequest $request
     * @author 陈妙威
     */
    public function initializeFieldsFromRequest(
        PhabricatorDashboardPanel $panel,
        PhabricatorCustomFieldList $field_list,
        AphrontRequest $request)
    {
        return;
    }

    /**
     * Should this panel pull content in over AJAX?
     *
     * Normally, panels use AJAX to render their content. This makes the page
     * interactable sooner, allows panels to render in parallel, and prevents one
     * slow panel from slowing everything down.
     *
     * However, some panels are very cheap to build (i.e., no expensive service
     * calls or complicated rendering). In these cases overall performance can be
     * improved by disabling async rendering so the panel rendering happens in the
     * same process.
     *
     * @return bool True to enable asynchronous rendering when appropriate.
     */
    public function shouldRenderAsync()
    {
        return true;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param PhabricatorDashboardPanel $panel
     * @param PhabricatorDashboardPanelRenderingEngine $engine
     * @param PHUIHeaderView $header
     * @return PHUIHeaderView
     * @author 陈妙威
     */
    public function adjustPanelHeader(
        PhabricatorUser $viewer,
        PhabricatorDashboardPanel $panel,
        PhabricatorDashboardPanelRenderingEngine $engine,
        PHUIHeaderView $header)
    {
        return $header;
    }

    /**
     * @return PhabricatorDashboardPanelType[]
     * @author 陈妙威
     */
    public static function getAllPanelTypes()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getPanelTypeKey')
            ->execute();
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return mixed
     * @author 陈妙威
     */
    final public function getEditEngineFields(PhabricatorDashboardPanel $panel)
    {
        return $this->newEditEngineFields($panel);
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newEditEngineFields(
        PhabricatorDashboardPanel $panel);

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return array
     * @author 陈妙威
     */
    public function getSubpanelPHIDs(PhabricatorDashboardPanel $panel)
    {
        return array();
    }

    /**
     * @param PhabricatorDashboardPanel $panel
     * @return array
     * @author 陈妙威
     */
    public function getCardClasses(PhabricatorDashboardPanel $panel)
    {
        return array();
    }
}
