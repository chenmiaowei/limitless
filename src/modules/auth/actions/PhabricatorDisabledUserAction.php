<?php

namespace orangins\modules\auth\actions;

use orangins\lib\response\Aphront404Response;

/**
 * Class PhabricatorDisabledUserAction
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorDisabledUserAction
    extends PhabricatorAuthAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireEnabledUser()
    {
        return false;
    }

    /**
     * @return Aphront404Response|\orangins\lib\view\AphrontDialogView
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $id = $request->getURIData('id');

        if (!$viewer->getIsDisabled()) {
            return new Aphront404Response();
        }

        return $this->newDialog()
            ->setTitle(\Yii::t("app", 'Account Disabled'))
            ->addCancelButton('/logout/', \Yii::t("app", 'Okay'))
            ->appendParagraph(\Yii::t("app", 'Your account has been disabled.'));
    }

}
