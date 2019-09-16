<?php

namespace orangins\modules\typeahead\datasource;

use orangins\lib\helpers\OranginsUtil;
use Exception;

/**
 * Class PhabricatorTypeaheadProxyDatasource
 * @package orangins\modules\typeahead\datasource
 * @author 陈妙威
 */
abstract class PhabricatorTypeaheadProxyDatasource
    extends PhabricatorTypeaheadCompositeDatasource
{

    /**
     * @var
     */
    private $datasource;

    /**
     * @param PhabricatorTypeaheadDatasource $datasource
     * @return $this
     * @author 陈妙威
     */
    public function setDatasource(PhabricatorTypeaheadDatasource $datasource)
    {
        $this->datasource = $datasource;
        $this->setParameters(
            array(
                'class' => get_class($datasource),
                'parameters' => $datasource->getParameters(),
            ));
        return $this;
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getDatasource()
    {
        if (!$this->datasource) {
            $class = $this->getParameter('class');

            $parent = 'PhabricatorTypeaheadDatasource';
            if (!is_subclass_of($class, $parent)) {
                throw new Exception(
                    \Yii::t("app",
                        'Configured datasource class "%s" must be a valid subclass of ' .
                        '"%s".',
                        $class,
                        $parent));
            }

            $datasource = OranginsUtil::newv($class, array());
            $datasource->setParameters($this->getParameter('parameters', array()));
            $this->datasource = $datasource;
        }

        return $this->datasource;
    }

    /**
     * @return array
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getComponentDatasources()
    {
        return array(
            $this->getDatasource(),
        );
    }

    /**
     * @return mixed|null
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return $this->getDatasource()->getDatasourceApplicationClass();
    }

    /**
     * @return mixed|string
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return $this->getDatasource()->getBrowseTitle();
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return $this->getDatasource()->getPlaceholderText();
    }

}
