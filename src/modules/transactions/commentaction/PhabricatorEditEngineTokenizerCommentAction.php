<?php

namespace orangins\modules\transactions\commentaction;

use orangins\lib\view\control\AphrontTokenizerTemplateView;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;

/**
 * Class PhabricatorEditEngineTokenizerCommentAction
 * @package orangins\modules\transactions\commentaction
 * @author 陈妙威
 */
final class PhabricatorEditEngineTokenizerCommentAction extends PhabricatorEditEngineCommentAction
{

    /**
     * @var
     */
    private $datasource;
    /**
     * @var
     */
    private $limit;

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
     * @return mixed
     * @author 陈妙威
     */
    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * @param $limit
     * @return $this
     * @author 陈妙威
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPHUIXControlType()
    {
        return 'tokenizer';
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws \Exception
     */
    public function getPHUIXControlSpecification()
    {
        $template = new AphrontTokenizerTemplateView();

        $datasource = $this->getDatasource();
        $limit = $this->getLimit();

        $value = $this->getValue();
        if (!$value) {
            $value = array();
        }
        $value = $datasource->getWireTokens($value);

        return array(
            'markup' => $template->render(),
            'config' => array(
                'src' => $datasource->getDatasourceURI(),
                'browseURI' => $datasource->getBrowseURI(),
                'placeholder' => $datasource->getPlaceholderText(),
                'limit' => $limit,
            ),
            'value' => $value,
        );
    }

}
