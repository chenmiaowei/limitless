<?php

namespace orangins\modules\conduit\parametertype;

/**
 * Class ConduitUserParameterType
 * @package orangins\modules\conduit\parametertype
 * @author 陈妙威
 */
final class ConduitUserParameterType
    extends ConduitParameterType
{

    /**
     * @param array $request
     * @param $key
     * @param $strict
     * @return mixed|null
     * @author 陈妙威
     */
    protected function getParameterValue(array $request, $key, $strict)
    {
        $value = parent::getParameterValue($request, $key, $strict);

        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            $this->raiseValidationException(
                $request,
                $key,
                \Yii::t("app", 'Expected PHID or null, got something else.'));
        }

        $user_phids = (new PhabricatorUserPHIDResolver())
            ->setViewer($this->getViewer())
            ->resolvePHIDs(array($value));

        return nonempty(head($user_phids), null);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getParameterTypeName()
    {
        return 'phid|string|null';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getParameterFormatDescriptions()
    {
        return array(
            \Yii::t("app", 'User PHID.'),
            \Yii::t("app", 'Username.'),
            \Yii::t("app", 'Literal null.'),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getParameterExamples()
    {
        return array(
            '"PHID-USER-1111"',
            '"alincoln"',
            'null',
        );
    }

}
