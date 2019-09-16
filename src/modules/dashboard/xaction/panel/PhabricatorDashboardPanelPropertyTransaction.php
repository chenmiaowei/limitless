<?php

namespace orangins\modules\dashboard\xaction\panel;

use orangins\modules\dashboard\models\PhabricatorDashboardPanel;

/**
 * Class PhabricatorDashboardPanelPropertyTransaction
 * @package orangins\modules\dashboard\xaction\panel
 * @author 陈妙威
 */
abstract class PhabricatorDashboardPanelPropertyTransaction
    extends PhabricatorDashboardPanelTransactionType
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getPropertyKey();

    /**
     * @param PhabricatorDashboardPanel $object
     * @author 陈妙威
     * @return
     */
    public function generateOldValue($object)
    {
        $property_key = $this->getPropertyKey();
        return $object->getProperty($property_key);
    }

    /**
     * @param PhabricatorDashboardPanel $object
     * @param $value
     * @author 陈妙威
     * @throws \Exception
     */
    public function applyInternalEffects($object, $value)
    {
        $property_key = $this->getPropertyKey();
        $object->setProperty($property_key, $value);
    }

}
