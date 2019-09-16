<?php

namespace orangins\modules\file\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\PhabricatorRemarkupControl;
use orangins\lib\view\form\PHUIFormLayoutView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\modules\draft\models\PhabricatorDraft;
use orangins\modules\file\editors\PhabricatorFileEditor;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\models\PhabricatorFileTransaction;
use orangins\modules\file\models\PhabricatorFileTransactionComment;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use PhutilURI;

/**
 * Class PhabricatorFileLightboxAction
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorFileLightboxAction
    extends PhabricatorFileAction
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
     * @return Aphront404Response|AphrontAjaxResponse
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $phid = $request->getURIData('phid');
        $comment = $request->getStr('comment');

        $file = PhabricatorFile::find()
            ->setViewer($viewer)
            ->withPHIDs(array($phid))
            ->executeOne();
        if (!$file) {
            return new Aphront404Response();
        }

        if (strlen($comment)) {
            $xactions = array();
            $xactions[] = (new PhabricatorFileTransaction())
                ->setTransactionType(PhabricatorTransactions::TYPE_COMMENT)
                ->attachComment(
                    (new PhabricatorFileTransactionComment())
                        ->setContent($comment));

            $editor = (new PhabricatorFileEditor())
                ->setActor($viewer)
                ->setContinueOnNoEffect(true)
                ->setContentSourceFromRequest($request);

            $editor->applyTransactions($file, $xactions);
        }

        $transactions = id(PhabricatorFileTransaction::find())
            ->withTransactionTypes(array(PhabricatorTransactions::TYPE_COMMENT));
        $timeline = $this->buildTransactionTimeline($file, $transactions);

        $comment_form = $this->renderCommentForm($file);

        $info = phutil_tag(
            'div',
            array(
                'class' => 'phui-comment-panel-header',
            ),
            $file->getName());

//        require_celerity_resource('phui-comment-panel-css');
        $content = phutil_tag(
            'div',
            array(
                'class' => 'phui-comment-panel',
            ),
            array(
                $info,
                $timeline,
                $comment_form,
            ));

        return (new AphrontAjaxResponse())
            ->setContent($content);
    }

    /**
     * @param PhabricatorFile $file
     * @return PHUIFormLayoutView|\PhutilSafeHTML
     * @throws \yii\base\Exception
     * @throws \PhutilMethodNotImplementedException
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderCommentForm(PhabricatorFile $file)
    {
        $viewer = $this->getViewer();

        if (!$viewer->isLoggedIn()) {
            $login_href = (new PhutilURI('/auth/start/'))
                ->setQueryParam('next', '/' . $file->getMonogram());
            return (new PHUIFormLayoutView())
                ->addClass('phui-comment-panel-empty')
                ->appendChild(
                    (new PHUIButtonView())
                        ->setTag('a')
                        ->setText(\Yii::t("app", 'Log In to Comment'))
                        ->setHref((string)$login_href));
        }

        $draft = PhabricatorDraft::newFromUserAndKey(
            $viewer,
            $file->getPHID());
        $post_uri = $this->getApplicationURI('thread/' . $file->getPHID() . '/');

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->setAction($post_uri)
            ->addSigil('lightbox-comment-form')
            ->addClass('lightbox-comment-form')
            ->setWorkflow(true)
            ->appendChild(
                (new PhabricatorRemarkupControl())
                    ->setUser($viewer)
                    ->setName('comment')
                    ->setValue($draft->getDraft()))
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->setValue(\Yii::t("app", 'Comment')));

        $view = phutil_tag_div('phui-comment-panel', $form);

        return $view;

    }

}
