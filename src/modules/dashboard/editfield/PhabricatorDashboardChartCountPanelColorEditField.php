<?php

namespace orangins\modules\dashboard\editfield;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\lib\view\phui\PHUITagView;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\dashboard\assets\JavelinDashboardChartPanelSelectBehaviorAsset;
use orangins\modules\transactions\editfield\PhabricatorEditField;

/**
 * Class PhabricatorDashboardQueryPanelApplicationEditField
 * @package orangins\modules\dashboard\editfield
 * @author 陈妙威
 */
final class PhabricatorDashboardChartCountPanelColorEditField
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
     * @return PhabricatorDashboardChartCountPanelColorEditField
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
        return (new AphrontFormSelectControl())
            ->setViewer($this->getViewer())
            ->setControlID($this->getControlID())
            ->setLabel($this->getLabel())
            ->setOptions([
                PHUITagView::COLOR_DANGER => PHUITagView::COLOR_DANGER,
                PHUITagView::COLOR_SUCCESS => PHUITagView::COLOR_SUCCESS,
                PHUITagView::COLOR_WARNING => PHUITagView::COLOR_WARNING,
                PHUITagView::COLOR_INFO => PHUITagView::COLOR_INFO,
                PHUITagView::COLOR_PRIMARY => PHUITagView::COLOR_PRIMARY,
                PHUITagView::COLOR_GREY => PHUITagView::COLOR_GREY,
                PHUITagView::COLOR_SLATE => PHUITagView::COLOR_SLATE,
                PHUITagView::COLOR_ORANGE => PHUITagView::COLOR_ORANGE,
                PHUITagView::COLOR_GREEN => PHUITagView::COLOR_GREEN,
                PHUITagView::COLOR_VIOLET => PHUITagView::COLOR_VIOLET,
                PHUITagView::COLOR_BLUE => PHUITagView::COLOR_BLUE,
                PHUITagView::COLOR_INDIGO => PHUITagView::COLOR_INDIGO,
                PHUITagView::COLOR_TEAL => PHUITagView::COLOR_TEAL,
            ])
            ->setValue($this->getValue());
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
