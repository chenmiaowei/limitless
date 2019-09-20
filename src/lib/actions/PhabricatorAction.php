<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/2/19
 * Time: 11:53 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\actions;

use orangins\lib\controllers\PhabricatorController;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\markup\PhabricatorMarkupEngine;
use orangins\lib\view\extension\PHUICurtainExtension;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\lib\view\layout\PHUICurtainView;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\home\application\PhabricatorHomeApplication;
use orangins\modules\home\engine\PhabricatorHomeProfileMenuEngine;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\transactions\engine\PhabricatorTimelineEngine;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\models\PhabricatorApplicationTransactionComment;
use PhutilURI;
use orangins\lib\PhabricatorApplication;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\control\AphrontCursorPagerView;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\lib\view\phui\PHUICrumbsView;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserEmail;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\query\PhabricatorApplicationTransactionQuery;
use Yii;
use yii\base\Action;
use Exception;

/**
 * Class PhabricatorAction
 * @package orangins\lib\actions
 * @author 陈妙威
 */
class PhabricatorAction extends Action
{
    /**
     * @var AphrontRequest
     */
    public $request;

    /**
     * @var PhabricatorController
     */
    public $controller;

    /**
     * @var PhabricatorUser
     */
    public $viewer;
    /**
     * @var bool
     */
    public $enableCsrfValidation = true;

    /**
     * @var PhabricatorAction
     */
    private $delegatingAction;


    /**
     * PhabricatorAction constructor.
     * @param $id
     * @param PhabricatorController $controller
     * @param array $config
     */
    public function __construct($id, PhabricatorController $controller, array $config = [])
    {
        parent::__construct($id, $controller, $config);
        $this->setController($controller);
        $this->setRequest($this->getController()->getRequest());
        $this->setViewer($this->getController()->getViewer());
    }

    /**
     * @return PhabricatorAction
     */
    public function getDelegatingAction()
    {
        return $this->delegatingAction;
    }

    /**
     * @param PhabricatorAction $delegatingAction
     * @return self
     */
    public function setDelegatingAction($delegatingAction)
    {
        $this->delegatingAction = $delegatingAction;
        return $this;
    }


