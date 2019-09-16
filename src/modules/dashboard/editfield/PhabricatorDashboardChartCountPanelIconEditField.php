<?php

namespace orangins\modules\dashboard\editfield;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\lib\view\form\control\PHUIFormIconSetControl;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\dashboard\assets\JavelinDashboardChartPanelSelectBehaviorAsset;
use orangins\modules\dashboard\icon\PhabricatorDashboardIconSet;
use orangins\modules\transactions\editfield\PhabricatorEditField;

/**
 * Class PhabricatorDashboardQueryPanelApplicationEditField
 * @package orangins\modules\dashboard\editfield
 * @author 陈妙威
 */
final class PhabricatorDashboardChartCountPanelIconEditField
    extends PhabricatorEditField
{
    /**
     * @var
     */
    public $controlInputId;

    /**
     * @var
     */
    private $controlID;


    /**
     * @param $getControlID
     * @return PhabricatorDashboardChartCountPanelIconEditField
     * @author 陈妙威
     */
    public function setControlInputID($getControlID)
    {
        $this->controlInputId = $getControlID;
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
            'inputID' => $this->controlInputId,
            'chartID' => $this->getControlID(),
            'type' => 'count'
        ]);

        return (new PHUIFormIconSetControl())
            ->setViewer($this->getViewer())
            ->setControlID($this->getControlID())
            ->setLabel($this->getLabel())
            ->setValue($this->getValue())
            ->setIconSet(new PhabricatorDashboardIconSet());
    }

    /**
     * @return AphrontStringHTTPParameterType
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        return new AphrontStringHTTPParameterType();
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
        return new ConduitStringParameterType();
    }

}
