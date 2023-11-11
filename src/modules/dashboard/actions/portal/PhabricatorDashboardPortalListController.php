<?php
namespace orangins\modules\dashboard\actions\portal;

final class PhabricatorDashboardPortalListController
  extends PhabricatorDashboardPortalController {

  public function shouldAllowPublic() {
    return true;
  }

  public function run() { $request = $this->getRequest();
    return (new PhabricatorDashboardPortalSearchEngine())
      ->setController($this)
      ->buildResponse();
  }

  protected function buildApplicationCrumbs() {
    $crumbs = parent::buildApplicationCrumbs();

    (new PhabricatorDashboardPortalEditEngine())
      ->setViewer($this->getViewer())
      ->addActionToCrumbs($crumbs);

    return $crumbs;
  }

}
