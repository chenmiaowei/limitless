<?php

namespace orangins\modules\dashboard\layoutconfig;

/**
 * Class PhabricatorDashboardOneThirdLayoutMode
 * @package orangins\modules\dashboard\layoutconfig
 * @author 陈妙威
 */
final class PhabricatorDashboardOneThirdLayoutMode
    extends PhabricatorDashboardLayoutMode
{

    /**
     *
     */
    const LAYOUTMODE = 'layout-mode-third-and-thirds';

    /**
     * @return int
     * @author 陈妙威
     */
    public function getLayoutModeOrder()
    {
        return 700;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getLayoutModeName()
    {
        return \Yii::t("app",'Two Columns: 33%%/66%%');
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
                ->addClass('col-lg-4'),
            $this->newColumn()
                ->setColumnKey('right')
                ->addClass('col-lg-8'),
        );
    }

}
