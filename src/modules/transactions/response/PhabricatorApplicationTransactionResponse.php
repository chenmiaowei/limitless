<?php

namespace orangins\modules\transactions\response;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\response\AphrontProxyResponse;
use orangins\lib\view\phui\PHUITimelineView;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;

/**
 * Class PhabricatorApplicationTransactionResponse
 * @package orangins\modules\transactions\response
 * @author 陈妙威
 */
final class PhabricatorApplicationTransactionResponse extends AphrontProxyResponse
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $transactions;
    /**
     * @var
     */
    private $isPreview;
    /**
     * @var
     */
    private $transactionView;
    /**
     * @var
     */
    private $previewContent;

    /**
     * @param $transaction_view
     * @return $this
     * @author 陈妙威
     */
    public function setTransactionView($transaction_view)
    {
        $this->transactionView = $transaction_view;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTransactionView()
    {
        return $this->transactionView;
    }

    /**
     * @return AphrontAjaxResponse|mixed
     * @author 陈妙威
     */
    protected function buildProxy()
    {
        return new AphrontAjaxResponse();
    }

    /**
     * @param $transactions
     * @return $this
     * @author 陈妙威
     */
    public function setTransactions($transactions)
    {
        assert_instances_of($transactions, PhabricatorApplicationTransaction::class);

        $this->transactions = $transactions;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param $is_preview
     * @return $this
     * @author 陈妙威
     */
    public function setIsPreview($is_preview)
    {
        $this->isPreview = $is_preview;
        return $this;
    }

    /**
     * @param $preview_content
     * @return $this
     * @author 陈妙威
     */
    public function setPreviewContent($preview_content)
    {
        $this->previewContent = $preview_content;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPreviewContent()
    {
        return $this->previewContent;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException

     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function reduceProxyResponse()
    {
        if ($this->transactionView) {
            $view = $this->transactionView;
        } else if ($this->getTransactions()) {
            $view = head($this->getTransactions())
                ->getApplicationTransactionViewObject();
        } else {
            $view = new PhabricatorApplicationTransactionView();
        }

        $view
            ->setUser($this->getViewer())
            ->setTransactions($this->getTransactions())
            ->setIsPreview($this->isPreview);

        if ($this->isPreview) {
            $buildEvents = $view->buildEvents();
            $xactions = mpull($buildEvents, 'render');
        } else {
            $list = $view->buildEvents();
            $xactions = mpull($list, 'render', 'getTransactionPHID');
        }

        // Force whatever the underlying views built to render into HTML for
        // the Javascript.
        foreach ($xactions as $key => $xaction) {
            $xactions[$key] = JavelinHtml::hsprintf('%s', $xaction);
        }

        $aural = JavelinHtml::phutil_tag(
            'h3',
            array(
                'class' => 'aural-only',
            ),
            \Yii::t("app", 'Comment Preview'));

        $content = array(
            'header' => JavelinHtml::hsprintf('%s', $aural),
            'xactions' => $xactions,
            'spacer' => PHUITimelineView::renderSpacer(),
            'previewContent' => JavelinHtml::hsprintf('%s', $this->getPreviewContent()),
        );

        return $this->getProxy()->setContent($content);
    }


}
