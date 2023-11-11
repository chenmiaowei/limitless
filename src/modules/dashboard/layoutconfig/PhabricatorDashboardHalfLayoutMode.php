<?php

namespace orangins\modules\dashboard\layoutconfig;

/**
 * Class PhabricatorDashboardHalfLayoutMode
 * @package orangins\modules\dashboard\layoutconfig
 * @author 陈妙威
 */
final class PhabricatorDashboardHalfLayoutMode
    extends PhabricatorDashboardLayoutMode
{

    /**
     *
     */
    const LAYOUTMODE = 'layout-mode-half-and-half';

    /**
     * @return int
     * @author 陈妙威
     */
    public function getLayoutModeOrder()
    {
        return 500;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getLayoutModeName()
    {
        return \Yii::t("app",'Two Columns: 50%%/50%%');
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function getLayoutModeColumns()
    {
        return array(
            $this->newColumn()
                ->setColumnKey('left')
                ->addClass('col-lg-6 half'),
            $this->newColumn()
                ->setColumnKey('right')
                ->addClass('col-lg-6 half'),
        );
    }

}
