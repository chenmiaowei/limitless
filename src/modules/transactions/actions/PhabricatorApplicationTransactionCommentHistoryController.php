<?php

namespace orangins\modules\transactions\actions;

use orangins\lib\response\Aphront400Response;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;

/**
 * Class PhabricatorApplicationTransactionCommentHistoryController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
final class PhabricatorApplicationTransactionCommentHistoryController
    extends PhabricatorApplicationTransactionController
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return Aphront400Response|Aphront404Response|AphrontDialogResponse
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $phid = $request->getURIData('phid');

        $xaction = (new PhabricatorObjectQuery())
            ->withPHIDs(array($phid))
            ->setViewer($viewer)
            ->executeOne();

        if (!$xaction) {
            return new Aphront404Response();
        }

        if (!$xaction->getComment()) {
            // You can't view history of a transaction with no comments.
            return new Aphront404Response();
        }

        if ($xaction->getComment()->getIsRemoved()) {
            // You can't view history of a transaction with a removed comment.
            return new Aphront400Response();
        }

        $comments = (new PhabricatorApplicationTransactionTemplatedCommentQuery())
            ->setViewer($viewer)
            ->setTemplate($xaction->getApplicationTransactionCommentObject())
            ->withTransactionPHIDs(array($xaction->getPHID()))
            ->execute();

        if (!$comments) {
            return new Aphront404Response();
        }

        $comments = msort($comments, 'getCommentVersion');

        $xactions = array();
        foreach ($comments as $comment) {
            $xaction1 = clone $xaction;
            $xactions[] = $xaction1
                ->makeEphemeral()
                ->setCommentVersion($comment->getCommentVersion())
                ->setContentSource($comment->getContentSource())
                ->setDateCreated($comment->created_at)
                ->attachComment($comment);
        }

        $obj_phid = $xaction->getObjectPHID();
        $obj_handle = (new PhabricatorHandleQuery())
            ->setViewer($viewer)
            ->withPHIDs(array($obj_phid))
            ->executeOne();

        $view = (new PhabricatorApplicationTransactionView())
            ->setUser($viewer)
            ->setObjectPHID($obj_phid)
            ->setTransactions($xactions)
            ->setShowEditActions(false)
            ->setHideCommentOptions(true);

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->setWidth(AphrontDialogView::WIDTH_FULL)
            ->setFlush(true)
            ->setTitle(\Yii::t("app", 'Comment History'));

        $dialog->appendChild($view);

        $dialog
            ->addCancelButton($obj_handle->getURI());

        return (new AphrontDialogResponse())->setDialog($dialog);
    }

}
