<?php

namespace orangins\lib\request\httpparametertype;

use orangins\lib\request\AphrontRequest;
use orangins\modules\phid\resolver\PhabricatorUserPHIDResolver;

/**
 * Class AphrontUserListHTTPParameterType
 * @package orangins\lib\request\httpparametertype
 * @author 陈妙威
 */
final class AphrontUserListHTTPParameterType extends AphrontListHTTPParameterType
{

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return array
     * @author 陈妙威
     */
    protected function getParameterValue(AphrontRequest $request, $key)
    {
        $type = new AphrontStringListHTTPParameterType();
        $list = $this->getValueWithType($type, $request, $key);

        return (new PhabricatorUserPHIDResolver())
            ->setViewer($this->getViewer())
            ->resolvePHIDs($list);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'list<user>';
    }

    /**
     * @return array|array
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app",'Comma-separated list of user PHIDs.'),
            \Yii::t("app",'List of user PHIDs, as array.'),
            \Yii::t("app",'Comma-separated list of usernames.'),
            \Yii::t("app",'List of usernames, as array.'),
            \Yii::t("app",'Mixture of usernames and PHIDs.'),
        );
    }

    /**
     * @return array|array
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            'v=PHID-USER-1111',
            'v=PHID-USER-1111,PHID-USER-2222',
            'v=username',
            'v=alincoln,htaft',
            'v[]=PHID-USER-1111&v[]=PHID-USER-2222',
            'v[]=htaft&v[]=alincoln',
            'v=PHID-USER-1111,alincoln',
            'v[]=PHID-USER-1111&v[]=htaft',
        );
    }

}
