<?php

namespace orangins\modules\dashboard\xaction\dashboard;

use orangins\modules\dashboard\layoutconfig\PhabricatorDashboardLayoutMode;

/**
 * Class PhabricatorDashboardLayoutTransaction
 * @package orangins\modules\dashboard\xaction\dashboard
 * @author 陈妙威
 */
final class PhabricatorDashboardLayoutTransaction
    extends PhabricatorDashboardTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'dashboard:layoutmode';

    /**
     * @param $object
     * @author 陈妙威
     * @return
     */
    public function generateOldValue($object)
    {
        return $object->getRawLayoutMode();
    }

    /**
     * @param $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setRawLayoutMode($value);
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getTitle()
    {
        $new = $this->getNewValue();

        return \Yii::t("app",
            '{0} changed the layout mode for this dashboard from {1} to {2}.',
            [
                $this->renderAuthor(),
                $this->renderOldValue(),
                $this->renderNewValue()
            ]);
    }

    /**
     * @param $object
     * @param array $xactions
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        $errors = array();

        $mode_map = PhabricatorDashboardLayoutMode::getLayoutModeMap();

        $old_value = $object->getRawLayoutMode();
        foreach ($xactions as $xaction) {
            $new_value = $xaction->getNewValue();

            if ($new_value === $old_value) {
                continue;
            }

            if (!isset($mode_map[$new_value])) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",
                        'Layout mode "%s" is not valid. Supported layout modes ' .
                        'are: %s.',
                        $new_value,
                        implode(', ', array_keys($mode_map))),
                    $xaction);
                continue;
            }
        }

        return $errors;
    }


}
