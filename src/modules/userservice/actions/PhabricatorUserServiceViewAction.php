<?php

namespace orangins\modules\userservice\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\tag\actions\PhabricatorTagAction;
use orangins\modules\userservice\editors\PhabricatorUserServiceEditEngine;
use orangins\modules\userservice\models\PhabricatorUserService;
use orangins\modules\userservice\models\PhabricatorUserServiceTransaction;
use Exception;
use orangins\modules\userservice\servicetype\PhabricatorUserServiceType;

/**
 * Class PhabricatorUserServiceViewAction
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorUserServiceViewAction extends PhabricatorTagAction
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
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');
        $phid = $request->getURIData('phid');

        if ($phid) {
            $file = PhabricatorUserService::find()
                ->setViewer($viewer)
                ->withPHIDs(array($phid))
                ->executeOne();

            if (!$file) {
                return new Aphront404Response();
            }
            return (new AphrontRedirectResponse())->setURI($file->getInfoURI());
        }

        $file = PhabricatorUserService::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->executeOne();
        if (!$file) {
            return new Aphront404Response();
        }

        $header = (new PHUIPageHeaderView())
            ->setUser($viewer)
            ->setPolicyObject($file)
            ->setHeader($file->name)
            ->setHeaderIcon('fa-file-o');


        $timeline = $this->buildTransactionView($file);
        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(
            $file->name,
            $file->getURI());
        $crumbs->setBorder(true);

        $object_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app",'Task Metadata'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY);

        $title = $file->name;


        $curtain = $this->buildCurtainView($file);
        $view = (new PHUITwoColumnView())
            ->setCurtain($curtain)
            ->setMainColumn(
                array(
                    $object_box,
                    $timeline,
                ));

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->setPageObjectPHIDs(array($file->getPHID()))
            ->appendChild($view);
    }

    /**
     * @param PhabricatorUserService $sxuserservice
     * @return mixed
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function buildCurtainView(PhabricatorUserService $sxuserservice)
    {
        $viewer = $this->getViewer();
        $id = $sxuserservice->getID();
        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $sxuserservice,
            PhabricatorPolicyCapability::CAN_EDIT);


        $phabricatorTaskTypes = PhabricatorUserServiceType::getAllTypes();
        $phabricatorTaskType = $phabricatorTaskTypes[$sxuserservice->type];

        $curtain = $this->newCurtainView($sxuserservice);
        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app",'Edit Task'))
                ->setIcon('fa-pencil')
                ->setHref($this->getApplicationURI("index/edit", ['id' => $sxuserservice->getID(), 'formKey' => $phabricatorTaskType->getKey()]))
                ->setWorkflow(!$can_edit)
                ->setDisabled(!$can_edit));

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setName(\Yii::t("app",'Delete Task'))
                ->setIcon('fa-times')
                ->setHref($this->getApplicationURI("index/delete", ['id' => $id]))
                ->setWorkflow(true)
                ->setDisabled(!$can_edit));
        return $curtain;
    }

    /**
     * @param PhabricatorUserService $file
     * @return array
     * @throws \ReflectionException
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function buildTransactionView(PhabricatorUserService $file)
    {
        $viewer = $this->getViewer();

        $timeline = $this->buildTransactionTimeline($file, PhabricatorUserServiceTransaction::find());

        $comment_view = (new PhabricatorUserServiceEditEngine())
            ->setViewer($viewer)
            ->buildEditEngineCommentView($file);

//        $monogram = $file->getMonogram();
//        $timeline->setQuoteRef($monogram);
        $comment_view->setTransactionTimeline($timeline);

        return array(
            $timeline,
            $comment_view,
        );
    }

}
