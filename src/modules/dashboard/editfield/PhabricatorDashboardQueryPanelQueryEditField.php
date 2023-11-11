<?php

namespace orangins\modules\dashboard\editfield;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\request\httpparametertype\AphrontSelectHTTPParameterType;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\dashboard\assets\JavelinDashboardQueryPanelSelectBehaviorAsset;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use PhutilClassMapQuery;

/**
 * Class PhabricatorDashboardQueryPanelQueryEditField
 * @package orangins\modules\dashboard\editfield
 * @author 陈妙威
 */
final class PhabricatorDashboardQueryPanelQueryEditField
    extends PhabricatorEditField
{

    /**
     * @var
     */
    private $applicationControlID;

    /**
     * @param $id
     * @return $this
     * @author 陈妙威
     */
    public function setApplicationControlID($id)
    {
        $this->applicationControlID = $id;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplicationControlID()
    {
        return $this->applicationControlID;
    }

    /**
     * @return \orangins\lib\view\form\control\AphrontFormControl|AphrontFormSelectControl
     * @author 陈妙威
     * @throws \ReflectionException
     */
    protected function newControl()
    {
        $engines = (new PhutilClassMapQuery())
            ->setUniqueMethod('getClassShortName')
            ->setAncestorClass(PhabricatorApplicationSearchEngine::className())
            ->setFilterMethod('canUseInPanelContext')
            ->execute();

        $value = $this->getValueForControl();

        $queries = array();
        $seen = false;
        foreach ($engines as $engine_class => $engine) {
            $engine->setViewer($this->getViewer());
            $engine_queries = $engine->loadEnabledNamedQueries();
            $query_map = mpull($engine_queries, 'getQueryName', 'getQueryKey');
            asort($query_map);

            foreach ($query_map as $key => $name) {
                $queries[$engine_class][] = array('key' => $key, 'name' => $name);
                if ($key == $value) {
                    $seen = true;
                }
            }
        }

        if (strlen($value) && !$seen) {
            $name = pht('Custom Query ("%s")', $value);
        } else {
            $name = pht('(None)');
        }

        $options = array($value => $name);

        $application_id = $this->getApplicationControlID();
        $control_id = JavelinHtml::generateUniqueNodeId();

        JavelinHtml::initBehavior(
            new JavelinDashboardQueryPanelSelectBehaviorAsset(),
            array(
                'applicationID' => $application_id,
                'queryID' => $control_id,
                'options' => $queries,
                'value' => array(
                    'key' => strlen($value) ? $value : null,
                    'name' => $name,
                ),
            ));

        return (new AphrontFormSelectControl())
            ->setID($control_id)
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
     * @return mixed|ConduitStringParameterType
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitStringParameterType();
    }
}
