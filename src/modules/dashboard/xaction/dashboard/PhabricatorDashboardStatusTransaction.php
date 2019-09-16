<?php

namespace orangins\modules\dashboard\xaction\dashboard;

use orangins\modules\dashboard\models\PhabricatorDashboard;

/**
 * Class PhabricatorDashboardStatusTransaction
 * @package orangins\modules\dashboard\xaction\dashboard
 * @author 陈妙威
 */
final class PhabricatorDashboardStatusTransaction
    extends PhabricatorDashboardTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'dashboard:status';

    /**
     * @param $object
     * @author 陈妙威
     * @return
     */
    public function generateOldValue($object)
    {
        return $object->getStatus();
    }

    /**
     * @param $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setStatus($value);
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getTitle()
    {
        $new = $this->getNewValue();

        switch ($new) {
            case PhabricatorDashboard::STATUS_ACTIVE:
                return \Yii::t("app",
                    '%s activated this dashboard.',
                    $this->renderAuthor());
            case PhabricatorDashboard::STATUS_ARCHIVED:
                return \Yii::t("app",
                    '%s archived this dashboard.',
                    $this->renderAuthor());
        }
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

        $valid_statuses = PhabricatorDashboard::getStatusNameMap();

        $old_value = $object->getStatus();
        foreach ($xactions as $xaction) {
            $new_value = $xaction->getNewValue();

            if ($new_value === $old_value) {
                continue;
            }

            if (!isset($valid_statuses[$new_value])) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",
                        'Status "%s" is not valid. Supported status constants are: %s.',
                        $new_value,
                        implode(', ', array_keys($valid_statuses))),
                    $xaction);
                continue;
            }
        }

        return $errors;
    }


}
