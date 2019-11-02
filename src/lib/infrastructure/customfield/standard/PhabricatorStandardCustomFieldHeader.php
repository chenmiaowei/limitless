<?php

namespace orangins\lib\infrastructure\customfield\standard;

use Exception;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\view\form\control\AphrontFormStaticControl;

/**
 * Class PhabricatorStandardCustomFieldHeader
 * @package orangins\lib\infrastructure\customfield\standard
 * @author 陈妙威
 */
final class PhabricatorStandardCustomFieldHeader
    extends PhabricatorStandardCustomField
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFieldType()
    {
        return 'header';
    }

    /**
     * @param array $handles
     * @return mixed|AphrontFormStaticControl
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws Exception
     * @author 陈妙威
     */
    public function renderEditControl(array $handles)
    {
        $header = phutil_tag(
            'div',
            array(
                'class' => 'phabricator-standard-custom-field-header',
            ),
            $this->getFieldName());
        return (new AphrontFormStaticControl())
            ->setValue($header);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldUseStorage()
    {
        return false;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getStyleForPropertyView()
    {
        return 'header';
    }

    /**
     * @param array $handles
     * @return mixed|string|null
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function renderPropertyViewValue(array $handles)
    {
        return $this->getFieldName();
    }

    /**
     * @return array|bool
     * @author 陈妙威
     */
    public function shouldAppearInApplicationSearch()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAppearInConduitTransactions()
    {
        return false;
    }

}
