<?php

namespace orangins\modules\dashboard\layoutconfig;

/**
 * Class PhabricatorDashboardTwoThirdsLayoutMode
 * @package orangins\modules\dashboard\layoutconfig
 * @author 陈妙威
 */
final class PhabricatorDashboardTwoThirdsLayoutMode
    extends PhabricatorDashboardLayoutMode
{

    /**
     *
     */
    const LAYOUTMODE = 'layout-mode-thirds-and-third';

    /**
     * @return int
     * @author 陈妙威
     */
    public function getLayoutModeOrder()
    {
        return 600;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getLayoutModeName()
    {
        return \Yii::t("app",'Two Columns: 66%%/33%%');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getLayoutModeColumns()
    {
        return array(
            $this->newColumn()
                ->setColumnKey('left')
                ->addClass('col-lg-8'),
            $this->newColumn()
                ->setColumnKey('right')
                ->addClass('col-lg-4'),
        );
    }

}
