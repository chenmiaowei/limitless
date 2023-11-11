<?php

namespace orangins\modules\dashboard\editfield;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\request\httpparametertype\AphrontSelectHTTPParameterType;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use PhutilClassMapQuery;

/**
 * Class PhabricatorDashboardQueryPanelApplicationEditField
 * @package orangins\modules\dashboard\editfield
 * @author 陈妙威
 */
final class PhabricatorDashboardQueryPanelApplicationEditField
    extends PhabricatorEditField
{

    /**
     * @var
     */
    private $controlID;

    /**
     * @return \orangins\lib\view\form\control\AphrontFormControl|AphrontFormSelectControl
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function newControl()
    {
        $engines = (new PhutilClassMapQuery())
            ->setUniqueMethod('getClassShortName')
            ->setAncestorClass(PhabricatorApplicationSearchEngine::className())
            ->setFilterMethod('canUseInPanelContext')
            ->execute();

        $all_apps = (new PhabricatorApplicationQuery())
            ->setViewer($this->getViewer())
            ->withUnlisted(false)
            ->withInstalled(true)
            ->execute();
        foreach ($engines as $index => $engine) {
            if (!isset($all_apps[$engine->getApplicationClassName()])) {
                unset($engines[$index]);
                continue;
            }
        }

        $options = array();

        $value = $this->getValueForControl();
        if (strlen($value) && empty($engines[$value])) {
            $options[$value] = $value;
        }

        $engines = msort($engines, 'getResultTypeDescription');
        foreach ($engines as $class_name => $engine) {
            $options[$class_name] = $engine->getResultTypeDescription();
        }

        return (new AphrontFormSelectControl())
            ->setID($this->getControlID())
            ->setOptions($options);
    }

    /**
     * @return AphrontSelectHTTPParameterType|\orangins\lib\request\httpparametertype\AphrontStringHTTPParameterType
     * @author 陈妙威
     */
    protected function newHTTPParameterType()
    {
        return new AphrontSelectHTTPParameterType();
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
