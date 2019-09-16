<?php

namespace orangins\modules\dashboard\editfield;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\dashboard\paneltype\chart\PhabricatorDashboardPanelChartEngine;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorDashboardQueryPanelApplicationEditField
 * @package orangins\modules\dashboard\editfield
 * @author 陈妙威
 */
final class PhabricatorDashboardChartPanelTypeEditField
    extends PhabricatorEditField
{

    private $id;
    /**
     * @var
     */
    private $controlID;

    /**
     * @return \orangins\lib\view\form\control\AphrontFormControl|AphrontFormSelectControl
     * @author 陈妙威
     */
    protected function newControl()
    {
        $phabricatorDashboardPanelChartEngines = PhabricatorDashboardPanelChartEngine::getAllEngines();
        $map = ArrayHelper::map($phabricatorDashboardPanelChartEngines, function (PhabricatorDashboardPanelChartEngine $chartEngine) {
            return $chartEngine->getChartTypeKey();
        }, function (PhabricatorDashboardPanelChartEngine $chartEngine) {
            return $chartEngine->getChartTypeDesc();
        });

        return (new AphrontFormSelectControl())
            ->setViewer($this->getViewer())
            ->setControlID($this->getControlID())
            ->setID($this->getID())
            ->setLabel($this->getLabel())
            ->setOptions($map)
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
     * @return mixed
     * @author 陈妙威
     */
    public function getID()
    {
        if (!$this->id) {
            $this->id = JavelinHtml::generateUniqueNodeId();
        }

        return $this->id;
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
