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
final class PhabricatorDashboardChartPanelTimeEditField
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
        return (new AphrontFormSelectControl())
            ->setViewer($this->getViewer())
            ->setControlID($this->getControlID())
            ->setLabel($this->getLabel())
            ->setOptions([
                'all' => '全部',
                '-1 week' => '近一周',
                '-1 month' => '近一个月',
                '-3 months' => '近三个月',
                '-1 year' => '近一年',
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
