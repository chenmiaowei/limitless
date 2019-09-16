<?php

namespace orangins\lib\request\httpparametertype;

use orangins\lib\request\AphrontRequest;
use yii\web\Request;

/**
 * Class AphrontProjectListHTTPParameterType
 * @package orangins\lib\request\httpparametertype
 * @author 陈妙威
 */
final class AphrontProjectListHTTPParameterType extends AphrontListHTTPParameterType
{

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return wild
     * @author 陈妙威
     */
    protected function getParameterValue(AphrontRequest $request, $key)
    {
        $type = new AphrontStringListHTTPParameterType();
        $list = $this->getValueWithType($type, $request, $key);

        return (new PhabricatorProjectPHIDResolver())
            ->setViewer($this->getViewer())
            ->resolvePHIDs($list);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'list<project>';
    }

    /**
     * @return array|list
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app",'Comma-separated list of project PHIDs.'),
            \Yii::t("app",'List of project PHIDs, as array.'),
            \Yii::t("app",'Comma-separated list of project hashtags.'),
            \Yii::t("app",'List of project hashtags, as array.'),
            \Yii::t("app",'Mixture of hashtags and PHIDs.'),
        );
    }

    /**
     * @return array|list
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            'v=PHID-PROJ-1111',
            'v=PHID-PROJ-1111,PHID-PROJ-2222',
            'v=hashtag',
            'v=frontend,backend',
            'v[]=PHID-PROJ-1111&v[]=PHID-PROJ-2222',
            'v[]=frontend&v[]=backend',
            'v=PHID-PROJ-1111,frontend',
            'v[]=PHID-PROJ-1111&v[]=backend',
        );
    }

}
