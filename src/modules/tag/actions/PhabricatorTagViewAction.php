<?php

namespace orangins\modules\tag\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\tag\editors\PhabricatorTagEditEngine;
use orangins\modules\tag\models\PhabricatorTag;
use orangins\modules\tag\models\PhabricatorTagTransaction;
use Exception;

/**
 * Class PhabricatorFileViewAction
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorTagViewAction extends PhabricatorTagAction
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
     * @return Aphront404Response|AphrontRedirectResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');
        $phid = $request->getURIData('phid');

        /** @var PhabricatorTag $tag */
        if ($phid) {
            $tag = PhabricatorTag::find()
                ->setViewer($viewer)
                ->withPHIDs(array($phid))
                ->executeOne();

            if (!$tag) {
                return new Aphront404Response();
            }
            return (new AphrontRedirectResponse())->setURI($tag->getInfoURI());
        }

        $tag = PhabricatorTag::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->executeOne();
        if (!$tag) {
            return new Aphront404Response();
        }

        $header = (new PHUIPageHeaderView())
            ->setUser($viewer)
            ->setPolicyObject($tag)
            ->setHeader($tag->name)
            ->setHeaderIcon('fa-file-o');


        $curtain = $this->buildCurtainView($tag);
        $timeline = $this->buildTransactionView($tag);
        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(
            $tag->getMonogram(),
            $tag->getURI());
        $crumbs->setBorder(true);


        $title = $tag->name;

        $view = (new PHUITwoColumnView())
            ->setCurtain($curtain)
            ->setMainColumn(
                array(
                    $timeline,
                ));

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->setPageObjectPHIDs(array($tag->getPHID()))
            ->appendChild($view);
    }

    /**
     * @param PhabricatorTag $tag
     * @return array
     * @throws \ReflectionException
     * @throws \Throwable
     * @author 陈妙威
     */
    private function buildTransactionView(PhabricatorTag $tag)
    {
        $viewer = $this->getViewer();

        $timeline = $this->buildTransactionTimeline($tag, PhabricatorTagTransaction::find());

        $comment_view = (new PhabricatorTagEditEngine())
            ->setViewer($viewer)
            ->buildEditEngineCommentView($tag);

        $monogram = $tag->getMonogram();

        $timeline->setQuoteRef($monogram);
        $comment_view->setTransactionTimeline($timeline);

        return array(
            $timeline,
            $comment_view,
        );
    }

    /**
     * @param PhabricatorTag $tag
     * @return mixed
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function buildCurtainView(PhabricatorTag $tag)
    {
        $viewer = $this->getViewer();

        $id = $tag->getID();

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $tag,
            PhabricatorPolicyCapability::CAN_EDIT);

        $curtain = $this->newCurtainView($tag);

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app",'Edit Tag'))
                ->setIcon('fa-pencil')
                ->setHref($this->getApplicationURI("index/edit", ['id' => $id]))
                ->setWorkflow(!$can_edit)
                ->setDisabled(!$can_edit));

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app",'Delete Tag'))
                ->setIcon('fa-times')
                ->setHref($this->getApplicationURI("index/delete", ['id' => $id]))
                ->setWorkflow(true)
                ->setDisabled(!$can_edit));
        return $curtain;
    }
}
