<?php

namespace orangins\modules\dashboard\actions\panel;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\modules\dashboard\actions\PhabricatorDashboardController;
use orangins\modules\dashboard\engine\PhabricatorDashboardPanelRenderingEngine;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use yii\base\Exception;

/**
 * Class PhabricatorDashboardPanelRenderController
 * @package orangins\modules\dashboard\actions\panel
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelRenderController
    extends PhabricatorDashboardController
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
     * @return Aphront404Response|AphrontAjaxResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');

        $panel = PhabricatorDashboardPanel::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->executeOne();
        if (!$panel) {
            return new Aphront404Response();
        }

        if ($request->isAjax()) {
            $parent_phids = $request->getStrList('parentPanelPHIDs', null);
            if ($parent_phids === null) {
                throw new Exception(
                    \Yii::t("app",
                        'Required parameter `parentPanelPHIDs` is not present in ' .
                        'request.'));
            }
        } else {
            $parent_phids = array();
        }

        $engine = (new PhabricatorDashboardPanelRenderingEngine())
            ->setViewer($viewer)
            ->setPanel($panel)
            ->setPanelPHID($panel->getPHID())
            ->setParentPanelPHIDs($parent_phids)
            ->setHeaderMode($request->getStr('headerMode'))
            ->setPanelKey($request->getStr('panelKey'));

        $context_phid = $request->getStr('contextPHID');
        if ($context_phid) {
            $context = (new PhabricatorObjectQuery())
                ->setViewer($viewer)
                ->withPHIDs(array($context_phid))
                ->executeOne();
            if (!$context) {
                return new Aphront404Response();
            }
            $engine->setContextObject($context);
        }

        $rendered_panel = $engine->renderPanel();

        if ($request->isAjax()) {
            return (new AphrontAjaxResponse())
                ->setContent(
                    array(
                        'panelMarkup' => hsprintf('%s', $rendered_panel),
                    ));
        }

        $crumbs = $this->buildApplicationCrumbs()
            ->addTextCrumb(\Yii::t("app",'Panels'), $this->getApplicationURI('panel/'))
            ->addTextCrumb($panel->getMonogram(), '/' . $panel->getMonogram())
            ->addTextCrumb(\Yii::t("app",'Standalone View'))
            ->setBorder(true);

        $view = (new PHUIBoxView())
            ->addClass('dashboard-view')
            ->appendChild($rendered_panel);

        return $this->newPage()
            ->setTitle(array(\Yii::t("app",'Panel'), $panel->getName()))
            ->setCrumbs($crumbs)
            ->appendChild($view);

    }

}
