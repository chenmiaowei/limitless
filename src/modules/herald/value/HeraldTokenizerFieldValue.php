<?php

namespace orangins\modules\herald\value;

use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use PhutilInvalidStateException;

/**
 * Class HeraldTokenizerFieldValue
 * @package orangins\modules\herald\value
 * @author 陈妙威
 */
final class HeraldTokenizerFieldValue
    extends HeraldFieldValue
{

    /**
     * @var
     */
    private $key;
    /**
     * @var PhabricatorTypeaheadDatasource
     */
    private $datasource;
    /**
     * @var
     */
    private $valueMap;

    /**
     * @param $key
     * @return $this
     * @author 陈妙威
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param PhabricatorTypeaheadDatasource $datasource
     * @return $this
     * @author 陈妙威
     */
    public function setDatasource(PhabricatorTypeaheadDatasource $datasource)
    {
        $this->datasource = $datasource;
        return $this;
    }

    /**
     * @return PhabricatorTypeaheadDatasource
     * @author 陈妙威
     */
    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * @param array $value_map
     * @return $this
     * @author 陈妙威
     */
    public function setValueMap(array $value_map)
    {
        $this->valueMap = $value_map;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getValueMap()
    {
        return $this->valueMap;
    }

    /**
     * @return string
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function getFieldValueKey()
    {
        if ($this->getKey() === null) {
            throw new PhutilInvalidStateException('setKey');
        }
        return 'tokenizer.' . $this->getKey();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getControlType()
    {
        return self::CONTROL_TOKENIZER;
    }

    /**
     * @return array
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function getControlTemplate()
    {
        if ($this->getDatasource() === null) {
            throw new PhutilInvalidStateException('setDatasource');
        }

        $datasource = $this->getDatasource();
        $datasource->setViewer($this->getViewer());

        return array(
            'tokenizer' => array(
                'datasourceURI' => $datasource->getDatasourceURI(),
                'browseURI' => $datasource->getBrowseURI(),
                'placeholder' => $datasource->getPlaceholderText(),
                'limit' => $datasource->getLimit(),
            ),
        );
    }

    /**
     * @param $value
     * @return string
     * @author 陈妙威
     */
    public function renderFieldValue($value)
    {
        $viewer = $this->getViewer();
        $value = (array)$value;

        if ($this->valueMap !== null) {
            foreach ($value as $k => $v) {
                $value[$k] = idx($this->valueMap, $v, $v);
            }
            return implode(', ', $value);
        }

        return $viewer->renderHandleList((array)$value)->setAsInline(true);
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function renderEditorValue($value)
    {
        $viewer = $this->getViewer();
        $value = (array)$value;

        $datasource = $this->getDatasource()
            ->setViewer($viewer);

        return $datasource->getWireTokens($value);
    }

}
