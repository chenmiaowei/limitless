<?php

namespace orangins\modules\dashboard\layoutconfig;

/**
 * Class PhabricatorDashboardFullLayoutMode
 * @package orangins\modules\dashboard\layoutconfig
 * @author 陈妙威
 */
final class PhabricatorDashboardFullLayoutMode
    extends PhabricatorDashboardLayoutMode
{

    /**
     *
     */
    const LAYOUTMODE = 'layout-mode-full';

    /**
     * @return int
     * @author 陈妙威
     */
    public function getLayoutModeOrder()
    {
        return 0;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getLayoutModeName()
    {
        return \Yii::t("app",'One Column: 100%%');
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function getLayoutModeColumns()
    {
        return array(
            $this->newColumn()
                ->setColumnKey('main')
                ->addClass('col-lg-12'),
        );
    }

}
