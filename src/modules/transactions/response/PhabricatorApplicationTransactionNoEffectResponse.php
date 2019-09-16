<?php

namespace orangins\modules\transactions\response;

use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontProxyResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionNoEffectException;

/**
 * Class PhabricatorApplicationTransactionNoEffectResponse
 * @package orangins\modules\transactions\response
 * @author 陈妙威
 */
final class PhabricatorApplicationTransactionNoEffectResponse extends AphrontProxyResponse
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var PhabricatorApplicationTransactionNoEffectException
     */
    private $exception;
    /**
     * @var
     */
    private $cancelURI;

    /**
     * @param $cancel_uri
     * @return $this
     * @author 陈妙威
     */
    public function setCancelURI($cancel_uri)
    {
        $this->cancelURI = $cancel_uri;
        return $this;
    }

    /**
     * @param PhabricatorApplicationTransactionNoEffectException $exception
     * @return $this
     * @author 陈妙威
     */
    public function setException(
        PhabricatorApplicationTransactionNoEffectException $exception)
    {
        $this->exception = $exception;
        return $this;
    }

    /**
     * @return AphrontDialogResponse|mixed
     * @author 陈妙威
     */
    protected function buildProxy()
    {
        return new AphrontDialogResponse();
    }

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function reduceProxyResponse()
    {
        $request = $this->getRequest();

        $ex = $this->exception;
        $xactions = $ex->getTransactions();

        $type_comment = PhabricatorTransactions::TYPE_COMMENT;
        $only_empty_comment = (count($xactions) == 1) &&
            (head($xactions)->getTransactionType() == $type_comment);

        $count = phutil_count($xactions);

        if ($ex->hasAnyEffect()) {
            $title = \Yii::t("app", '{0} Action(s) With No Effect', [$count]);
            $head = \Yii::t("app", 'Some of your {0} action(s) have no effect:', [$count]);
            $tail = \Yii::t("app", 'Apply remaining actions?');
            $continue = \Yii::t("app", 'Apply Remaining Actions');
        } else if ($ex->hasComment()) {
            $title = \Yii::t("app", 'Post as Comment');
            $head = \Yii::t("app", 'The {0} action(s) you are taking have no effect:', [$count]);
            $tail = \Yii::t("app", 'Do you want to post your comment anyway?');
            $continue = \Yii::t("app", 'Post Comment');
        } else if ($only_empty_comment) {
            // Special case this since it's common and we can give the user a nicer
            // dialog than "Action Has No Effect".
            $title = \Yii::t("app", 'Empty Comment');
            $head = null;
            $tail = null;
            $continue = null;
        } else {
            $title = \Yii::t("app", '{0} Action(s) Have No Effect', [$count]);
            $head = \Yii::t("app", 'The {0} action(s) you are taking have no effect:', [$count]);
            $tail = null;
            $continue = null;
        }

        $dialog = (new AphrontDialogView())
            ->setUser($request->getViewer())
            ->setTitle($title);

        $dialog->appendChild($head);

        $list = array();
        foreach ($xactions as $xaction) {
            $list[] = $xaction->getNoEffectDescription();
        }

        if ($list) {
            $dialog->appendList($list);
        }
        $dialog->appendChild($tail);

        if ($continue) {
            $passthrough = $request->getPassthroughRequestParameters();
            foreach ($passthrough as $key => $value) {
                $dialog->addHiddenInput($key, $value);
            }
            $dialog->addHiddenInput('__continue__', 1);
            $dialog->addSubmitButton($continue);
        }

        $dialog->addCancelButton($this->cancelURI);

        return $this->getProxy()->setDialog($dialog);
    }

}