    /**
     * @return AphrontRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param AphrontRequest $request
     * @return self
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return PhabricatorController
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param PhabricatorController $controller
     * @return self
     */
    public function setController($controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @return PhabricatorUser
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return self
     */
    public function setViewer($viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }


    /**
     * 创建新的页面
     * @return PhabricatorStandardPageView
     * @author 陈妙威
     */
    public function newPage()
    {
        /** @var PhabricatorStandardPageView $page */
        $page = (new PhabricatorStandardPageView())
            ->setView($this->controller->getView())
            ->setRequest($this->getRequest())
            ->setAction($this)
            ->setDeviceReady(true);

        $application = $this->controller->getCurrentApplication();
        if ($application) {
            $page->setApplicationName($application->getName());
            if ($application->getTitleGlyph()) {
                $page->setGlyph($application->getTitleGlyph());
            }
        }

        $viewer = $this->getRequest()->getViewer();
        if ($viewer) {
            $page->setViewer($viewer);
        }

        return $page;
    }

    /**
     * 代理执行其他的Action
     * @param PhabricatorAction $action
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    final public function delegateToAction(PhabricatorAction $action)
    {
        $request = $this->getRequest();

        $action->setDelegatingAction($this);
        $action->setRequest($request);

        return $action->runWithParams([]);
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireLogin()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireAdmin()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireEnabledUser()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPartialSessions()
    {
        return false;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function shouldRequireEmailVerification()
    {
        return PhabricatorUserEmail::isEmailVerificationRequired();
    }

    /**
     * @param $parameter_name
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowRestrictedParameter($parameter_name)
    {
        return false;
    }

    /**
     * @return bool
     * @throws Exception
     * @author 陈妙威
     */
    public function shouldRequireMultiFactorEnrollment()
    {
        if (!$this->shouldRequireLogin()) {
            return false;
        }

        if (!$this->shouldRequireEnabledUser()) {
            return false;
        }

        if ($this->shouldAllowPartialSessions()) {
            return false;
        }

        $user = $this->getRequest()->getViewer();
        if (!$user->getIsStandardUser()) {
            return false;
        }

        return PhabricatorEnv::getEnvConfig('security.require-multi-factor-auth');
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowLegallyNonCompliantUsers()
    {
        return false;
    }

    /**
     * 启用整个页面的拖拽上传文件
     * @return bool
     * @author 陈妙威
     */
    public function isGlobalDragAndDropUploadEnabled()
    {
        return false;
    }

    /**
     * @return PHUICrumbsView
     * @throws Exception
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function buildApplicationCrumbsForEditEngine()
    {
        return $this->controller->buildApplicationCrumbsForEditEngine();
    }

    /**
     * 创建面包屑
     * @return PHUICrumbsView
     * @throws Exception
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $view = $this->controller->buildApplicationCrumbs();
        return $view;
    }

    /**
     * 获取当前应用的链接
     * @param string $path
     * @param $params
     * @return string
     * @throws Exception
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function getApplicationURI($path = null, $params = [])
    {
        if (!$this->controller->getCurrentApplication()) {
            throw new Exception(Yii::t('app', 'No application!'));
        }
        return $this->controller->getCurrentApplication()->getApplicationURI($path, $params);
    }


    /**
     * @return null
     * @throws Exception
     * @author 陈妙威
     */
//    public function requireLegalpadSignatures()
//    {
//        if (!$this->shouldRequireLogin()) {
//            return null;
//        }
//
//        if ($this->shouldAllowLegallyNonCompliantUsers()) {
//            return null;
//        }
//
//        $viewer = $this->getViewer();
//
//        if (!$viewer->hasSession()) {
//            return null;
//        }
//
//        $session = $viewer->getSession();
//        if ($session->getIsPartial()) {
//            // If the user hasn't made it through MFA yet, require they survive
//            // MFA first.
//            return null;
//        }
//
//        if ($session->getSignedLegalpadDocuments()) {
//            return null;
//        }
//
//        if (!$viewer->isLoggedIn()) {
//            return null;
//        }
//
//        $must_sign_docs = array();
//        $sign_docs = array();
//
////        $legalpad_class = 'PhabricatorLegalpadApplication';
////        $legalpad_installed = PhabricatorApplication::isClassInstalledForViewer(
////            $legalpad_class,
////            $viewer);
////        if ($legalpad_installed) {
////            $sign_docs = (new LegalpadDocumentQuery())
////                ->setViewer($viewer)
////                ->withSignatureRequired(1)
////                ->needViewerSignatures(true)
////                ->setOrder('oldest')
////                ->execute();
////
////            foreach ($sign_docs as $sign_doc) {
////                if (!$sign_doc->getUserSignature($viewer->getPHID())) {
////                    $must_sign_docs[] = $sign_doc;
////                }
////            }
////        }
//
//        if (!$must_sign_docs) {
//            // If nothing needs to be signed (either because there are no documents
//            // which require a signature, or because the user has already signed
//            // all of them) mark the session as good and continue.
//            $engine = (new PhabricatorAuthSessionEngine())
//                ->signLegalpadDocuments($viewer, $sign_docs);
//
//            return null;
//        }
//
//        $request = $this->getRequest();
//        $request->setURIMap(
//            array(
//                'id' => OranginsUtil::head($must_sign_docs)->getID(),
//            ));
//
//        $application = PhabricatorApplication::getByClass($legalpad_class);
//        $this->setCurrentApplication($application);
//
//        $controller = new LegalpadDocumentSignController();
//        return $this->delegateToController($controller);
//    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        return null;
    }


    /**
     * Create a new @{class:AphrontDialogView} with defaults filled in.
     *
     * @return AphrontDialogView New dialog.
     */
    public function newDialog()
    {
        return (new AphrontDialogView())
            ->setViewer($this->getRequest()->getViewer())
            ->setSubmitURI(Yii::$app->request->url);
    }


    /**
     * @param $capability
     * @return bool
     * @throws Exception
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function hasApplicationCapability($capability)
    {
        return PhabricatorPolicyFilter::hasCapability(
            $this->getRequest()->getViewer(),
            $this->controller->getCurrentApplication(),
            $capability);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getHomeUrl()
    {
        return Yii::$app->getHomeUrl();
    }


    /**
     * @param $capability
     * @throws Exception
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function requireApplicationCapability($capability)
    {
        PhabricatorPolicyFilter::requireCapability(
            $this->getRequest()->getViewer(),
            $this->controller->getCurrentApplication(),
            $capability);
    }

    /**
     * @param PhabricatorApplicationTransactionInterface|ActiveRecordPHID $object
     * @param PhabricatorApplicationTransactionQuery $query
     * @param PhabricatorMarkupEngine $engine
     * @param array $view_data
     * @return mixed
     * @throws \Throwable
     * @author 陈妙威
     */
    protected function buildTransactionTimeline(
        PhabricatorApplicationTransactionInterface $object,
        PhabricatorApplicationTransactionQuery $query,
        PhabricatorMarkupEngine $engine = null,
        $view_data = array())
    {

        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $xaction = $object->getApplicationTransactionTemplate();

        $pager = (new AphrontCursorPagerView())
            ->readFromRequest($request)
            ->setURI(new PhutilURI(
                '/transactions/showolder/' . $object->getPHID() . '/'));

        /** @var PhabricatorApplicationTransaction $xactions */
        $xactions = $query
            ->setViewer($viewer)
            ->withObjectPHIDs(array($object->getPHID()))
            ->needComments(true)
            ->executeWithCursorPager($pager);
        $xactions = array_reverse($xactions);

        $timeline_engine = PhabricatorTimelineEngine::newForObject($object)
            ->setViewer($viewer)
            ->setTransactions($xactions)
            ->setViewData($view_data);

        $view = $timeline_engine->buildTimelineView();

        if ($engine) {
            foreach ($xactions as $xaction) {
                if ($xaction->getComment()) {
                    $engine->addObject(
                        $xaction->getComment(),
                        PhabricatorApplicationTransactionComment::MARKUP_FIELD_COMMENT);
                }
            }
            $engine->process();
            $view->setMarkupEngine($engine);
        }

        $timeline = $view
            ->setPager($pager)
            ->setQuoteTargetID($this->getRequest()->getStr('quoteTargetID'))
            ->setQuoteRef($this->getRequest()->getStr('quoteRef'));

        return $timeline;
    }


    /**
     * @param null $object
     * @return mixed
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function newCurtainView($object = null)
    {
        $viewer = $this->getViewer();

        $action_id = JavelinHtml::generateUniqueNodeId();

        $action_list = (new PhabricatorActionListView())
            ->setViewer($viewer)
            ->setID($action_id);

        // NOTE: Applications (objects of class PhabricatorApplication) can't
        // currently be set here, although they don't need any of the extensions
        // anyway. This should probably work differently than it does, though.
        if ($object) {
            if ($object instanceof ActiveRecordPHID) {
                $action_list->setObject($object);
            }
        }

        $curtain = (new PHUICurtainView())
            ->setViewer($viewer)
            ->setActionList($action_list);

        if ($object) {
            $panels = PHUICurtainExtension::buildExtensionPanels($viewer, $object);
            foreach ($panels as $panel) {
                $curtain->addPanel($panel);
            }
        }

        return $curtain;
    }

    /**
     * @param AphrontSideNavFilterView $nav
     * @param $capability
     * @param $key
     * @param $name
     * @param null $uri
     * @param null $icon
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function addNavFilter(AphrontSideNavFilterView $nav, $capability, $key, $name, $uri = null, $icon = null)
    {
        $hasCapability = PhabricatorPolicyFilter::hasCapability($this->getViewer(), $this->controller->getCurrentApplication(), $capability);
        if($hasCapability)  {
            $nav->addFilter($key, $name, $uri, $icon);
        }
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $item_identifier
     * @return \orangins\lib\view\layout\AphrontSideNavFilterView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \Exception
     * @author 陈妙威
     */
    final protected function newNavigation(
        PhabricatorUser $viewer,
        $item_identifier = null) {
        $home_app = (new PhabricatorApplicationQuery())
            ->setViewer($viewer)
            ->withShortName(false)
            ->withClasses(array(PhabricatorHomeApplication::class))
            ->withInstalled(true)
            ->executeOne();

        $engine = (new PhabricatorHomeProfileMenuEngine())
            ->setViewer($viewer)
            ->setProfileObject($home_app)
            ->setCustomPHID($viewer->getPHID())
            ->setAction($this)
            ->setShowContentCrumbs(false);

        $view_list = $engine->newProfileMenuItemViewList();

        $item_identifier && $view_list->setSelectedViewWithItemIdentifier($item_identifier);

        $navigation = $view_list->newNavigationView();

        return $navigation;
    }
}