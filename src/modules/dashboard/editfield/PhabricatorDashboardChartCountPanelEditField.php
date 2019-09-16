<?php

namespace orangins\modules\dashboard\editfield;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\request\httpparametertype\AphrontStringListHTTPParameterType;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\lib\view\form\control\AphrontFormTokenizerControl;
use orangins\modules\conduit\parametertype\ConduitStringListParameterType;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\dashboard\assets\JavelinDashboardChartPanelSelectBehaviorAsset;
use orangins\modules\dashboard\paneltype\chart\PhabricatorDashboardPanelChartPieDataSourceEngine;
use orangins\modules\dashboard\typeahead\PhabricatorDashboardPanelChartCountDatasource;
use orangins\modules\dashboard\typeahead\PhabricatorDashboardPanelChartPieDatasource;
use orangins\modules\transactions\editfield\PhabricatorEditField;

/**
 * Class PhabricatorDashboardQueryPanelApplicationEditField
 * @package orangins\modules\dashboard\editfield
 * @author 陈妙威
 */
final class PhabricatorDashboardChartCountPanelEditField
    extends PhabricatorEditField
{
    /**
     * @var
     */
    public $typeControlId;

    /**
     * @var
     */
    private $controlID;


    /**
     * @param $getControlID
     * @return PhabricatorDashboardChartCountPanelEditField
     * @author 陈妙威
     */
    public function setTypeControlID($getControlID)
    {
        $this->typeControlId = $getControlID;
        return $this;
    }

    /**
     * @return \orangins\lib\view\form\control\AphrontFormControl|AphrontFormSelectControl
     * @author 陈妙威
     * @throws \ReflectionException
     */
    protected function newControl()
    {
        JavelinHtml::initBehavior(new JavelinDashboardChartPanelSelectBehaviorAsset(), [
            'inputID' =>$this->typeControlId,
            'chartID' => $this->getControlID(),
            'type' => 'count'
        ]);
        return (new AphrontFormTokenizerControl())
            ->setDatasource(new PhabricatorDashboardPanelChartCountDatasource())
            ->setLimit(1)
            ->setViewer($this->getViewer())
            ->setControlID($this->getControlID())
            ->setLabel($this->getLabel())
            ->setValue($this->getValue());
    }

    /**
     * @return AphrontStringListHTTPParameterType
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        return new AphrontStringListHTTPParameterType();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getControlID()
    {
        if (!$this->controlID) {
            $this->controlID = JavelinHtml::generateUniqueNodeId();
        }

        return $this->controlID;
    }

    /**
     * @return mixed|ConduitStringParameterType
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitStringListParameterType();
    }

}
