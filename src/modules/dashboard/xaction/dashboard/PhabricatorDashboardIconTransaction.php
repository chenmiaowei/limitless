<?php

namespace orangins\modules\dashboard\xaction\dashboard;

/**
 * Class PhabricatorDashboardIconTransaction
 * @package orangins\modules\dashboard\xaction\dashboard
 * @author 陈妙威
 */
final class PhabricatorDashboardIconTransaction
    extends PhabricatorDashboardTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'dashboard:icon';

    /**
     * @param $object
     * @author 陈妙威
     * @return
     */
    public function generateOldValue($object)
    {
        return $object->getIcon();
    }

    /**
     * @param $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setIcon($value);
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getTitle()
    {
        $old = $this->getOldValue();
        $new = $this->getNewValue();

        return \Yii::t("app",
            '%s changed the icon for this dashboard from %s to %s.',
            $this->renderAuthor(),
            $this->renderOldValue(),
            $this->renderNewValue());
    }

}
