<?php

namespace orangins\modules\conpherence\view;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\markup\PhabricatorMarkupEngine;
use orangins\lib\view\AphrontView;
use orangins\lib\view\layout\PhabricatorAnchorView;
use orangins\modules\conpherence\models\ConpherenceThread;
use orangins\modules\conpherence\models\ConpherenceTransaction;
use orangins\modules\phid\PhabricatorObjectHandle;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\models\PhabricatorApplicationTransactionComment;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;
use PhutilInvalidStateException;

/**
 * Class ConpherenceTransactionView
 * @package orangins\modules\conpherence\view
 * @author 陈妙威
 */
final class ConpherenceTransactionView extends AphrontView
{

    /**
     * @var
     */
    private $conpherenceThread;
    /**
     * @var
     */
    private $conpherenceTransaction;
    /**
     * @var
     */
    private $handles;
    /**
     * @var
     */
    private $markupEngine;
    /**
     * @var array
     */
    private $classes = array();
    /**
     * @var
     */
    private $searchResult;
    /**
     * @var
     */
    private $timeOnly;

    /**
     * @param ConpherenceThread $t
     * @return $this
     * @author 陈妙威
     */
    public function setConpherenceThread(ConpherenceThread $t)
    {
        $this->conpherenceThread = $t;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getConpherenceThread()
    {
        return $this->conpherenceThread;
    }

    /**
     * @param ConpherenceTransaction $tx
     * @return $this
     * @author 陈妙威
     */
    public function setConpherenceTransaction(ConpherenceTransaction $tx)
    {
        $this->conpherenceTransaction = $tx;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getConpherenceTransaction()
    {
        return $this->conpherenceTransaction;
    }

    /**
     * @param array $handles
     * @return $this
     * @author 陈妙威
     */
    public function setHandles(array $handles)
    {
        assert_instances_of($handles, PhabricatorObjectHandle::className());
        $this->handles = $handles;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHandles()
    {
        return $this->handles;
    }

    /**
     * @param PhabricatorMarkupEngine $markup_engine
     * @return $this
     * @author 陈妙威
     */
    public function setMarkupEngine(PhabricatorMarkupEngine $markup_engine)
    {
        $this->markupEngine = $markup_engine;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getMarkupEngine()
    {
        return $this->markupEngine;
    }

    /**
     * @param $class
     * @return $this
     * @author 陈妙威
     */
    public function addClass($class)
    {
        $this->classes[] = $class;
        return $this;
    }

    /**
     * @param $result
     * @return $this
     * @author 陈妙威
     */
    public function setSearchResult($result)
    {
        $this->searchResult = $result;
        return $this;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        $viewer = $this->getUser();
        if (!$viewer) {
            throw new PhutilInvalidStateException('setUser');
        }

//    require_celerity_resource('conpherence-transaction-css');

        $transaction = $this->getConpherenceTransaction();
        switch ($transaction->getTransactionType()) {
            case ConpherenceThreadDateMarkerTransaction::TRANSACTIONTYPE:
                return javelin_tag(
                    'div',
                    array(
                        'class' => 'conpherence-transaction-view date-marker',
                        'sigil' => 'conpherence-transaction-view',
                        'meta' => array(
                            'id' => $transaction->getID() + 0.5,
                        ),
                    ),
                    array(
                        phutil_tag(
                            'span',
                            array(
                                'class' => 'date',
                            ),
                            phabricator_format_local_time(
                                $transaction->created_at,
                                $viewer,
                                'M jS, Y')),
                    ));
                break;
        }

        $info = $this->renderTransactionInfo();
        $actions = $this->renderTransactionActions();
        $image = $this->renderTransactionImage();
        $content = $this->renderTransactionContent();
        $classes = implode(' ', $this->classes);
        $transaction_dom_id = 'anchor-' . $transaction->getID();

        $header = phutil_tag_div(
            'conpherence-transaction-header grouped',
            array($actions, $info));

        return javelin_tag(
            'div',
            array(
                'class' => 'conpherence-transaction-view ' . $classes,
                'id' => $transaction_dom_id,
                'sigil' => 'conpherence-transaction-view',
                'meta' => array(
                    'id' => $transaction->getID(),
                ),
            ),
            array(
                $image,
                phutil_tag_div('conpherence-transaction-detail grouped',
                    array($header, $content)),
            ));
    }

    /**
     * @return \PhutilSafeHTML
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderTransactionInfo()
    {
        $viewer = $this->getUser();
        $thread = $this->getConpherenceThread();
        $transaction = $this->getConpherenceTransaction();
        $info = array();

        JavelinHtml::initBehavior(new JavelinTooltipAsset());
        $tip = OranginsViewUtil::phabricator_datetime($transaction->created_at, $viewer);
        $label = OranginsViewUtil::phabricator_time($transaction->created_at, $viewer);
        $width = 360;

        Javelin::initBehavior('phabricator-watch-anchor');
        $anchor = (new PhabricatorAnchorView())
            ->setAnchorName($transaction->getID())
            ->render();

        if ($this->searchResult) {
            $uri = $thread->getMonogram();
            $info[] = hsprintf(
                '%s',
                javelin_tag(
                    'a',
                    array(
                        'href' => '/' . $uri . '#' . $transaction->getID(),
                        'class' => 'transaction-date',
                        'sigil' => 'conpherence-search-result-jump',
                    ),
                    $tip));
        } else {
            $info[] = hsprintf(
                '%s%s',
                $anchor,
                javelin_tag(
                    'a',
                    array(
                        'href' => '#' . $transaction->getID(),
                        'class' => 'transaction-date anchor-link',
                        'sigil' => 'has-tooltip',
                        'meta' => array(
                            'tip' => $tip,
                            'size' => $width,
                        ),
                    ),
                    $label));
        }

        return phutil_tag(
            'span',
            array(
                'class' => 'conpherence-transaction-info',
            ),
            $info);
    }

    /**
     * @return array|null
     * @author 陈妙威
     */
    private function renderTransactionActions()
    {
        $transaction = $this->getConpherenceTransaction();

        switch ($transaction->getTransactionType()) {
            case PhabricatorTransactions::TYPE_COMMENT:
                $handles = $this->getHandles();
                $author = $handles[$transaction->getAuthorPHID()];
                $actions = array($author->renderLink());
                break;
            default:
                $actions = null;
                break;
        }

        return $actions;
    }

    /**
     * @return null|\PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderTransactionImage()
    {
        $image = null;
        $transaction = $this->getConpherenceTransaction();
        switch ($transaction->getTransactionType()) {
            case PhabricatorTransactions::TYPE_COMMENT:
                $handles = $this->getHandles();
                $author = $handles[$transaction->getAuthorPHID()];
                $image_uri = $author->getImageURI();
                $image = phutil_tag(
                    'span',
                    array(
                        'class' => 'conpherence-transaction-image',
                        'style' => 'background-image: url(' . $image_uri . ');',
                    ));
                break;
        }
        return $image;
    }

    /**
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderTransactionContent()
    {
        $transaction = $this->getConpherenceTransaction();
        $content = null;
        $content_class = null;
        $content = null;
        $handles = $this->getHandles();
        switch ($transaction->getTransactionType()) {
            case PhabricatorTransactions::TYPE_COMMENT:
                $this->addClass('conpherence-comment');
                $author = $handles[$transaction->getAuthorPHID()];
                $comment = $transaction->getComment();
                $content = $this->getMarkupEngine()->getOutput(
                    $comment,
                    PhabricatorApplicationTransactionComment::MARKUP_FIELD_COMMENT);
                $content_class = 'conpherence-message';
                break;
            default:
                $content = $transaction->getTitle();
                $this->addClass('conpherence-edited');
                break;
        }

        $view = phutil_tag(
            'div',
            array(
                'class' => $content_class,
            ),
            $content);

        return phutil_tag_div('conpherence-transaction-content', $view);
    }

}
