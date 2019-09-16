<?php

namespace orangins\modules\transactions\actions;

use orangins\lib\infrastructure\contentsource\PhabricatorContentSource;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\response\AphrontReloadResponse;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionCommentEditor;

final class PhabricatorApplicationTransactionCommentRemoveController
    extends PhabricatorApplicationTransactionController
{

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
            return new Aphront404Response();
        }

        if ($xaction->getComment()->getIsRemoved()) {
            // You can't remove an already-removed comment.
            return new Aphront400Response();
        }

        $obj_phid = $xaction->getObjectPHID();
        $obj_handle = (new PhabricatorHandleQuery())
            ->setViewer($viewer)
            ->withPHIDs(array($obj_phid))
            ->executeOne();

        if ($request->isDialogFormPost()) {
            $comment = $xaction->getApplicationTransactionCommentObject()
                ->setContent('')
                ->setIsRemoved(true);

            $editor = (new PhabricatorApplicationTransactionCommentEditor())
                ->setActor($viewer)
                ->setContentSource(PhabricatorContentSource::newFromRequest($request))
                ->applyEdit($xaction, $comment);

            if ($request->isAjax()) {
                return (new AphrontAjaxResponse())->setContent(array());
            } else {
                return (new AphrontReloadResponse())->setURI($obj_handle->getURI());
            }
        }

        $form = (new AphrontFormView())
            ->setUser($viewer);

        $dialog = $this->newDialog()
            ->setTitle(\Yii::t("app", 'Remove Comment'));

        $dialog
            ->addHiddenInput('anchor', $request->getStr('anchor'))
            ->appendParagraph(
                \Yii::t("app",
                    "Removing a comment prevents anyone (including you) from reading " .
                    "it. Removing a comment also hides the comment's edit history " .
                    "and prevents it from being edited."))
            ->appendParagraph(
                \Yii::t("app", 'Really remove this comment?'));

        $dialog
            ->addSubmitButton(\Yii::t("app", 'Remove Comment'))
            ->addCancelButton($obj_handle->getURI());

        return $dialog;
    }

}
