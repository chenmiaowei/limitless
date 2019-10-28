<?php

namespace orangins\modules\transactions\actions;

use Exception;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\phui\PHUICrumbsView;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use PhutilMethodNotImplementedException;
use Yii;

/**
 * Class PhabricatorApplicationTransactionDetailController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
final class PhabricatorApplicationTransactionDetailController
    extends PhabricatorApplicationTransactionController
{

    /**
     * @var PhabricatorObjectHandle
     */
    private $objectHandle;

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return AphrontDialogView|Aphront404Response
     * @throws Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        // Users can end up on this page directly by following links in email,
        // so we try to make it somewhat reasonable as a standalone page.

        $viewer = $this->getViewer();
        $phid = $request->getURIData('phid');

        $xaction = (new PhabricatorObjectQuery())
            ->withPHIDs(array($phid))
            ->setViewer($viewer)
            ->executeOne();
        if (!$xaction) {
            return new Aphront404Response();
        }

        $details = $xaction->renderChangeDetails($viewer);

        $object_phid = $xaction->getObjectPHID();
        $handles = $viewer->loadHandles(array($object_phid));
        $handle = $handles[$object_phid];
        $this->objectHandle = $handle;

        $cancel_uri = $handle->getURI();

        if ($request->isAjax()) {
            $button_text = Yii::t("app", 'Done');
        } else {
            $button_text = Yii::t("app", 'Continue');
        }

        return $this->newDialog()
            ->setTitle(Yii::t("app", 'Change Details'))
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->setClass('aphront-dialog-tab-group')
            ->appendChild($details)
            ->addCancelButton('#', $button_text);
    }

    /**
     * @return PHUICrumbsView
     * @throws PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();

        $handle = $this->objectHandle;
        if ($handle) {
            $crumbs->addTextCrumb(
                $handle->getObjectName(),
                $handle->getURI());
        }

        return $crumbs;
    }


}
