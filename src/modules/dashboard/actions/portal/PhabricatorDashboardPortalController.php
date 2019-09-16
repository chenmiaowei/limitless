<?php
namespace orangins\modules\dashboard\actions\portal;

abstract class PhabricatorDashboardPortalController
  extends PhabricatorDashboardController {

  protected function buildApplicationCrumbs() {
    $crumbs = new PHUICrumbsView();

    $crumbs->addCrumb(
      (new PHUICrumbView())
        ->setHref('/portal/')
        ->setName(\Yii::t("app",'Portals'))
        ->setIcon('fa-compass'));

    return $crumbs;
  }

}
