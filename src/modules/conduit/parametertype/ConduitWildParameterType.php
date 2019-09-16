<?php

namespace orangins\modules\conduit\parametertype;

/**
 * Class ConduitWildParameterType
 * @package orangins\modules\conduit\parametertype
 * @author 陈妙威
 */
final class ConduitWildParameterType
    extends ConduitParameterType
{

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'wild';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app", 'Any mixed or complex value. Check the documentation for details.'),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            \Yii::t("app", '(Wildcard)'),
        );
    }

}
