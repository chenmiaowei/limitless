<?php

namespace orangins\modules\conduit\parametertype;

/**
 * Class ConduitColumnsParameterType
 * @package orangins\modules\conduit\parametertype
 * @author 陈妙威
 */
final class ConduitColumnsParameterType
    extends ConduitParameterType
{

    /**
     * @param array $request
     * @param $key
     * @param $strict
     * @return mixed
     * @author 陈妙威
     */
    protected function getParameterValue(array $request, $key, $strict)
    {
        // We don't do any meaningful validation here because the transaction
        // itself validates everything and the input format is flexible.
        return parent::getParameterValue($request, $key, $strict);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'columns';
    }

    /**
     * @return array|null
     * @author 陈妙威
     */
    protected function getParameterDefault()
    {
        return array();
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app", 'Single column PHID.'),
            \Yii::t("app", 'List of column PHIDs.'),
            \Yii::t("app", 'List of position dictionaries.'),
            \Yii::t("app", 'List with a mixture of PHIDs and dictionaries.'),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            '"PHID-PCOL-1111"',
            '["PHID-PCOL-2222", "PHID-PCOL-3333"]',
            '[{"columnPHID": "PHID-PCOL-4444", "afterPHID": "PHID-TASK-5555"}]',
            '[{"columnPHID": "PHID-PCOL-4444", "beforePHID": "PHID-TASK-6666"}]',
        );
    }

}
