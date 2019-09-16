<?php
namespace orangins\modules\dashboard\actions\portal;

final class PhabricatorDashboardPortalEditController
  extends PhabricatorDashboardPortalController {

  public function run() { $request = $this->getRequest();
    return (new PhabricatorDashboardPortalEditEngine())
      ->setController($this)
      ->buildResponse();
  }

}
