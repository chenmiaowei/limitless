<?php

namespace orangins\modules\transactions\bulk\type;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\control\AphrontTokenizerTemplateView;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;

/**
 * Class BulkTokenizerParameterType
 * @package orangins\modules\transactions\bulk\type
 * @author 陈妙威
 */
final class BulkTokenizerParameterType
    extends BulkParameterType
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
     * @throws \yii\base\Exception
     */
    public function getPHUIXControlSpecification()
    {
        $template = new AphrontTokenizerTemplateView();
        $template_markup = $template->render();

        $datasource = $this->getDatasource();

        return array(
            'markup' => (string)JavelinHtml::hsprintf('%s', $template_markup),
            'config' => array(
                'src' => $datasource->getDatasourceURI(),
                'browseURI' => $datasource->getBrowseURI(),
                'placeholder' => $datasource->getPlaceholderText(),
                'limit' => $datasource->getLimit(),
            ),
            'value' => null,
        );
    }

}
