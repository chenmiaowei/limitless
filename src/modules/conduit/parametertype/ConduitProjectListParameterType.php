<?php

namespace orangins\modules\conduit\parametertype;

/**
 * Class ConduitProjectListParameterType
 * @package orangins\modules\conduit\parametertype
 * @author 陈妙威
 */
final class ConduitProjectListParameterType
    extends ConduitListParameterType
{

    /**
     * @param array $request
     * @param $key
     * @param $strict
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function getParameterValue(array $request, $key, $strict)
    {
        $list = parent::getParameterValue($request, $key, $strict);
        $list = $this->parseStringList($request, $key, $list, $strict);
        return (new PhabricatorProjectPHIDResolver())
            ->setViewer($this->getViewer())
            ->resolvePHIDs($list);
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'list<project>';
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app", 'List of project PHIDs.'),
            \Yii::t("app", 'List of project tags.'),
            \Yii::t("app", 'List with a mixture of PHIDs and tags.'),
        );
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            '["PHID-PROJ-1111"]',
            '["backend"]',
            '["PHID-PROJ-2222", "frontend"]',
        );
    }

}
