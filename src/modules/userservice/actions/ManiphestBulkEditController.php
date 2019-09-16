<?php

namespace orangins\modules\userservice\actions;


/**
 * Class ManiphestBulkEditController
 * @package orangins\modules\userservice\actions
 * @author 陈妙威
 */
final class ManiphestBulkEditController extends PhabricatorUserServiceAction
{

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $viewer = $this->getViewer();
        $request = $this->getRequest();

        return $this->newDialog()
            ->setTitle("已选中")
            ->appendChild(print_r($request->getStrList('ids'), true));
    }
}
