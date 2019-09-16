<?php

namespace orangins\modules\dashboard\actions\panel;

use orangins\lib\response\Aphront404Response;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\dashboard\actions\PhabricatorDashboardController;
use orangins\modules\dashboard\editors\PhabricatorDashboardPanelEditEngine;
use orangins\modules\dashboard\interfaces\PhabricatorDashboardPanelContainerInterface;
use orangins\modules\dashboard\paneltype\PhabricatorDashboardPanelType;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use PhutilURI;

/**
 * Class PhabricatorDashboardPanelEditController
 * @package orangins\modules\dashboard\actions\panel
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelEditController
    extends PhabricatorDashboardController
{

    /**
     * @return mixed
     * @throws \AphrontDuplicateKeyQueryException
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $engine = (new PhabricatorDashboardPanelEditEngine())
            ->setAction($this);

        // We can create or edit a panel in the context of a dashboard or
        // container panel, like a tab panel. If we started this flow on some
        // container object, we want to return to that container when we're done
        // editing.

        $context_phid = $request->getStr('contextPHID');
        if (strlen($context_phid)) {
            $context = (new PhabricatorObjectQuery())
                ->setViewer($viewer)
                ->withPHIDs(array($context_phid))
                ->requireCapabilities(
                    array(
                        PhabricatorPolicyCapability::CAN_VIEW,
                        PhabricatorPolicyCapability::CAN_EDIT,
                    ))
                ->executeOne();
            if (!$context) {
                return new Aphront404Response();
            }

            if (!($context instanceof PhabricatorDashboardPanelContainerInterface)) {
                return new Aphront404Response();
            }

            $engine
                ->setContextObject($context)
                ->addContextParameter('contextPHID', $context_phid);
        } else {
            $context = null;
        }

        $id = $request->getURIData('id');
        if (!$id) {
            $column_key = $request->getStr('columnKey');

            if ($context) {
                $cancel_uri = $context->getURI();
            } else {
                $cancel_uri = $this->getApplicationURI('panel/');
            }

            $panel_type = $request->getStr('panelType');
            $panel_types = PhabricatorDashboardPanelType::getAllPanelTypes();
            if (empty($panel_types[$panel_type])) {
                return $this->buildPanelTypeResponse($cancel_uri);
            }

            $engine
                ->addContextParameter('panelType', $panel_type)
                ->addContextParameter('columnKey', $column_key)
                ->setPanelType($panel_type)
                ->setColumnKey($column_key);
        }

        return $engine->buildResponse();
    }

    /**
     * @param $cancel_uri
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    private function buildPanelTypeResponse($cancel_uri)
    {
        $viewer = $this->getViewer();
        $request = $this->getRequest();

        $base_uri = $request->getRequestURI();
        $base_uri = new PhutilURI($base_uri);

        $menu = (new PHUIObjectItemListView())
            ->setViewer($viewer)
            ->setFlush(true)
            ->setBig(true);

        /** @var PhabricatorDashboardPanelType[] $panel_types */
        $panel_types = PhabricatorDashboardPanelType::getAllPanelTypes();
        foreach ($panel_types as $panel_type) {
            $item = (new PHUIObjectItemView())
                ->setClickable(true)
                ->setImageIcon($panel_type->getIcon())
                ->setHeader($panel_type->getPanelTypeName())
                ->addAttribute($panel_type->getPanelTypeDescription());

            $type_uri = id(clone $base_uri)
                ->replaceQueryParam('panelType', $panel_type->getPanelTypeKey());

            $item->setHref($type_uri);

            $menu->addItem($item);
        }

        return $this->newDialog()
            ->addBodyClass('p-0')
            ->setTitle(\Yii::t("app",'Choose Panel Type'))
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->appendChild($menu)
            ->addCancelButton($cancel_uri);
    }

}
