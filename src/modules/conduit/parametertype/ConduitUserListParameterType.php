<?php

namespace orangins\modules\conduit\parametertype;

/**
 * Class ConduitUserListParameterType
 * @package orangins\modules\conduit\parametertype
 * @author 陈妙威
 */
final class ConduitUserListParameterType
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
        return (new PhabricatorUserPHIDResolver())
            ->setViewer($this->getViewer())
            ->resolvePHIDs($list);
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'list<user>';
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app", 'List of user PHIDs.'),
            \Yii::t("app", 'List of usernames.'),
            \Yii::t("app", 'List with a mixture of PHIDs and usernames.'),
        );
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            '["PHID-USER-1111"]',
            '["alincoln"]',
            '["PHID-USER-2222", "alincoln"]',
        );
    }

}
